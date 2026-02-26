<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormRecordsItemScanController extends Controller
{
    public function scan(Request $request)
    {
        $code = trim((string) $request->query('code'));
        $formType = strtoupper(trim((string) $request->query('form_type'))); // ICS/PAR

        if ($code === '') {
            return response()->json(['message' => 'Empty scan.'], 422);
        }

        if (!in_array($formType, ['ICS', 'PAR'], true)) {
            return response()->json(['message' => 'Invalid form type.'], 422);
        }

        // Find item by serial
        $item = DB::table('items')
            ->select('serial_no', 'item_name', 'status', 'property_no')
            ->where('serial_no', $code)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'This is not in the inventory yet.'], 404);
        }

        // Only scannable if Available
        if (strtolower((string) $item->status) !== 'available') {
            return response()->json([
                'message' => "Item is not scannable because status is {$item->status}."
            ], 409);
        }

        // Get unit cost from propertyinventory
        $pi = DB::table('propertyinventory')
            ->select('unit_cost')
            ->where('property_no', $item->property_no)
            ->first();

        if (!$pi) {
            return response()->json([
                'message' => 'No matching propertyinventory record found for this item.'
            ], 409);
        }

        $unitCost = (float) $pi->unit_cost;

        // Validate unit cost by form type
        if ($formType === 'ICS') {
            if ($unitCost < 15000 || $unitCost > 49000) {
                return response()->json([
                    'message' => "Invalid for ICS. Unit cost must be 15,000–49,000. This item is ₱" . number_format($unitCost, 2) . "."
                ], 422);
            }
        } else { // PAR
            if ($unitCost < 50000) {
                return response()->json([
                    'message' => "Invalid for PAR. Unit cost must be 50,000 and up. This item is ₱" . number_format($unitCost, 2) . "."
                ], 422);
            }
        }

        return response()->json([
            'serial_no' => $item->serial_no,
            'item_name' => $item->item_name ?? 'Item',
            'status' => $item->status,
            'unit_cost' => $unitCost,
            'form_type' => $formType,
        ]);
    }
}