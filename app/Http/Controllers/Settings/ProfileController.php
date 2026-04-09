<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AmazonServerService;
use Illuminate\Support\Str;
use Inertia\Inertia;

use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => $request->session()->get('status'),
            'profilePhotoUrl' => self::resolvePhotoUrl($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        if ($request->hasFile('profile_photo')) {
            $uploadResult = AmazonServerService::upload(
                'users/photos',
                $request->file('profile_photo'),
                Str::uuid()->toString()
            );
            $request->user()->profile_photo_path = $uploadResult['path'] ?? null;
            $request->user()->profile_photo_disk = $uploadResult['storage'] ?? config('filesystems.default');
        }

        DB::transaction(function () use ($request) {
            $request->user()->save();
        });

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public static function resolvePhotoUrl($user): ?string
    {
        return AmazonServerService::resolveUrl(
            $user->profile_photo_path,
            $user->profile_photo_disk
        );
    }
}
