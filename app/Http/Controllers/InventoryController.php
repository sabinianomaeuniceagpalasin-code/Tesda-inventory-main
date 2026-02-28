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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string',
            'classification' => 'required|string',
            'source_of_fund' => 'required|string',
            'date_acquired' => 'required|date',
            'property_no' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'manual_serial' => 'nullable|string',
            'maintenance_interval_days' => 'nullable|integer|min:0',
            'maintenance_threshold_usage' => 'nullable|integer|min:0',
            'expected_life_hours' => 'nullable|integer|min:0',
        ]);

        $quantity = $validated['quantity'];

        DB::transaction(function () use ($validated, $quantity) {
            if (!empty($validated['manual_serial']) && $quantity == 1) {
                if (DB::table('items')->where('serial_no', $validated['manual_serial'])->exists()) {
                    throw new \Exception("Serial number already exists.");
                }
                $this->insertItemRecord($validated, $validated['manual_serial']);
            } else {
                $lastNumber = DB::table('items')
                    ->where('property_no', $validated['property_no'])
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
                ->where('property_no', $validated['property_no'])
                ->first();

            if ($existingInventory) {
                DB::table('propertyinventory')
                    ->where('property_no', $validated['property_no'])
                    ->update([
                        'quantity' => DB::raw("quantity + $quantity"),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('propertyinventory')->insert([
                    'property_no' => $validated['property_no'],
                    'item_name' => $validated['item_name'],
                    'quantity' => $quantity,
                    'unit_cost' => $validated['unit_cost'],
                    'sources_of_fund' => $validated['source_of_fund'],
                    'classification' => $validated['classification'],
                    'date_acquired' => $validated['date_acquired'],
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', "✅ Added successfully!");
    }

    private function insertItemRecord($validated, $serial_no)
    {
        DB::table('items')->insert([
            'item_name' => $validated['item_name'] ?? 'New Item',
            'classification' => $validated['classification'] ?? 'Unclassified',
            'source_of_fund' => $validated['source_of_fund'] ?? 'N/A',
            'date_acquired' => $validated['date_acquired'] ?? now(),
            'property_no' => $validated['property_no'] ?? ('AUTO-' . time()),
            'serial_no' => $serial_no,
            'stock' => 1,
            'status' => 'Available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function scanItem($input_data)
{
    try {
        // 1) Extract serial only
        $serial_no = $input_data;

        // QR format could be "Item Name|SERIAL" — ignore name, only take serial
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

        // 2) Find approval request that contains this serial (supports exact / CSV / contains)
        $approval = DB::table('item_approval_requests')
            ->where(function ($q) use ($serial_no_nospace) {
                $q->whereRaw("REPLACE(UPPER(serial_number),' ','') = ?", [$serial_no_nospace])
                  ->orWhereRaw("FIND_IN_SET(?, REPLACE(UPPER(serial_number),' ','')) > 0", [$serial_no_nospace])
                  ->orWhereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%'.$serial_no_nospace.'%']);
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

        // ✅ 3) Always use item name from approval request
        $itemName = $approval->item_name;

        // 4) Continue logic
        $item = DB::table('items')->where('serial_no', $serial_no)->first();

        if (!$item) {
            $tempPropNo = 'AUTO-' . strtoupper(substr(md5(time()), 0, 6));
            $lastId = DB::table('items')->max('item_id') ?? 0;
            $newId = $lastId + 1;

            $newItemData = [
                'item_id' => $newId,
                'item_name' => $itemName,
                'classification' => 'Equipment',
                'source_of_fund' => 'Scanned Entry',
                'date_acquired' => now(),
                'property_no' => $tempPropNo,
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

            // ✅ Insert/Update propertyinventory too
            DB::table('propertyinventory')->updateOrInsert(
                ['property_no' => $tempPropNo],
                [
                    'item_name' => $itemName,
                    'quantity' => DB::raw('COALESCE(quantity,0) + 1'),
                    'unit_cost' => 0,
                    'sources_of_fund' => 'Scanned Entry',
                    'classification' => 'Equipment',
                    'date_acquired' => now(),
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $item = DB::table('items')->where('serial_no', $serial_no)->first();
        } else {
            // If it exists, ensure item_name is correct (optional but good)
            if ($item->item_name !== $itemName) {
                DB::table('items')->where('serial_no', $serial_no)->update([
                    'item_name' => $itemName,
                    'updated_at' => now()
                ]);
                $item->item_name = $itemName;
            }

            if ($item->status !== 'Available') {
                DB::table('items')->where('serial_no', $serial_no)->update([
                    'status' => 'Available',
                    'updated_at' => now()
                ]);

                // ✅ Ensure propertyinventory row exists + increment
                DB::table('propertyinventory')->updateOrInsert(
                    ['property_no' => $item->property_no],
                    [
                        'item_name' => $itemName,
                        'quantity' => DB::raw('COALESCE(quantity,0) + 1'),
                        'unit_cost' => 0,
                        'sources_of_fund' => $item->source_of_fund ?? 'Scanned Entry',
                        'classification' => $item->classification ?? 'Equipment',
                        'date_acquired' => $item->date_acquired ?? now(),
                        'status' => 'Available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
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

    // extract serial
    $serial_no = $input_data;
    if (str_contains($input_data, '|')) {
        $parts = explode('|', $input_data, 2);
        $serial_no = trim($parts[1] ?? '');
    }

    $serial_no = strtoupper(trim($serial_no));
    $serial_no_nospace = str_replace(' ', '', $serial_no);

    if (!$serial_no) {
        return response()->json(['success' => false, 'message' => 'Invalid scan.'], 422);
    }

    // check approved
    $approval = DB::table('item_approval_requests')
        ->where(function ($q) use ($serial_no_nospace) {
            $q->whereRaw("REPLACE(UPPER(serial_number),' ','') = ?", [$serial_no_nospace])
              ->orWhereRaw("FIND_IN_SET(?, REPLACE(UPPER(serial_number),' ','')) > 0", [$serial_no_nospace])
              ->orWhereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%'.$serial_no_nospace.'%']);
        })
        ->orderByDesc('request_id')
        ->first();

    if (!$approval) {
        return response()->json(['success' => false, 'message' => "Serial {$serial_no} is not in approval requests."], 403);
    }

    if (strtolower(trim($approval->status)) !== 'approved') {
        return response()->json(['success' => false, 'message' => "Serial {$serial_no} is not approved yet."], 403);
    }

    // return preview info (NO INSERT)
    $existing = DB::table('items')->where('serial_no', $serial_no)->first();

    return response()->json([
        'success' => true,
        'item' => [
            'item_name' => $approval->item_name,
            'serial_no' => $serial_no,
            'property_no' => $existing->property_no ?? null,
            'exists' => (bool) $existing,
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

                    // 🔍 approval check
                    $approval = DB::table('item_approval_requests')
                        ->whereRaw("REPLACE(UPPER(serial_number),' ','') LIKE ?", ['%' . str_replace(' ', '', $serial_no) . '%'])
                        ->orderByDesc('request_id')
                        ->first();

                    if (!$approval || strtolower($approval->status) !== 'approved') {
                        continue;
                    }

                    $itemName = $approval->item_name;

                    // 🔍 LOOKUP DATA (THIS IS THE KEY FIX)
                    $lookup = DB::table('items_lookup')
                        ->whereRaw('LOWER(item_name) = ?', [strtolower($itemName)])
                        ->first();

                    if (!$lookup) {
                        continue; // no lookup = do not insert
                    }

                    $propertyNo = $lookup->property_no;
                    $unitCost   = $lookup->unit_cost ?? 0;
                    $sof        = $lookup->source_of_fund ?? 'N/A';
                    $class      = $lookup->classification ?? 'N/A';

                    // ❌ prevent duplicate serials
                    if (DB::table('items')->where('serial_no', $serial_no)->exists()) {
                        continue;
                    }

                    // 🧾 INSERT ITEM (ONE ROW PER SERIAL)
                    DB::table('items')->insert([
                        'item_name' => $itemName,
                        'classification' => $class,
                        'source_of_fund' => $sof,
                        'date_acquired' => now(),
                        'property_no' => $propertyNo,   // ✅ REAL PROPERTY NO
                        'serial_no' => $serial_no,
                        'stock' => 1,
                        'status' => 'Available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 🔢 COUNT ITEMS FOR THIS PROPERTY
                    $count = DB::table('items')
                        ->where('property_no', $propertyNo)
                        ->count();

                    // 📦 UPDATE PROPERTY INVENTORY (ONE ROW ONLY)
                    DB::table('propertyinventory')->updateOrInsert(
                        ['property_no' => $propertyNo],
                        [
                            'item_name' => $itemName,
                            'quantity' => $count,         // ✅ COUNT OF SERIALS
                            'unit_cost' => $unitCost,     // ✅ FROM LOOKUP
                            'sources_of_fund' => $sof,
                            'classification' => $class,
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