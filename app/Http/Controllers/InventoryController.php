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
            $itemName = 'Auto-Added Item';
            $serial_no = $input_data;

            if (str_contains($input_data, '|')) {
                $parts = explode('|', $input_data);
                $itemName = trim($parts[0]);
                $serial_no = trim($parts[1]);
            }

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

                DB::table('propertyinventory')->insert([
                    'property_no' => $tempPropNo,
                    'item_name' => $itemName,
                    'quantity' => 1,
                    'unit_cost' => 0,
                    'sources_of_fund' => 'Scanned Entry',
                    'classification' => 'Equipment',
                    'date_acquired' => now(),
                    'status' => 'Available',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $item = DB::table('items')->where('serial_no', $serial_no)->first();
            } else {
                if ($item->status !== 'Available') {
                    DB::table('items')->where('serial_no', $serial_no)->update([
                        'status' => 'Available',
                        'updated_at' => now()
                    ]);

                    DB::table('propertyinventory')
                        ->where('property_no', $item->property_no)
                        ->increment('quantity', 1);
                }
            }

            return response()->json([
                'success' => true,
                'item' => [
                    'name' => $item->item_name,
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

    public function receiveBatch(Request $request)
    {
        return response()->json(['success' => true]);
    }
}