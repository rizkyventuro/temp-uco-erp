<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return Inertia::render('settings/Password', [
            'requireOldPassword' => !$user->has_no_password
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $request->user()->update([
                'password' => $request->password,
                'has_no_password' => 0,
            ]);
        });


        return back();
    }
}
