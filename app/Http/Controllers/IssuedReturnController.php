<?php

namespace App\Http\Controllers;

use App\Models\IssuedLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FormArchiveService;

class IssuedReturnController extends Controller
{
    public function returnItem($id)
    {
        DB::beginTransaction();

        try {
            // 1) Get issued record
            $issued = IssuedLog::findOrFail($id);

            // Prevent double return
            if (!empty($issued->actual_return_date)) {
                return response()->json([
                    'success' => false,
                    'error' => 'This item has already been returned.'
                ], 422);
            }

            // 2) Mark return datetime
            $issued->actual_return_date = now();
            $issued->save();

            // 3) Update item status to Available
            $item = Item::where('serial_no', $issued->serial_no)->first();
            if ($item) {
                $item->status = 'Available';
                $item->save();
            }

            // 4) Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type' => 'inventory',
                'title' => 'Item Returned',
                'message' => 'Serial No. ' . $issued->serial_no . ' has been returned.',
                'severity' => 'info',
                'entity_type' => 'item',
                'entity_id' => $item->item_id ?? null,
                'action_url' => 'http://127.0.0.1:8000/dashboard?section=issued',
                'data' => json_encode([
                    'serial_no' => $issued->serial_no,
                    'reference_no' => $issued->reference_no,
                    'returned_by_user_id' => Auth::id(),
                    'returned_at' => now()->toDateTimeString(),
                ]),
                'created_by_user_id' => Auth::id(),
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

            // 6) Archive check using the service
            $reference = $issued->reference_no;
            if ($reference) {
                FormArchiveService::tryArchiveByReference($reference);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item returned successfully.',
                'reference_no' => $reference
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}