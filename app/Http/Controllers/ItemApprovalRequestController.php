<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemApprovalRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name'        => 'required|string|max:150',
            'items.*.type'        => 'required|in:qr,barcode',
            'items.*.serial'      => 'required|string|max:100',
            'items.*.department'  => 'required|in:ICS,ENGINEERING',
            'items.*.description' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $loggedInUser = auth()->user();
            $loggedInUserId = $loggedInUser->user_id;
            $requesterName = trim(($loggedInUser->first_name ?? '') . ' ' . ($loggedInUser->last_name ?? ''));

            $lastBatch = DB::table('item_approval_requests')
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(batch_id AS UNSIGNED)) as max_batch")
                ->value('max_batch');

            $batchId = ((int) ($lastBatch ?? 0)) + 1;

            $groups = [];

            foreach ($request->items as $item) {
                $desc = $item['description'] ?? '';
                $key = $item['name'] . '|' . $item['type'] . '|' . $item['department'] . '|' . $desc;

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'item_name'    => $item['name'],
                        'request_type' => $item['type'],
                        'department'   => $item['department'],
                        'description'  => $desc,
                        'serials'      => [],
                    ];
                }

                $groups[$key]['serials'][] = $item['serial'];
            }

            foreach ($groups as $group) {
                $itemRequestId = DB::table('item_approval_requests')->insertGetId([
                    'batch_id'             => (string) $batchId,
                    'item_name'            => $group['item_name'],
                    'department'           => $group['department'],
                    'description'          => $group['description'],
                    'serial_number'        => implode(', ', $group['serials']),
                    'quantity'             => count($group['serials']),
                    'request_type'         => $group['request_type'],
                    'status'               => 'pending',
                    'requested_by_user_id' => $loggedInUserId,
                    'requested_at'         => now(),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                // 1) create main notification
                $notifId = DB::table('notifications')->insertGetId([
                    'type'               => 'approval_request',
                    'title'              => 'New QR Approval Request',
                    'message'            => $requesterName . ' submitted Batch #' . $batchId . ' for ' . $group['item_name'] . ' (' . count($group['serials']) . ' item/s).',
                    'severity'           => 'info',
                    'entity_type'        => 'item_approval_request',
                    'entity_id'          => $itemRequestId,
                    'action_url'         => route('inventory.settings', ['tab' => 'approval-requests']),
                    'created_by_user_id' => $loggedInUserId,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                // 2) get ADMIN users only
                $adminIds = DB::table('users')
                    ->where('role', 'Admin')
                    ->pluck('user_id');

                // 3) assign only admins as recipients
                foreach ($adminIds as $adminUserId) {
                    DB::table('notification_recipients')->insert([
                        'notif_id'           => $notifId,
                        'recipient_user_id'  => $adminUserId,
                        'read_at'            => null,
                        'deleted_at'         => null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Item approval request sent successfully',
                'batch_id' => $batchId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit approval request',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}