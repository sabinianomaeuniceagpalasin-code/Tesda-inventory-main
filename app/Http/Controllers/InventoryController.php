<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = DB::table('propertyinventory')
            ->select('property_no', 'item_name', 'quantity', 'unit_cost')
            ->get();

        $totalTools = DB::table('items')->count();
        $availableItems = DB::table('items')->where('status', 'Available')->count();
        $issuedItems = DB::table('items')->where('status', 'Borrowed')->count();
        $forRepair = DB::table('items')->whereIn('status', ['For Repair', 'Damaged'])->count();

        return view('dashboard', compact('inventory', 'totalTools', 'availableItems', 'issuedItems', 'forRepair'));
    }

    public function checkPropertyNo($property_no)
    {
        $tool = DB::table('items')
            ->select('item_name', 'classification', 'source_of_fund')
            ->where('property_no', $property_no)
            ->first();

        $inventory = DB::table('propertyinventory')
            ->select('unit_cost')
            ->where('property_no', $property_no)
            ->first();

        if ($tool) {
            return response()->json([
                'exists' => true,
                'data' => [
                    'item_name' => $tool->item_name,
                    'classification' => $tool->classification,
                    'source_of_fund' => $tool->source_of_fund,
                    'unit_cost' => $inventory->unit_cost ?? 0
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    private function getOrCreatePropertyNoByItemName(string $itemName): string
    {
        $normalizedName = trim(mb_strtolower($itemName));

        $existing = DB::table('items')
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalizedName])
            ->whereNotNull('property_no')
            ->orderBy('item_id')
            ->value('property_no');

        if ($existing !== null && $existing !== '') {
            return (string) $existing;
        }

        $maxPropertyNo = DB::table('items')
            ->whereNotNull('property_no')
            ->whereRaw("property_no REGEXP '^[0-9]+$'")
            ->lockForUpdate()
            ->max(DB::raw('CAST(property_no AS UNSIGNED)'));

        return (string) (($maxPropertyNo ?? 0) + 1);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string',
            'classification' => 'nullable|string',
            'source_of_fund' => 'nullable|string',
            'date_acquired' => 'required|date',
            'property_no' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'manual_serial' => 'nullable|string',
            'maintenance_interval_days' => 'nullable|integer|min:0',
            'maintenance_threshold_usage' => 'nullable|integer|min:0',
            'expected_life_hours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $quantity = $validated['quantity'];

        DB::transaction(function () use ($validated, $quantity) {
            $propertyNo = $this->getOrCreatePropertyNoByItemName($validated['item_name']);

            $validated['property_no'] = $propertyNo;

            if (!empty($validated['manual_serial']) && $quantity == 1) {
                if (DB::table('items')->where('serial_no', $validated['manual_serial'])->exists()) {
                    throw new \Exception("Serial number already exists.");
                }
                $this->insertItemRecord($validated, $validated['manual_serial']);
            } else {
                $lastNumber = DB::table('items')
                    ->where('serial_no', 'like', 'SN%')
                    ->lockForUpdate()
                    ->max(DB::raw('CAST(SUBSTRING(serial_no, 3) AS UNSIGNED)')) ?? 0;

                for ($i = 1; $i <= $quantity; $i++) {
                    do {
                        $lastNumber++;
                        $serial_no = 'SN' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
                        $exists = DB::table('items')->where('serial_no', $serial_no)->exists();
                    } while ($exists);

                    $this->insertItemRecord($validated, $serial_no);
                }
            }

            $existingInventory = DB::table('propertyinventory')
                ->where('property_no', $propertyNo)
                ->first();

            if ($existingInventory) {
                DB::table('propertyinventory')
                    ->where('property_no', $propertyNo)
                    ->update([
                        'quantity' => DB::raw("quantity + $quantity"),
                        'item_name' => $validated['item_name'],
                        'unit_cost' => $validated['unit_cost'] ?? $existingInventory->unit_cost,
                        'sources_of_fund' => $validated['source_of_fund'] ?? DB::raw('sources_of_fund'),
                        'classification' => $validated['classification'] ?? DB::raw('classification'),
                        'date_acquired' => $validated['date_acquired'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('propertyinventory')->insert([
                    'property_no' => $propertyNo,
                    'item_name' => $validated['item_name'],
                    'quantity' => $quantity,
                    'unit_cost' => $validated['unit_cost'] ?? null,
                    'sources_of_fund' => $validated['source_of_fund'] ?? null,
                    'classification' => $validated['classification'] ?? null,
                    'date_acquired' => $validated['date_acquired'],
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', '✅ Added successfully!');
    }

    private function insertItemRecord($validated, $serial_no)
    {
        DB::table('items')->insert([
            'item_name' => $validated['item_name'] ?? 'New Item',
            'description' => $validated['description'] ?? null,
            'classification' => $validated['classification'] ?? null,
            'source_of_fund' => $validated['source_of_fund'] ?? null,
            'date_acquired' => $validated['date_acquired'] ?? now(),
            'property_no' => $validated['property_no'],
            'serial_no' => $serial_no,
            'stock' => 1,
            'status' => 'Available',
            'remarks' => $validated['remarks'] ?? null,
            'maintenance_interval_days' => $validated['maintenance_interval_days'] ?? null,
            'maintenance_threshold_usage' => $validated['maintenance_threshold_usage'] ?? null,
            'expected_life_hours' => $validated['expected_life_hours'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function update(Request $request, $serial_no)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'classification' => 'required|string|max:255',
            'source_of_fund' => 'required|string|max:255',
            'date_acquired' => 'required|date',
            'status' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($request, $serial_no) {
            $item = DB::table('items')->where('serial_no', $serial_no)->first();

            if (!$item) {
                throw new \Exception("Item with serial {$serial_no} not found.");
            }

            $newPropertyNo = $this->getOrCreatePropertyNoByItemName($request->item_name);

            DB::table('items')
                ->where('serial_no', $serial_no)
                ->update([
                    'property_no' => $newPropertyNo,
                    'classification' => $request->classification,
                    'item_name' => $request->item_name,
                    'source_of_fund' => $request->source_of_fund,
                    'date_acquired' => $request->date_acquired,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            $countForProperty = DB::table('items')
                ->where('property_no', $newPropertyNo)
                ->count();

            DB::table('propertyinventory')->updateOrInsert(
                ['property_no' => $newPropertyNo],
                [
                    'item_name' => $request->item_name,
                    'quantity' => $countForProperty,
                    'sources_of_fund' => $request->source_of_fund,
                    'classification' => $request->classification,
                    'date_acquired' => $request->date_acquired,
                    'status' => $request->status,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });

        return redirect()->back()->with('success', 'Item updated successfully.');
    }

    public function destroy($serial_no)
    {
        DB::transaction(function () use ($serial_no) {
            $item = DB::table('items')->where('serial_no', $serial_no)->first();

            if (!$item) {
                throw new \Exception("Item with serial {$serial_no} not found.");
            }

            $property_no = $item->property_no;

            DB::table('items')
                ->where('serial_no', $serial_no)
                ->delete();

            $remaining = DB::table('items')
                ->where('property_no', $property_no)
                ->count();

            if ($remaining <= 0) {
                DB::table('propertyinventory')
                    ->where('property_no', $property_no)
                    ->delete();
            } else {
                DB::table('propertyinventory')
                    ->where('property_no', $property_no)
                    ->update([
                        'quantity' => $remaining,
                        'updated_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Item with serial {$serial_no} deleted successfully."
        ]);
    }

    public function scanItem($input_data)
    {
        try {
            $serial_no = $input_data;

            if (str_contains($input_data, '|')) {
                $parts = explode('|', $input_data, 2);
                $serial_no = trim($parts[1] ?? '');
            }

            $serial_no = strtoupper(trim($serial_no));
            $serial_no_nospace = str_replace(' ', '', $serial_no);

            if (!$serial_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid scan. No serial number detected.'
                ], 422);
            }

            $approval = DB::table('item_approval_requests')
                ->where(function ($q) use ($serial_no_nospace) {
                    $q->whereRaw("REPLACE(UPPER(serial_number),' ','') = ?", [$serial_no_nospace])
                        ->orWhereRaw("FIND_IN_SET(?, REPLACE(UPPER(serial_number),' ','')) > 0", [$serial_no_nospace])
                        ->orWhereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%' . $serial_no_nospace . '%']);
                })
                ->orderByDesc('request_id')
                ->first();

            if (!$approval) {
                return response()->json([
                    'success' => false,
                    'message' => "Serial {$serial_no} is not in approval requests. Please request approval first."
                ], 403);
            }

            $approvalStatus = strtolower(trim((string) $approval->status));
            if ($approvalStatus !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => "Serial {$serial_no} is not approved yet. Current status: {$approval->status}"
                ], 403);
            }

            $itemName = $approval->item_name;

            $item = DB::table('items')->where('serial_no', $serial_no)->first();

            if (!$item) {
                $propertyNo = $this->getOrCreatePropertyNoByItemName($itemName);

                $newItemData = [
                    'item_name' => $itemName,
                    'description' => $approval->description ?? null,
                    'classification' => $approval->classification ?? null,
                    'source_of_fund' => $approval->source_of_fund ?? null,
                    'date_acquired' => now(),
                    'property_no' => $propertyNo,
                    'serial_no' => $serial_no,
                    'stock' => 1,
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if (Schema::hasColumn('items', 'total_usage_hours')) {
                    $newItemData['total_usage_hours'] = 0;
                }

                DB::table('items')->insert($newItemData);

                $count = DB::table('items')->where('property_no', $propertyNo)->count();

                DB::table('propertyinventory')->updateOrInsert(
                    ['property_no' => $propertyNo],
                    [
                        'item_name' => $itemName,
                        'quantity' => $count,
                        'unit_cost' => $approval->unit_cost ?? null,
                        'sources_of_fund' => $approval->source_of_fund ?? null,
                        'classification' => $approval->classification ?? null,
                        'date_acquired' => now(),
                        'status' => 'Available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $item = DB::table('items')->where('serial_no', $serial_no)->first();
            }

            return response()->json([
                'success' => true,
                'item' => [
                    'item_name' => $item->item_name,
                    'serial_no' => $item->serial_no,
                    'property_no' => $item->property_no
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function validateScan(Request $request)
    {
        $request->validate(['input' => 'required|string']);

        $input_data = $request->input('input');

        $serial_no = $input_data;
        if (str_contains($input_data, '|')) {
            $parts = explode('|', $input_data, 2);
            $serial_no = trim($parts[1] ?? '');
        }

        $serial_no = strtoupper(trim($serial_no));
        $serial_no_nospace = str_replace(' ', '', $serial_no);

        if (!$serial_no) {
            return response()->json([
                'success' => false,
                'code' => 'invalid',
                'message' => 'Invalid scan. No serial detected.'
            ], 422);
        }

        $approval = DB::table('item_approval_requests')
            ->where(function ($q) use ($serial_no_nospace) {
                $q->whereRaw("REPLACE(UPPER(serial_number),' ','') = ?", [$serial_no_nospace])
                    ->orWhereRaw("FIND_IN_SET(?, REPLACE(UPPER(serial_number),' ','')) > 0", [$serial_no_nospace])
                    ->orWhereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%' . $serial_no_nospace . '%']);
            })
            ->orderByDesc('request_id')
            ->first();

        if (!$approval) {
            return response()->json([
                'success' => false,
                'code' => 'no_request',
                'message' => "Serial {$serial_no} is not in approval requests."
            ], 403);
        }

        $status = strtolower(trim((string) $approval->status));

        if ($status === 'rejected') {
            return response()->json([
                'success' => false,
                'code' => 'rejected',
                'message' => "Serial {$serial_no} was REJECTED. You cannot receive this item."
            ], 403);
        }

        if ($status !== 'approved') {
            return response()->json([
                'success' => false,
                'code' => 'not_approved',
                'message' => "Serial {$serial_no} is not approved yet. Current status: {$approval->status}"
            ], 403);
        }

        $existing = DB::table('items')->where('serial_no', $serial_no)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'code' => 'already_exists',
                'message' => "Serial {$serial_no} already exists in ITEMS (already received)."
            ], 409);
        }

        $propertyNo = $this->getOrCreatePropertyNoByItemName($approval->item_name);

        return response()->json([
            'success' => true,
            'item' => [
                'item_name' => $approval->item_name,
                'serial_no' => $serial_no,
                'property_no' => $propertyNo,
            ]
        ]);
    }

    public function receiveBatch(Request $request)
    {
        $request->validate([
            'serials' => 'required|array|min:1',
            'serials.*' => 'required|string'
        ]);

        $serials = array_values(array_unique(array_map(fn($s) => strtoupper(trim($s)), $request->serials)));

        DB::transaction(function () use ($serials) {
            foreach ($serials as $serial_no) {
                $approval = DB::table('item_approval_requests')
                    ->whereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%' . str_replace(' ', '', $serial_no) . '%'])
                    ->orderByDesc('request_id')
                    ->first();

                if (!$approval || strtolower($approval->status) !== 'approved') {
                    continue;
                }

                if (DB::table('items')->where('serial_no', $serial_no)->exists()) {
                    continue;
                }

                $itemName = $approval->item_name;
                $description = $approval->description ?? null;
                $classification = $approval->classification ?? null;
                $sourceOfFund = $approval->source_of_fund ?? null;
                $propertyNo = $this->getOrCreatePropertyNoByItemName($itemName);

                DB::table('items')->insert([
                    'item_name' => $itemName,
                    'description' => $description,
                    'classification' => $classification,
                    'source_of_fund' => $sourceOfFund,
                    'date_acquired' => now(),
                    'property_no' => $propertyNo,
                    'serial_no' => $serial_no,
                    'stock' => 1,
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count = DB::table('items')
                    ->where('property_no', $propertyNo)
                    ->count();

                DB::table('propertyinventory')->updateOrInsert(
                    ['property_no' => $propertyNo],
                    [
                        'item_name' => $itemName,
                        'quantity' => $count,
                        'unit_cost' => $approval->unit_cost ?? null,
                        'sources_of_fund' => $sourceOfFund,
                        'classification' => $classification,
                        'date_acquired' => now(),
                        'status' => 'Available',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return response()->json(['success' => true]);
    }
}