<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\DamageReport;

class IssuedDamageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'serial_no'   => 'required|string|exists:items,serial_no',
            'observation' => 'required|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $serialNo    = trim($request->input('serial_no'));
        $observation = trim($request->input('observation'));

        $item = Item::where('serial_no', $serialNo)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        $allowedStatuses = ['Issued', 'Available', 'For Repair', 'Maintenance'];
        if (!in_array($item->status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => "Item cannot be reported as damaged from its current status: {$item->status}.",
            ], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('damage_images', 'public');
        }

        DB::beginTransaction();

        try {
            $item->status     = 'Damaged';
            $item->updated_at = now();
            $item->save();

            $latestLog = DB::table('issuedlog')
                ->where('serial_no', $serialNo)
                ->orderByDesc('issue_id')
                ->first();

            $damageId = DB::table('damagereports')->insertGetId([
                'serial_no'     => $serialNo,
                'observation'   => $observation,
                'borrower_name' => $latestLog->borrower_name ?? null,
                'reported_by'   => auth()->id(),
                'reported_at'   => now(),
                'image_path'    => $imagePath,
                'is_ticketed'   => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $notifId = DB::table('notifications')->insertGetId([
                'type'               => 'damage_report',
                'title'              => 'Damage Report Filed',
                'message'            => "{$item->item_name} ({$serialNo}) was reported as damaged. Reason: {$observation}",
                'severity'           => 'warning',
                'entity_type'        => 'damage_report',
                'entity_id'          => $damageId,
                'action_url'         => route('dashboard', ['section' => 'damaged']),
                'data'               => json_encode([
                    'serial_no'   => $serialNo,
                    'item_name'   => $item->item_name,
                    'observation' => $observation,
                    'borrower'    => $latestLog->borrower_name ?? null,
                    'image_url'   => $imagePath ? asset('storage/' . $imagePath) : null,
                ]),
                'created_by_user_id' => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $recipientIds = DB::table('users')
                ->whereIn('role', ['Admin', 'Property Custodian'])
                ->pluck('user_id');

            $recipientRows = $recipientIds->map(fn($uid) => [
                'notif_id'          => $notifId,
                'recipient_user_id' => $uid,
                'read_at'           => null,
                'deleted_at'        => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ])->all();

            if (!empty($recipientRows)) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as damaged and report created successfully.',
                'damage'  => [
                    'damage_id'   => $damageId,
                    'serial_no'   => $serialNo,
                    'item_name'   => $item->item_name,
                    'observation' => $observation,
                    'reported_at' => now()->format('F d, Y'),
                    'image_url'   => $imagePath ? asset('storage/' . $imagePath) : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to report damage: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showBySerial($serialNo)
    {
        $report = DB::table('damagereports as d')
            ->leftJoin('items as i', 'i.serial_no', '=', 'd.serial_no')
            ->where('d.serial_no', $serialNo)
            ->orderByDesc('d.reported_at')
            ->select(
                'd.damage_id',
                'd.serial_no',
                'd.observation',
                'd.reported_at',
                'd.image_path',
                'd.is_ticketed',
                'i.item_name'
            )
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'No damage report found for this serial number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report'  => [
                'damage_id'   => $report->damage_id,
                'serial_no'   => $report->serial_no,
                'item_name'   => $report->item_name,
                'observation' => $report->observation,
                'reported_at' => $report->reported_at
                                    ? Carbon::parse($report->reported_at)->format('F d, Y')
                                    : '-',
                'is_ticketed' => (bool) $report->is_ticketed,
                'image_url'   => $report->image_path
                                    ? asset('storage/' . $report->image_path)
                                    : null,
            ],
        ]);
    }
}