<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Item;
use App\Models\IssuedLog;
use App\Models\DamageReport;
use App\Services\FormArchiveService;

class IssuedDamageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_no' => ['required', 'string', 'max:255'],
            'observation' => ['required', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();

        try {
            $serialNo = $validated['serial_no'];
            $obs = $validated['observation'];

            $userId = Auth::id();
            if (!$userId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.',
                ], 403);
            }

            // Find item
            $item = Item::where('serial_no', $serialNo)->first();
            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Item not found for serial: {$serialNo}",
                ], 404);
            }

            // Get latest issued log for borrower/reference
            $issued = IssuedLog::where('serial_no', $serialNo)
                ->orderByDesc('issue_id')
                ->first();

            if (!$issued) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No issued record found for serial: {$serialNo}",
                ], 422);
            }

            $borrowerName = $issued->borrower_name ?? 'N/A';

            // Mark item as damaged
            $item->status = 'Damaged';
            $item->save();

            // Insert damage report
            $damage = DamageReport::create([
                'serial_no'     => $serialNo,
                'borrower_name' => $borrowerName,
                'reported_at'   => now(),
                'observation'   => $obs,
                'reported_by'   => $userId,
            ]);

            // Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type' => 'inventory',
                'title' => 'Item Damaged',
                'message' => "Serial No. {$serialNo} was marked as damaged. Observation: {$obs}",
                'severity' => 'warning',
                'entity_type' => 'damage_report',
                'entity_id' => $damage->damage_id ?? null,
                'action_url' => 'http://127.0.0.1:8000/dashboard?section=issued',
                'data' => json_encode([
                    'serial_no' => $serialNo,
                    'item_id' => $item->item_id ?? null,
                    'reference_no' => $issued->reference_no ?? null,
                    'borrower_name' => $borrowerName,
                    'observation' => $obs,
                    'reported_by_user_id' => $userId,
                    'reported_at' => now()->toDateTimeString(),
                ]),
                'created_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send only to Admin users
            $adminUsers = DB::table('users')
                ->where('role', 'Admin')
                ->pluck('user_id');

            $recipientRows = [];
            foreach ($adminUsers as $adminUserId) {
                $recipientRows[] = [
                    'notif_id' => $notifId,
                    'recipient_user_id' => $adminUserId,
                    'read_at' => null,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($recipientRows)) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            // Archive check
            if (!empty($issued->reference_no)) {
                FormArchiveService::tryArchiveByReference($issued->reference_no);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Damage reported successfully.',
                'damage_id' => $damage->damage_id ?? null,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showBySerial($serialNo)
    {
        $damage = DamageReport::where('serial_no', $serialNo)
            ->orderByDesc('reported_at')
            ->first();

        if (!$damage) {
            return response()->json([
                'success' => false,
                'message' => 'No damage report found for this serial.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'damage' => $damage
        ]);
    }

    public function table()
    {
        $damageReports = DamageReport::with('item')
            ->orderByDesc('reported_at')
            ->get();

        return view('partials.damage_table_rows', compact('damageReports'));
    }
}