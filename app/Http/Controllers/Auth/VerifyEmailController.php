<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function __invoke(VerifyEmailRequest $request): RedirectResponse
    {
        $user = User::query()->findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            if (!Auth::check()) {
                Auth::login($user);
            }
            return redirect()->route('dashboard')
                ->with('info', 'Email sudah diverifikasi sebelumnya');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Email berhasil diverifikasi, selamat datang!');
    }
}
