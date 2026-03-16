<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\IssuedLog;
use App\Models\Item;
use App\Services\FormArchiveService;

class IssuedUnserviceableController extends Controller
{
    public function markUnserviceable(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Get issued log model directly
            $issuedLog = IssuedLog::lockForUpdate()->find($id);

            if (!$issuedLog) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Issued item not found.'
                ], 404);
            }

            // Prevent duplicate closing
            if (!empty($issuedLog->actual_return_date)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'This issued item is already closed/returned.'
                ], 422);
            }

            // Get item
            $item = Item::where('serial_no', $issuedLog->serial_no)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item record not found.'
                ], 404);
            }

            if (($item->status ?? null) === 'Unserviceable') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item is already marked as Unserviceable.'
                ], 422);
            }

            // Compute usage hours
            $endTime = now();
            $issuedDate = Carbon::parse($issuedLog->issued_date);
            $hoursUsed = max(1, $issuedDate->diffInHours($endTime));

            // Close issuance
            $issuedLog->actual_return_date = $endTime;
            $issuedLog->usage_hours = $hoursUsed;
            $issuedLog->save();

            // Mark item as Unserviceable + update totals
            $item->status = 'Unserviceable';
            $item->usage_count = ($item->usage_count ?? 0) + 1;
            $item->total_usage_hours = ($item->total_usage_hours ?? 0) + $hoursUsed;
            $item->save();

            // Save unserviceable report
            DB::table('unserviceablereports')->insert([
                'serial_no' => $issuedLog->serial_no,
                'reason' => $request->reason,
                'borrower_name' => $issuedLog->borrower_name,
                'reported_by' => $userId,
                'reported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type' => 'inventory',
                'title' => 'Item Marked as Unserviceable',
                'message' => "Item '{$item->item_name}' (Serial: {$item->serial_no}) was marked as unserviceable. Reason: {$request->reason}",
                'severity' => 'warning',
                'entity_type' => 'item',
                'entity_id' => $item->item_id,
                'action_url' => 'http://127.0.0.1:8000/dashboard?section=issued',
                'data' => json_encode([
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_name,
                    'serial_no' => $item->serial_no,
                    'property_no' => $item->property_no,
                    'reference_no' => $issuedLog->reference_no,
                    'borrower_name' => $issuedLog->borrower_name,
                    'reason' => $request->reason,
                    'reported_by_user_id' => $userId,
                    'reported_at' => now()->toDateTimeString(),
                    'usage_hours' => $hoursUsed,
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
            if (!empty($issuedLog->reference_no)) {
                FormArchiveService::tryArchiveByReference($issuedLog->reference_no);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item marked as Unserviceable successfully.',
                'usage_hours' => $hoursUsed,
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