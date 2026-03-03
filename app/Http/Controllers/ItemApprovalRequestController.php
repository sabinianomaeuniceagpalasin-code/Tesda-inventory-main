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
            'items.*.description' => 'required|string|max:255', // ✅ NEW
            'items.*.type'        => 'required|in:qr,barcode',
            'items.*.serial'      => 'required|string|max:100',
            'items.*.department'  => 'required|in:ICS,ENGINEERING',
        ]);

        DB::beginTransaction();

        try {
            // ✅ GROUP by name + description + type + department
            $groups = [];

            foreach ($request->items as $item) {
                $key = $item['name'] . '|' . $item['description'] . '|' . $item['type'] . '|' . $item['department'];

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'item_name'    => $item['name'],
                        'description'  => $item['description'], // ✅ NEW
                        'request_type' => $item['type'],
                        'Department'   => $item['department'],
                        'serials'      => [],
                    ];
                }

                $groups[$key]['serials'][] = $item['serial'];
            }

            // ✅ INSERT one row per group
            foreach ($groups as $group) {
                $itemRequestId = DB::table('item_approval_requests')->insertGetId([
                    'item_name'     => $group['item_name'],
                    'description'   => $group['description'], // ✅ NEW
                    'serial_number' => implode(', ', $group['serials']),
                    'quantity'      => count($group['serials']),
                    'request_type'  => $group['request_type'],
                    'Department'    => $group['Department'],
                    'status'        => 'pending',
                    'requested_at'  => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                DB::table('notifications')->insert([
                    'item_id'    => $itemRequestId,
                    'user_id'    => auth()->id(),
                    'title'      => 'Item Needs Approval',
                    'message'    => $group['item_name'] . ' - ' . $group['description'] . ' (' . count($group['serials']) . ') request submitted.',
                    'type'       => 'approval',
                    'role'       => 'Admin',
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item approval request sent successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit approval request',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}