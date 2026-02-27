<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FormArchiveService;

class IssuedUnserviceableController extends Controller
{
    public function markUnserviceable(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // ✅ Get issued + item details
        $issuedItem = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->where('i.issue_id', $id)
            ->select(
                'i.*',
                'it.item_id',
                'it.item_name',
                'it.serial_no',
                'it.property_no',
                'i.reference_no',
                'i.borrower_name'
            )
            ->first();

        if (!$issuedItem) {
            return response()->json(['status' => 'error', 'message' => 'Issued item not found.']);
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        DB::beginTransaction();
        try {
            // 1) Mark item as Unserviceable
            DB::table('items')
                ->where('serial_no', $issuedItem->serial_no)
                ->update([
                    'status' => 'Unserviceable',
                    'updated_at' => now()
                ]);

            // 2) Save unserviceable report (make sure columns exist)
            DB::table('unserviceablereports')->insert([
                'serial_no' => $issuedItem->serial_no,
                'reason' => $request->reason,
                'borrower_name' => $issuedItem->borrower_name, // if you added this column
                'reported_by' => $userId,
                'reported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3) Notification
            DB::table('notifications')->insert([
                'item_id' => $issuedItem->item_id,
                'user_id' => $userId,
                'title' => 'Item marked as Unserviceable',
                'message' => "Item '{$issuedItem->item_name}' (Serial: {$issuedItem->serial_no}) marked unserviceable. Reason: {$request->reason}",
                'type' => 'inventory',
                'role' => 'Admin',
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 4) Archive check
            if (!empty($issuedItem->reference_no)) {
                FormArchiveService::tryArchiveByReference($issuedItem->reference_no);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Item marked as Unserviceable successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Unserviceable Error [Issue ID: {$id}]: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}