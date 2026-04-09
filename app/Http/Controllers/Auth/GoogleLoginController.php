<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleLoginController extends Controller
{
    public function store(Request $request)
    {
        $token = $request->input('token');

        if (!$token) {
            return back()->withErrors(['email' => 'Google token is missing.']);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return back()->withErrors(['email' => 'Invalid Google token.']);
        }

        // Decode JWT Payload safely
        $payload = strtr($parts[1], '-_', '+/');
        $payload = base64_decode($payload);
        $dataUser = json_decode($payload, true);

        if (!$dataUser || !isset($dataUser['email'])) {
            return back()->withErrors(['email' => 'Failed to retrieve email from Google token.']);
        }

        $user = User::where('email', $dataUser['email'])->first();

        if (!$user) {
            // Create user automatically using provided google details
            $user = clone User::create([
                'name' => $dataUser['name'] ?? 'Google User',
                'email' => $dataUser['email'],
                'password' => Hash::make(Str::random(16)), // random password since they use Google
                'is_active' => true,
                'has_no_password' => 1,
                'email_verified_at' => now(), // Treat google emails as verified
                'profile_photo_path' => $dataUser['picture'] ?? null,
                'profile_photo_disk' => 'image_google',
            ]);
            $user->syncRoles(['pengepul']);
        } else {
            if (!$user->is_active) {
                return back()->withErrors(['email' => 'Your account is currently inactive.']);
            }
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
