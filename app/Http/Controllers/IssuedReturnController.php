<?php

namespace App\Http\Controllers;

use App\Models\IssuedLog;
use App\Models\Item;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\FormArchiveService;

class IssuedReturnController extends Controller
{
    public function returnItem($id)
    {
        DB::beginTransaction();

        try {
            // 1) Get issued record
            $issued = IssuedLog::findOrFail($id);

            // Mark return datetime
            $issued->actual_return_date = now();
            $issued->save();

            // 2) Update Item status to Available
            $item = Item::where('serial_no', $issued->serial_no)->first();
            if ($item) {
                $item->status = "Available";
                $item->save();
            }

            // 3) Notification (user_id is required in your table)
            Notification::create([
                'item_id' => $item->item_id ?? null,
                'user_id' => Auth::id(),
                'title'   => 'Item Returned',
                'message' => 'Serial No. ' . $issued->serial_no . ' has been returned.',
                'type'    => 'inventory',
                'role'    => 'Admin', // adjust to your system if needed
                'is_read' => 0,
            ]);

            // 4) Archive check using the service
            $reference = $issued->reference_no;
            if ($reference) {
                FormArchiveService::tryArchiveByReference($reference);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item returned successfully.',
                'reference_no' => $reference
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}