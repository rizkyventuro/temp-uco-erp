<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class,
            \App\Http\Controllers\Auth\CustomPasswordResetLinkController::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return null;
            }

            // Check if account is inactive
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('This account is inactive.'),
                ]);
            }

            // Check if account is suspended
            if ($user->suspended_until && $user->suspended_until->isFuture()) {
                $minutes = now()->diffInMinutes($user->suspended_until) + 1;
                throw ValidationException::withMessages([
                    Fortify::username() => __("This account is suspended. Please try again in {$minutes} minutes."),
                ]);
            }

            if (Hash::check($request->password, $user->password)) {
                // Reset on success
                $user->update([
                    'failed_login_attempts' => 0,
                    'suspended_until' => null,
                ]);

                return $user;
            }

            // Handle failed attempt
            $failedAttempts = $user->failed_login_attempts + 1;
            $updateData = ['failed_login_attempts' => $failedAttempts];

            if ($failedAttempts == 3) {
                $updateData['suspended_until'] = now()->addMinutes(3);
                $message = __('Too many failed login attempts. Your account is suspended for 3 minutes.');
            } elseif ($failedAttempts == 4) {
                $updateData['suspended_until'] = now()->addMinutes(4);
                $message = __('Too many failed login attempts. Your account is suspended for 4 minutes.');
            } elseif ($failedAttempts == 5) {
                $updateData['suspended_until'] = now()->addMinutes(5);
                $message = __('Too many failed login attempts. Your account is suspended for 5 minutes.');
            } elseif ($failedAttempts >= 6) {
                $updateData['is_active'] = false;
                $message = __('Too many failed login attempts. Your account is now inactive. Please contact support.');
            } else {
                $message = __('Invalid credentials.');
            }

            $user->update($updateData);

            throw ValidationException::withMessages([
                Fortify::username() => $message,
            ]);
        });

    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
