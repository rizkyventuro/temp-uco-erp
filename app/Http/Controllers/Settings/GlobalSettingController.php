<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlobalSettingController extends Controller
{
    public function edit(): Response
    {
        $verification = GlobalSetting::get('verification', ['mode' => 'manual']);

        return Inertia::render('settings/GlobalSetting', [
            'verificationMode' => $verification['mode'] ?? 'manual',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_mode' => 'required|in:manual,auto',
        ]);

        GlobalSetting::set(
            'verification',
            ['mode' => $request->verification_mode],
            'Pengaturan mode verifikasi user'
        );

        return to_route('global-setting.edit');
    }
}
