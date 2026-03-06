<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LockController extends Controller
{
    public function unlockScreen(Request $request)
    {
        try {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $isValid = Hash::check($request->password, $user->password);

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Unlocked successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Unlock screen error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
}