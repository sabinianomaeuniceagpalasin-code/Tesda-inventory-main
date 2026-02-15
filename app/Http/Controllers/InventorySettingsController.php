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

        // Pending item approval requests, batched
        $itemRequests = DB::table('item_approval_requests')
            ->select(
                'item_name',
                'serial_number',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('MIN(request_id) as request_id'), // pick one representative id
                'request_type',
                'requested_at',
                'created_at'
            )
            ->where('status', 'pending')
            ->groupBy('item_name', 'serial_number', 'request_type', 'created_at', 'requested_at')
            ->orderBy('requested_at', 'desc')
            ->get();

        return view('Inventory-settings', compact('users', 'itemRequests'));
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
       ITEM APPROVAL
    ================================= */

    public function approveItem($id)
    {
        DB::table('item_approval_requests')
            ->where('request_id', $id)
            ->update([
                'status'       => 'approved',
                'approved_at'  => now(),
                'updated_at'   => now(),
            ]);

        // OPTIONAL: generate QR code / inventory entry here

        return back()->with('success', 'Item request approved.');
    }

    public function rejectItem($id)
    {
        DB::table('item_approval_requests')
            ->where('request_id', $id)
            ->update([
                'status'       => 'rejected',
                'rejected_at'  => now(),
                'updated_at'   => now(),
            ]);

        return back()->with('success', 'Item request rejected.');
    }
}