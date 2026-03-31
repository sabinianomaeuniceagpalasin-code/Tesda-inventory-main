<?php

namespace App\Http\Controllers;

use App\Models\IssuedLog;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FormArchiveService;
use Carbon\Carbon;

class IssuedReturnController extends Controller
{
    public function returnItem($id)
    {
        DB::beginTransaction();

        try {
            // 1) Get issued record with row-level lock to prevent race conditions
            $issued = IssuedLog::lockForUpdate()->findOrFail($id);

            // Prevent double return (strict null check)
            if ($issued->actual_return_date !== null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'This item has already been returned.'
                ], 422);
            }

            $returnedAt = now();

            // 2) Compute usage hours from issued_date to returnedAt
            $issuedDate = Carbon::parse($issued->issued_date);
            $hoursUsed = max(1, $issuedDate->diffInHours($returnedAt));

            // 3) Save issued record
            $issued->actual_return_date = $returnedAt;
            $issued->usage_hours = $hoursUsed;
            $issued->save();

            // 4) Update item status + usage counters
            $item = Item::where('serial_no', $issued->serial_no)->lockForUpdate()->first();
            if ($item) {
                $item->status = 'Available';
                $item->usage_count = ($item->usage_count ?? 0) + 1;
                $item->total_usage_hours = ($item->total_usage_hours ?? 0) + $hoursUsed;
                $item->save();
            }

            // 5) Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type'              => 'inventory',
                'title'             => 'Item Returned',
                'message'           => 'Serial No. ' . $issued->serial_no . ' has been returned.',
                'severity'          => 'info',
                'entity_type'       => 'item',
                'entity_id'         => $item->item_id ?? null,
                'action_url'        => 'http://127.0.0.1:8000/dashboard?section=issued',
                'data'              => json_encode([
                    'serial_no'           => $issued->serial_no,
                    'reference_no'        => $issued->reference_no,
                    'returned_by_user_id' => Auth::id(),
                    'returned_at'         => $returnedAt->toDateTimeString(),
                    'usage_hours'         => $hoursUsed,
                ]),
                'created_by_user_id' => Auth::id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // 6) Send only to Admin users
            $adminUsers = DB::table('users')
                ->where('role', 'Admin')
                ->pluck('user_id');

            $recipientRows = [];
            foreach ($adminUsers as $adminUserId) {
                $recipientRows[] = [
                    'notif_id'           => $notifId,
                    'recipient_user_id'  => $adminUserId,
                    'read_at'            => null,
                    'deleted_at'         => null,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            if (!empty($recipientRows)) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            // 7) Archive check using the service
            $reference = $issued->reference_no;
            if ($reference) {
                FormArchiveService::tryArchiveByReference($reference);
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Item returned successfully.',
                'reference_no' => $reference,
                'usage_hours'  => $hoursUsed,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}