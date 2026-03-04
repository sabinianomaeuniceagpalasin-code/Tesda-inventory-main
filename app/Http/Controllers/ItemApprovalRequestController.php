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
            'items.*.name'       => 'required|string|max:150',
            'items.*.type'       => 'required|in:qr,barcode',
            'items.*.serial'     => 'required|string|max:100',
            'items.*.department' => 'required|in:ICS,ENGINEERING',
            'items.*.description' => 'required|string|max:255',
            // optional if you want to validate description too:
            // 'items.*.description' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Generate ONE batch_id for this send action
            // lockForUpdate prevents two users from getting the same next batch_id
            $lastBatch = DB::table('item_approval_requests')
                ->lockForUpdate()
                ->max('batch_id');

            $batchId = ($lastBatch ?? 0) + 1;

            // ===============================
            // GROUP ITEMS (name + type + department)
            // ===============================
            $groups = [];

                foreach ($request->items as $item) {
                $desc = $item['description'] ?? '';
                $key = $item['name'].'|'.$item['type'].'|'.$item['department'].'|'.$desc;

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


            // ===============================
            // INSERT ONE ROW PER GROUP (same batch_id)
            // ===============================
            foreach ($groups as $group) {
                DB::table('item_approval_requests')->insert([
                    'batch_id'      => $batchId,
                    'item_name'     => $group['item_name'],
                    'department'    => $group['department'],
                    'description'   => $group['description'],
                    'serial_number' => implode(', ', $group['serials']),
                    'quantity'      => count($group['serials']),
                    'request_type'  => $group['request_type'],
                    'status'        => 'pending',
                    'requested_at'  => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                DB::table('notifications')->insert([
                    'item_id'    => $itemRequestId,
                    'user_id'    => auth()->id(),
                    'title'      => 'Item Needs Approval',
                    'message'    => 'Batch #' . $batchId . ': ' . $group['item_name'] . ' (' . count($group['serials']) . ') request submitted.',
                    'type'       => 'approval',
                    'role'       => 'Admin',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Item approval request sent successfully',
                'batch_id' => $batchId, // ✅ helpful for UI if you want to show it
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