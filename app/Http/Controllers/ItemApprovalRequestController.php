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
            'items.*.name'   => 'required|string|max:150',
            'items.*.type'   => 'required|in:qr,barcode',
            'items.*.serial' => 'required|string|max:100',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 GROUP ITEMS (name + type)
            $groups = [];

            foreach ($request->items as $item) {
                $key = $item['name'] . '|' . $item['type'];

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'item_name'     => $item['name'],
                        'request_type'  => $item['type'],
                        'serial_start'  => $item['serial'], // first serial
                        'quantity'      => 0,
                    ];
                }

                $groups[$key]['quantity']++;
            }

            // 🔹 INSERT ONE ROW PER GROUP
            foreach ($groups as $group) {
                DB::table('item_approval_requests')->insert([
                    'item_name'     => $group['item_name'],
                    'serial_number' => $group['serial_start'],
                    'quantity'      => $group['quantity'],
                    'request_type'  => $group['request_type'],
                    'status'        => 'pending',
                    'requested_at'  => now(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
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
                'message' => 'Failed to submit approval request'
            ], 500);
        }
    }
}
