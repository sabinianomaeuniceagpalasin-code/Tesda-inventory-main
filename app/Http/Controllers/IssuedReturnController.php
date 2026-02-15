<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IssuedLog;
use App\Models\Item;
use App\Models\FormRecord;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IssuedReturnController extends Controller
{
    public function returnItem($id)
    {
        DB::beginTransaction();

        try {
            // 1️⃣ Get issued record
            $issued = IssuedLog::findOrFail($id);

            // Mark return datetime
            $issued->actual_return_date = now();
            $issued->save();

            // 2️⃣ Update Item status
            $item = Item::where('serial_no', $issued->serial_no)->first();
            if ($item) {
                $item->status = "Available";
                $item->save();
            }

            // 3️⃣ Add Notification
            Notification::create([
                'item_id' => $item->item_id ?? null,
                'title'   => 'Item Returned',
                'message' => 'Serial No. ' . $issued->serial_no . ' has been returned.',
                'type'    => 'inventory',
            ]);

            // 4️⃣ Archive form if all items are completed (returned or actioned)
            $reference = $issued->reference_no;

            $totalItems = IssuedLog::where('reference_no', $reference)->count();

            $completedItems = DB::table('issuedlog as i')
                ->join('items as it', 'i.serial_no', '=', 'it.serial_no')
                ->where('i.reference_no', $reference)
                ->where(function ($q) {
                    $q->whereNotNull('i.actual_return_date')
                      ->orWhereIn('it.status', ['Unserviceable', 'Damaged', 'Lost']);
                })
                ->count();

            if ($totalItems > 0 && $totalItems == $completedItems) {
                FormRecord::where('reference_no', $reference)
                    ->update(['status' => 'Archived', 'updated_at' => now()]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item returned successfully.',
                'reference_no' => $reference
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
