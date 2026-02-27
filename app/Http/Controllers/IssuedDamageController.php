<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Item;
use App\Models\IssuedLog;
use App\Models\DamageReport;
use App\Models\Notification;
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

        // ✅ Find item
        $item = Item::where('serial_no', $serialNo)->first();
        if (!$item) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Item not found for serial: {$serialNo}",
            ], 404);
        }

        // ✅ Get the latest issuedlog for this serial (to fetch borrower_name + reference_no)
        $issued = IssuedLog::where('serial_no', $serialNo)
            ->orderByDesc('issue_id') // safest (auto inc)
            ->first();

        if (!$issued) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "No issued record found for serial: {$serialNo}",
            ], 422);
        }

        $borrowerName = $issued->borrower_name ?? 'N/A';

        // ✅ Mark item as Damaged
        $item->status = 'Damaged';
        $item->save();

        // ✅ Insert into damagereports INCLUDING borrower_name
        $damage = DamageReport::create([
            'serial_no'      => $serialNo,
            'borrower_name'  => $borrowerName,
            'reported_at'    => now(),
            'observation'    => $obs,
            'reported_by'    => $userId,
        ]);

        // ✅ Notification
        Notification::create([
            'user_id' => $userId,
            'item_id' => $item->item_id,
            'title'   => 'Item Damaged',
            'message' => "Serial No. {$serialNo} was marked as damaged. Observation: {$obs}",
            'type'    => 'inventory',
            'role'    => 'Admin',
            'is_read' => 0,
        ]);

        // ✅ Archive check (based on reference_no from issuedlog)
        if (!empty($issued->reference_no)) {
            \App\Services\FormArchiveService::tryArchiveByReference($issued->reference_no);
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