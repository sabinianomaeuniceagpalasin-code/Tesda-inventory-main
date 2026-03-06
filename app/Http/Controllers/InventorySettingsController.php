<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Mail\AccountApproved;
use App\Mail\AccountRejected;

class InventorySettingsController extends Controller
{
    /* ================================
       PAGE LOAD
    ================================= */
    public function index()
    {
        // Pending user accounts
        $users = User::where('is_verified', 1)
            ->where('is_approved', 0)
            ->get();

        // Pending item approval requests
        $itemRequests = DB::table('item_approval_requests')
            ->select(
                'request_id',
                'batch_id',
                'item_name',
                'department',
                'description',
                'serial_number',
                'quantity',
                'request_type',
                'requested_at',
                'status'
            )
            ->where('status', 'pending')
            ->whereNotNull('batch_id')
            ->orderByDesc('requested_at')
            ->get();

        // Archive requests
        $archiveRequests = DB::table('item_approval_requests')
            ->select(
                'request_id',
                'batch_id',
                'item_name',
                'department',
                'description',
                'serial_number',
                'quantity',
                'request_type',
                'requested_at',
                'status'
            )
            ->whereIn('status', ['approved', 'rejected', 'pending'])
            ->whereNotNull('batch_id')
            ->orderByDesc('requested_at')
            ->get();

        // ===============================
        // ITEM LIFESPAN LIMITS
        // grouped by item_name + description
        // ===============================
        $lifespanItems = DB::table('items')
            ->select(
                'item_name',
                'description',
                DB::raw('COALESCE(MAX(expected_life_hours), 0) as expected_life_hours'),
                DB::raw('MIN(item_id) as item_id')
            )
            ->groupBy('item_name', 'description')
            ->orderBy('item_name', 'asc')
            ->orderBy('description', 'asc')
            ->get();

        $lifespanPreview = $lifespanItems->take(5);

        // ===============================
        // CLASSIFICATION MANAGEMENT
        // grouped by item_name + description
        // ===============================
        $classifications = DB::table('items')
            ->select(
                'item_name',
                'description',
                DB::raw('COALESCE(MAX(classification), "") as classification'),
                DB::raw('MIN(item_id) as item_id')
            )
            ->groupBy('item_name', 'description')
            ->orderBy('item_name', 'asc')
            ->orderBy('description', 'asc')
            ->get();

        $classificationsPreview = $classifications->take(5);

        return view('Inventory-settings', compact(
            'users',
            'itemRequests',
            'archiveRequests',
            'lifespanItems',
            'lifespanPreview',
            'classifications',
            'classificationsPreview'
        ));
    }

    /* ================================
       USER APPROVAL
    ================================= */
    public function approve($id)
    {
        $user = User::where('user_id', $id)->firstOrFail();

        $user->update([
            'is_approved' => 1
        ]);

        Mail::to($user->email)->send(new AccountApproved($user));

        return back()->with('success', 'User account approved.');
    }

    public function reject($id)
    {
        $user = User::where('user_id', $id)->firstOrFail();

        Mail::to($user->email)->send(new AccountRejected($user));
        $user->delete();

        return back()->with('success', 'User account rejected.');
    }

    /* ================================
       ITEM LIFESPAN LIMITS
    ================================= */
    public function updateLifespan(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string',
            'description' => 'required|string',
            'expected_life_hours' => 'required|integer|min:0',
        ]);

        DB::table('items')
            ->where('item_name', $request->item_name)
            ->where('description', $request->description)
            ->update([
                'expected_life_hours' => $request->expected_life_hours,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Lifespan updated for all matching items.');
    }

    public function deleteLifespan(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string',
            'description' => 'required|string',
        ]);

        DB::table('items')
            ->where('item_name', $request->item_name)
            ->where('description', $request->description)
            ->update([
                'expected_life_hours' => 0,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Lifespan reset for all matching items.');
    }

    /* ================================
       ITEM APPROVAL
    ================================= */
    public function approveItem($id)
    {
        DB::table('item_approval_requests')
            ->where('request_id', $id)
            ->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Item request approved.'
        ]);
    }

    public function rejectItem($id)
    {
        DB::table('item_approval_requests')
            ->where('request_id', $id)
            ->update([
                'status'      => 'rejected',
                'rejected_at' => now(),
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Item request rejected.'
        ]);
    }

    public function approveBatch($batchId)
    {
        DB::table('item_approval_requests')
            ->where('batch_id', $batchId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch approved.',
        ]);
    }

    public function rejectBatch($batchId)
    {
        DB::table('item_approval_requests')
            ->where('batch_id', $batchId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'rejected',
                'rejected_at' => now(),
                'updated_at'  => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch rejected.',
        ]);
    }

    /* ================================
       CLASSIFICATION MANAGEMENT
    ================================= */
    public function updateClassification(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string',
            'description' => 'required|string',
            'classification' => 'required|string|max:255',
        ]);

        DB::table('items')
            ->where('item_name', $request->item_name)
            ->where('description', $request->description)
            ->update([
                'classification' => $request->classification,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('inventory.settings')
            ->with('success', 'Classification updated successfully.');
    }

    public function deleteClassification(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string',
            'description' => 'required|string',
        ]);

        DB::table('items')
            ->where('item_name', $request->item_name)
            ->where('description', $request->description)
            ->update([
                'classification' => null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('inventory.settings')
            ->with('success', 'Classification reset successfully.');
    }
}