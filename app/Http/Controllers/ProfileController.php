<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\LoginHistory;



class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile-settings'); // change if your blade name is different
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // Validate basic info
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')
            ],
        ];

        // If user wants to change password, require current_password + confirmation
        $wantsPasswordChange =
            $request->filled('current_password') ||
            $request->filled('new_password') ||
            $request->filled('new_password_confirmation');

        if ($wantsPasswordChange) {
            $rules['current_password'] = ['required'];
            $rules['new_password'] = ['required', 'min:8', 'confirmed']; 
            // confirmed => expects new_password_confirmation
        }

        $data = $request->validate($rules);

        // Update basic info
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'];
        $user->email = $data['email'];

        // Only set if column exists in users table
        if ($request->has('contact_no')) {
            $user->contact_number = $data['contact_no'] ?? null;
        }

        // Update password if requested
        if ($wantsPasswordChange) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'Current password is incorrect.'])
                    ->withInput();
            }

            $user->password = Hash::make($data['new_password']);
        }

        $user->save();

        return back()->with('success', 'Account settings updated successfully.');
    }

    public function loginHistory(Request $request)
{
    $user = $request->user();
    $userId = $user->user_id ?? $user->id;

    $range = $request->query('range', '7'); // 7, 30, all
    $q = LoginHistory::where('user_id', $userId)->orderByDesc('logged_in_at');

    if ($range !== 'all') {
        $days = (int)$range;
        $q->where('logged_in_at', '>=', now()->subDays($days));
    }

    $history = $q->limit(200)->get();
    $lastSuccessful = LoginHistory::where('user_id', $userId)
        ->orderByDesc('logged_in_at')
        ->first();

    return view('login-history', compact('history', 'lastSuccessful', 'range'));
}
}