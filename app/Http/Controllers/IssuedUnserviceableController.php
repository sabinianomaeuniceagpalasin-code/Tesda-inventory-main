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
            return response()->json([
                'status' => 'error',
                'message' => 'Issued item not found.'
            ], 404);
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // 1) Prevent duplicate unserviceable marking if already marked
            $currentItem = DB::table('items')
                ->where('serial_no', $issuedItem->serial_no)
                ->first();

            if (!$currentItem) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item record not found.'
                ], 404);
            }

            if (($currentItem->status ?? null) === 'Unserviceable') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item is already marked as Unserviceable.'
                ], 422);
            }

            // 2) Mark item as Unserviceable
            DB::table('items')
                ->where('serial_no', $issuedItem->serial_no)
                ->update([
                    'status' => 'Unserviceable',
                    'updated_at' => now()
                ]);

            // 3) Save unserviceable report
            DB::table('unserviceablereports')->insert([
                'serial_no' => $issuedItem->serial_no,
                'reason' => $request->reason,
                'borrower_name' => $issuedItem->borrower_name,
                'reported_by' => $userId,
                'reported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4) Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type' => 'inventory',
                'title' => 'Item Marked as Unserviceable',
                'message' => "Item '{$issuedItem->item_name}' (Serial: {$issuedItem->serial_no}) was marked as unserviceable. Reason: {$request->reason}",
                'severity' => 'warning',
                'entity_type' => 'item',
                'entity_id' => $issuedItem->item_id,
                'action_url' => 'http://127.0.0.1:8000/dashboard?section=issued',
                'data' => json_encode([
                    'item_id' => $issuedItem->item_id,
                    'item_name' => $issuedItem->item_name,
                    'serial_no' => $issuedItem->serial_no,
                    'property_no' => $issuedItem->property_no,
                    'reference_no' => $issuedItem->reference_no,
                    'borrower_name' => $issuedItem->borrower_name,
                    'reason' => $request->reason,
                    'reported_by_user_id' => $userId,
                    'reported_at' => now()->toDateTimeString(),
                ]),
                'created_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5) Send only to Admin users
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

            // 6) Archive check
            if (!empty($issuedItem->reference_no)) {
                FormArchiveService::tryArchiveByReference($issuedItem->reference_no);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item marked as Unserviceable successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Unserviceable Error [Issue ID: {$id}]: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}