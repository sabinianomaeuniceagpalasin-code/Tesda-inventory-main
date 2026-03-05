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
            // ✅ If batch_id is varchar in DB, cast to unsigned for numeric max
            $lastBatch = DB::table('item_approval_requests')
                ->lockForUpdate()
                ->selectRaw("MAX(CAST(batch_id AS UNSIGNED)) as max_batch")
                ->value('max_batch');

            $batchId = ((int)($lastBatch ?? 0)) + 1;

            // ✅ GROUP ITEMS (name + type + department + description)
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

                // ✅ INSERT request row and capture ID
                $itemRequestId = DB::table('item_approval_requests')->insertGetId([
                    'batch_id'      => (string)$batchId,
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

                // ✅ Notification using correct request_id
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
                'batch_id' => $batchId,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit approval request',
                // ✅ include real error so you can see it in Swal
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}