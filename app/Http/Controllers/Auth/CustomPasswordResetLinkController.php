<?php

namespace App\Http\Controllers\Auth;

use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Requests\SendPasswordResetLinkRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Illuminate\Contracts\Support\Responsable;

class CustomPasswordResetLinkController extends PasswordResetLinkController
{
    public function store(SendPasswordResetLinkRequest $request): Responsable
    {
        $email = strtolower($request->input(Fortify::email()));
        $ip    = $request->ip();

        $dailyKey    = 'pwd_reset_daily_' . $email . '_' . $ip;
        $cooldownKey = 'pwd_reset_cooldown_' . $email . '_' . $ip;

        // Check if there is an active cooldown (2 minutes)
        if (Cache::has($cooldownKey)) {
            $secondsRemaining = Cache::get($cooldownKey) - time();
            if ($secondsRemaining > 0) {
                throw ValidationException::withMessages([
                    Fortify::email() => __('Please wait ' . ceil($secondsRemaining / 60) . ' minutes before requesting another password reset email.'),
                ]);
            }
        }

        // Check daily limit (max 3 times)
        $attemptsToday = Cache::get($dailyKey, 0);

        if ($attemptsToday >= 3) {
            $secondsUntilTomorrow = strtotime('tomorrow') - time();
            $hours   = floor($secondsUntilTomorrow / 3600);
            $minutes = floor(($secondsUntilTomorrow % 3600) / 60);

            throw ValidationException::withMessages([
                Fortify::email() => __('You have reached the maximum number of password reset requests for today. Please try again in ' . $hours . ' hours and ' . $minutes . ' minutes.'),
            ]);
        }

        // Increment attempts and set cooldown
        Cache::put($dailyKey, $attemptsToday + 1, strtotime('tomorrow') - time());
        Cache::put($cooldownKey, time() + 120, 120);

        return parent::store($request);
    }
}