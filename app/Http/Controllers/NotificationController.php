<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications', [
            'notifications' => Notification::latest()->get()
        ]);
    }
}
