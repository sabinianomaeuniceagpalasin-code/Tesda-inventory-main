<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IssuedUnserviceableController extends Controller
{
    public function markUnserviceable(Request $request, $id)
    {
        // 1️⃣ Get the issued item along with item details
        $issuedItem = DB::table('issuedlog as i')
            ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
            ->where('i.issue_id', $id)
            ->select(
                'i.*',
                'it.item_id',
                'it.item_name',
                'it.serial_no',
                'it.property_no',
                'i.reference_no'
            )
            ->first();

        if (!$issuedItem) {
            return response()->json(['status' => 'error', 'message' => 'Issued item not found.']);
        }

        DB::beginTransaction();

        try {
            // 2️⃣ Update item status to 'Unserviceable'
            $updated = DB::table('items')
                ->where('serial_no', $issuedItem->serial_no)
                ->update([
                    'status' => 'Unserviceable',
                    'updated_at' => now()
                ]);

            if (!$updated) {
                throw new \Exception("Failed to update item status.");
            }

            // 3️⃣ Decrement quantity in propertyinventory
            $inventory = DB::table('propertyinventory')
                ->where('item_name', $issuedItem->item_name)
                ->where('property_no', $issuedItem->property_no)
                ->first();

            if ($inventory) {
                DB::table('propertyinventory')
                    ->where('inventory_id', $inventory->inventory_id)
                    ->decrement('quantity');
            } else {
                throw new \Exception("Property inventory not found for item '{$issuedItem->item_name}'.");
            }

            // 4️⃣ Insert notification
            DB::table('notifications')->insert([
                'item_id' => $issuedItem->item_id,
                'title' => 'Item marked as Unserviceable',
                'message' => "Item '{$issuedItem->item_name}' (Serial No: {$issuedItem->serial_no}) has been marked as unserviceable.",
                'type' => 'inventory',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 5️⃣ Archive form if all items are completed (returned or actioned)
            $reference = $issuedItem->reference_no;

            $totalItems = DB::table('issuedlog')
                ->where('reference_no', $reference)
                ->count();

            $completedItems = DB::table('issuedlog as i')
                ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
                ->where('i.reference_no', $reference)
                ->where(function($q) {
                    $q->whereNotNull('i.actual_return_date')
                      ->orWhereIn('it.status', ['Unserviceable', 'Damaged', 'Lost']);
                })
                ->count();

            if ($totalItems > 0 && $totalItems == $completedItems) {
                DB::table('formrecords')
                    ->where('reference_no', $reference)
                    ->update(['status' => 'Archived', 'updated_at' => now()]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item marked as Unserviceable successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Unserviceable Error [Issue ID: $id]: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark as Unserviceable. Check logs for details.'
            ]);
        }
    }
}
