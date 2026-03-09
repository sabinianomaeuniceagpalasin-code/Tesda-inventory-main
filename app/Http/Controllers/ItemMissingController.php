<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\FormArchiveService;

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
            $userId = Auth::id();

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

            // Get latest issued log for this serial
            $issued = DB::table('issuedlog')
                ->where('serial_no', $serial)
                ->orderByDesc('issue_id')
                ->first();

            DB::beginTransaction();

            DB::table('missing')->insert([
                'item_name'     => (string) $item->item_name,
                'serial_number' => (string) $serial,
                'borrower_name' => (string) $borrower,
                'reported_by'   => Auth::check() ? (Auth::user()->name ?? 'System') : 'System',
                'reported_at'   => Carbon::now(),
            ]);

            DB::table('items')
                ->where('serial_no', $serial)
                ->update([
                    'status' => 'Missing',
                    'updated_at' => Carbon::now(),
                ]);

            // Create notification
            $notifId = DB::table('notifications')->insertGetId([
                'type' => 'inventory',
                'title' => 'Item Marked as Missing',
                'message' => "Item '{$item->item_name}' (Serial: {$serial}) was marked as missing. Borrower: {$borrower}",
                'severity' => 'warning',
                'entity_type' => 'item',
                'entity_id' => $item->item_id ?? null,
                'action_url' => '/dashboard?section=issued',
                'data' => json_encode([
                    'item_id' => $item->item_id ?? null,
                    'item_name' => $item->item_name ?? null,
                    'serial_no' => $serial,
                    'property_no' => $item->property_no ?? null,
                    'borrower_name' => $borrower,
                    'reference_no' => $issued->reference_no ?? null,
                    'reported_by_user_id' => $userId,
                    'reported_at' => Carbon::now()->toDateTimeString(),
                    'status' => 'Missing',
                ]),
                'created_by_user_id' => $userId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Notify Admin only
            $adminUsers = DB::table('users')
                ->where('role', 'Admin')
                ->pluck('user_id');

            $recipientRows = [];
            foreach ($adminUsers as $adminUserId) {
                $recipientRows[] = [
                    'notif_id' => $notifId,
                    'recipient_user_id' => $adminUserId,
                    'read_at' => null,
                    'deleted_at' => null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            if (!empty($recipientRows)) {
                DB::table('notification_recipients')->insert($recipientRows);
            }

            // Archive check like return / damage / unserviceable
            if (!empty($issued?->reference_no)) {
                FormArchiveService::tryArchiveByReference($issued->reference_no);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as missing successfully.',
                'reference_no' => $issued->reference_no ?? null,
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