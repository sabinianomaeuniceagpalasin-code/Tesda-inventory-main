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
    $users = User::where('is_verified', 1)
        ->where('is_approved', 0)
        ->get();

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

    $lifespanItems = DB::table('items')
        ->select(
            'item_name',
            'description',
            DB::raw('COALESCE(MAX(expected_life_years), 0) as expected_life_years'),
            DB::raw('MIN(item_id) as item_id')
        )
        ->groupBy('item_name', 'description')
        ->orderBy('item_name', 'asc')
        ->orderBy('description', 'asc')
        ->get();

    $lifespanPreview = $lifespanItems->take(5);

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

    // ✅ SOURCE OF FUND MANAGEMENT
    $sourceOfFunds = DB::table('items')
        ->select(
            'item_name',
            'description',
            DB::raw('COALESCE(MAX(source_of_fund), "") as source_of_fund'),
            DB::raw('MIN(item_id) as item_id')
        )
        ->groupBy('item_name', 'description')
        ->orderBy('item_name', 'asc')
        ->orderBy('description', 'asc')
        ->get();

    $sourceOfFundsPreview = $sourceOfFunds->take(5);

    return view('Inventory-settings', compact(
        'users',
        'itemRequests',
        'archiveRequests',
        'lifespanItems',
        'lifespanPreview',
        'classifications',
        'classificationsPreview',
        'sourceOfFunds',
        'sourceOfFundsPreview'
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
            'expected_life_years' => 'required|integer|min:0',
        ]);

        DB::table('items')
            ->where('item_name', $request->item_name)
            ->where('description', $request->description)
            ->update([
                'expected_life_years' => $request->expected_life_years,
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
                'expected_life_years' => 0,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Lifespan reset for all matching items.');
    }

    /* ================================
       ITEM APPROVAL
    ================================= */
    public function approveItem($request_id)
{
    DB::beginTransaction();

    try {
        $admin = auth()->user();
        $adminUserId = $admin->user_id;
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));

        $requestRow = DB::table('item_approval_requests')
            ->where('request_id', $request_id)
            ->lockForUpdate()
            ->first();

        if (!$requestRow) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.',
            ], 404);
        }

        if ($requestRow->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved.',
            ], 422);
        }

        DB::table('item_approval_requests')
            ->where('request_id', $request_id)
            ->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'updated_at'  => now(),
            ]);

        $this->notifyRequester(
            $requestRow,
            'approved',
            $adminUserId,
            $adminName
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully.',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to approve request.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    public function rejectItem($request_id)
{
    DB::beginTransaction();

    try {
        $admin = auth()->user();
        $adminUserId = $admin->user_id;
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));

        $requestRow = DB::table('item_approval_requests')
            ->where('request_id', $request_id)
            ->lockForUpdate()
            ->first();

        if (!$requestRow) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found.',
            ], 404);
        }

        if ($requestRow->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected.',
            ], 422);
        }

        DB::table('item_approval_requests')
            ->where('request_id', $request_id)
            ->update([
                'status'      => 'rejected',
                'rejected_at' => now(),
                'updated_at'  => now(),
            ]);

        $this->notifyRequester(
            $requestRow,
            'rejected',
            $adminUserId,
            $adminName
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully.',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to reject request.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    public function approveBatch($batch_id)
{
    DB::beginTransaction();

    try {
        $admin = auth()->user();
        $adminUserId = $admin->user_id;
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));

        $batchRequests = DB::table('item_approval_requests')
            ->where('batch_id', $batch_id)
            ->lockForUpdate()
            ->get();

        if ($batchRequests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found.',
            ], 404);
        }

        foreach ($batchRequests as $requestRow) {
            if ($requestRow->status !== 'pending') {
                continue;
            }

            DB::table('item_approval_requests')
                ->where('request_id', $requestRow->request_id)
                ->update([
                    'status'      => 'approved',
                    'approved_at' => now(),
                    'updated_at'  => now(),
                ]);

            $this->notifyRequester(
                $requestRow,
                'approved',
                $adminUserId,
                $adminName
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Batch approved successfully.',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to approve batch.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    public function rejectBatch($batch_id)
{
    DB::beginTransaction();

    try {
        $admin = auth()->user();
        $adminUserId = $admin->user_id;
        $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));

        $batchRequests = DB::table('item_approval_requests')
            ->where('batch_id', $batch_id)
            ->lockForUpdate()
            ->get();

        if ($batchRequests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found.',
            ], 404);
        }

        foreach ($batchRequests as $requestRow) {
            if ($requestRow->status !== 'pending') {
                continue;
            }

            DB::table('item_approval_requests')
                ->where('request_id', $requestRow->request_id)
                ->update([
                    'status'      => 'rejected',
                    'rejected_at' => now(),
                    'updated_at'  => now(),
                ]);

            $this->notifyRequester(
                $requestRow,
                'rejected',
                $adminUserId,
                $adminName
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Batch rejected successfully.',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to reject batch.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function updateSourceOfFund(Request $request)
{
    $request->validate([
        'item_name' => 'required|string',
        'description' => 'required|string',
        'source_of_fund' => 'required|string|max:255',
    ]);

    DB::table('items')
        ->where('item_name', $request->item_name)
        ->where('description', $request->description)
        ->update([
            'source_of_fund' => $request->source_of_fund,
            'updated_at' => now(),
        ]);

    DB::table('propertyinventory')
        ->where('item_name', $request->item_name)
        ->update([
            'sources_of_fund' => $request->source_of_fund,
            'updated_at' => now(),
        ]);

    return redirect()
        ->route('inventory.settings')
        ->with('success', 'Source of fund updated successfully.');
}

public function deleteSourceOfFund(Request $request)
{
    $request->validate([
        'item_name' => 'required|string',
        'description' => 'required|string',
    ]);

    DB::table('items')
        ->where('item_name', $request->item_name)
        ->where('description', $request->description)
        ->update([
            'source_of_fund' => null,
            'updated_at' => now(),
        ]);

    DB::table('propertyinventory')
        ->where('item_name', $request->item_name)
        ->update([
            'sources_of_fund' => null,
            'updated_at' => now(),
        ]);

    return redirect()
        ->route('inventory.settings')
        ->with('success', 'Source of fund reset successfully.');
}

private function notifyRequester($requestRow, $status, $adminUserId, $adminName)
{
    if (!$requestRow || !$requestRow->requested_by_user_id) {
        return;
    }

    $title = $status === 'approved'
        ? 'QR Request Approved'
        : 'QR Request Rejected';

    $type = $status === 'approved'
        ? 'approval_approved'
        : 'approval_rejected';

    $severity = $status === 'approved'
        ? 'success'
        : 'danger';

    $message = 'Your request for ' . $requestRow->item_name .
        ' (Batch #' . $requestRow->batch_id . ') was ' . $status .
        ' by ' . $adminName . '.';

    $notifId = DB::table('notifications')->insertGetId([
        'type'               => $type,
        'title'              => $title,
        'message'            => $message,
        'severity'           => $severity,
        'entity_type'        => 'item_approval_request',
        'entity_id'          => $requestRow->request_id,
        'action_url'         => route('dashboard') . '?section=generate',
        'created_by_user_id' => $adminUserId,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    DB::table('notification_recipients')->insert([
        'notif_id'          => $notifId,
        'recipient_user_id' => $requestRow->requested_by_user_id,
        'read_at'           => null,
        'deleted_at'        => null,
        'created_at'        => now(),
        'updated_at'        => now(),
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