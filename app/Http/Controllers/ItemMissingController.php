<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ItemMissingController extends Controller
{
    public function markMissing(Request $request)
    {
        $request->validate([
            'serial_no' => 'required|string',
            'borrower_name' => 'required|string',
        ]);

        try {
            $serial = trim($request->serial_no);
            $borrower = trim($request->borrower_name);

            $item = DB::table('items')
                ->where('serial_no', $serial)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found.'
                ], 404);
            }

            $existingMissing = DB::table('missing')
                ->where('serial_number', $serial)
                ->exists();

            if ($existingMissing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is already recorded as missing.'
                ], 409);
            }

            DB::beginTransaction();

            DB::table('missing')->insert([
                'item_name'     => (string) $item->item_name,
                'serial_number' => (string) $serial,
                'borrower_name' => (string) $borrower,
                'reported_by'  => Auth::check() ? (Auth::user()->name ?? 'System') : 'System',
                'reported_at'  => Carbon::today()->toDateString(),
            ]);

            DB::table('items')
                ->where('serial_no', $serial)
                ->update([
                    'status' => 'Missing',
                    'updated_at' => Carbon::now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as missing successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Mark Missing Error', [
                'message' => $e->getMessage(),
                'serial_no' => $request->serial_no,
                'borrower_name' => $request->borrower_name,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while marking item as missing.',
                'error' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
            ], 500);
        }
    }
}