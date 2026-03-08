<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = auth()->user()->user_id;

        $notifications = DB::table('notification_recipients as nr')
            ->join('notifications as n', 'n.notif_id', '=', 'nr.notif_id')
            ->where('nr.recipient_user_id', $userId)
            ->whereNull('nr.deleted_at')
            ->orderByDesc('n.created_at')
            ->select(
                'nr.recipient_id',
                'nr.read_at',
                'n.notif_id',
                'n.title',
                'n.message',
                'n.type',
                'n.severity',
                'n.action_url',
                'n.entity_type',
                'n.entity_id',
                'n.created_at'
            )
            ->get();

        return view('notifications', [
            'notifications' => $notifications
        ]);
    }

    public function markAsRead($recipientId)
    {
        $userId = auth()->user()->user_id;

        DB::table('notification_recipients')
            ->where('recipient_id', $recipientId)
            ->where('recipient_user_id', $userId)
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function markAllAsRead()
    {
        $userId = auth()->user()->user_id;

        DB::table('notification_recipients')
            ->where('recipient_user_id', $userId)
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true
        ]);
    }
}