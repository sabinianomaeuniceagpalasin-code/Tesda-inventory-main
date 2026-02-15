<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // ✅ Inventory index
    public function index()
    {
        $inventory = DB::table('propertyinventory')
            ->select('property_no', 'item_name', 'quantity', 'unit_cost')
            ->get();

        $totalTools     = DB::table('items')->count();
        $availableItems = DB::table('items')->where('status', 'Available')->count();
        $issuedItems    = DB::table('items')->where('status', 'Borrowed')->count();
        $forRepair      = DB::table('items')->whereIn('status', ['For Repair', 'Damaged'])->count();

        return view('dashboard', compact('inventory', 'totalTools', 'availableItems', 'issuedItems', 'forRepair'));
    }

    // ✅ Check if property number exists and return its details
    public function checkPropertyNo($property_no)
    {
        // Get the first item info from items table
        $tool = DB::table('items')
            ->select('item_name', 'classification', 'source_of_fund')
            ->where('property_no', $property_no)
            ->first();

        // Get unit_cost from propertyinventory table
        $inventory = DB::table('propertyinventory')
            ->select('unit_cost')
            ->where('property_no', $property_no)
            ->first();

        if ($tool) {
            return response()->json([
                'exists' => true,
                'data' => [
                    'item_name'      => $tool->item_name,
                    'classification' => $tool->classification,
                    'source_of_fund' => $tool->source_of_fund,
                    'unit_cost'      => $inventory->unit_cost ?? 0
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }

    // ✅ Store new item
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'        => 'required|string',
            'classification'   => 'required|string',
            'source_of_fund'   => 'required|string',
            'date_acquired'    => 'required|date',
            'property_no'      => 'required|string',
            'quantity'         => 'required|integer|min:1',
            'unit_cost'        => 'required|numeric|min:0',
            'remarks'          => 'nullable|string',
            'maintenance_interval_days'   => 'nullable|integer|min:0',
            'maintenance_threshold_usage' => 'nullable|integer|min:0',
            'expected_life_hours'         => 'nullable|integer|min:0',
        ]);

        $quantity = $validated['quantity'];

        DB::transaction(function () use ($validated, $quantity) {

            // -------------------------
            // 1️⃣ Insert new rows into items table
            // -------------------------
            $lastNumber = DB::table('items')
                ->where('property_no', $validated['property_no'])
                ->where('serial_no', 'like', 'SN%')
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(serial_no, 3) AS UNSIGNED)'));

            $lastNumber = $lastNumber ?: 0;

            for ($i = 1; $i <= $quantity; $i++) {
                do {
                    $lastNumber++;
                    $serial_no = 'SN' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
                    $exists = DB::table('items')->where('serial_no', $serial_no)->exists();
                } while ($exists);

                DB::table('items')->insert([
                    'item_name'                    => $validated['item_name'],
                    'classification'               => $validated['classification'],
                    'source_of_fund'               => $validated['source_of_fund'],
                    'date_acquired'                => $validated['date_acquired'],
                    'property_no'                  => $validated['property_no'],
                    'serial_no'                    => $serial_no,
                    'stock'                         => 1,
                    'remarks'                       => $validated['remarks'] ?? null,
                    'status'                        => 'Available',
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                    'last_maintenance_date'         => null,
                    'maintenance_interval_days'     => $validated['maintenance_interval_days'] ?? null,
                    'maintenance_threshold_usage'   => $validated['maintenance_threshold_usage'] ?? null,
                    'expected_life_hours'           => $validated['expected_life_hours'] ?? null,
                    'total_usage_hours'             => 0,
                ]);
            }

            // -------------------------
            // 2️⃣ Update or insert into propertyinventory
            // -------------------------
            $existingInventory = DB::table('propertyinventory')
                ->where('property_no', $validated['property_no'])
                ->first();

            if ($existingInventory) {
                // Increment quantity only
                DB::table('propertyinventory')
                    ->where('property_no', $validated['property_no'])
                    ->update([
                        'quantity'   => DB::raw("quantity + $quantity"),
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new property
                DB::table('propertyinventory')->insert([
                    'property_no'     => $validated['property_no'],
                    'item_name'       => $validated['item_name'],
                    'quantity'        => $quantity,
                    'unit_cost'       => $validated['unit_cost'],
                    'sources_of_fund' => $validated['source_of_fund'],
                    'classification'  => $validated['classification'],
                    'date_acquired'   => $validated['date_acquired'],
                    'status'          => 'Available',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', "✅ Added {$quantity} item(s) successfully!");
    }
}
