<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\KtpAutoVerificationJob;
use App\Models\GlobalSetting;
use App\Models\Notification;
use App\Models\User;
use App\Services\AmazonServerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $user = Auth::user()->load('profile');

        if ($user->is_verified_by_admin === 2) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/CompleteProfile', [
            'user' => [
                'name'                 => $user->name,
                'is_verified_by_admin' => $user->is_verified_by_admin,
                'profile'              => $user->profile ? [
                    'phone'                  => $user->profile->phone,
                    'birth_date'             => $user->profile->birth_date?->format('Y-m-d'),
                    'gender'                 => $user->profile->gender,
                    'occupation'             => $user->profile->occupation,
                    'address'                => $user->profile->address,
                    'province_referensi_id'  => $user->profile->province_referensi_id,
                    'city_referensi_id'      => $user->profile->city_referensi_id,
                    'district'               => $user->profile->district,
                    'village'                => $user->profile->village,
                    'postal_code'            => $user->profile->postal_code,
                    'id_card_number'         => $user->profile->id_card_number,
                    'verification_note'      => $user->profile->verification_note,
                    'id_card_photo_url' => $user->profile?->id_card_photo_path
                        ? \Storage::disk($user->profile->id_card_photo_disk)->temporaryUrl(
                            $user->profile->id_card_photo_path,
                            now()->addMinutes(30)
                        )
                        : null,
                ] : null,
            ],
            'provinces' => \App\Models\Province::orderBy('nama')->get(['referensi_id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isRejected = $user->is_verified_by_admin === 3;

        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'phone'                 => ['required', 'string', 'max:20'],
            'birth_date'            => ['required', 'date'],
            'gender'                => ['required', 'in:male,female'],
            'occupation'            => ['nullable', 'string', 'max:255'],
            'address'               => ['required', 'string'],
            'province_referensi_id' => ['required', 'string', 'max:32', 'exists:provinces,referensi_id'],
            'city_referensi_id'     => ['required', 'string', 'max:32', 'exists:cities,referensi_id'],
            'district'              => ['nullable', 'string', 'max:255'],
            'village'               => ['nullable', 'string', 'max:255'],
            'postal_code'           => ['nullable', 'string', 'max:10'],
            'id_card_number'        => ['required', 'string', 'size:16'],
            'id_card_photo'         => [$isRejected ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Upload foto KTP
        $idCardPhotoPath = $user->profile?->id_card_photo_path;
        $idCardPhotoDisk = $user->profile?->id_card_photo_disk;

        if ($request->hasFile('id_card_photo')) {
            $uploadResult = AmazonServerService::upload(
                'users/id-cards',
                $request->file('id_card_photo'),
                Str::uuid()->toString()
            );
            $idCardPhotoPath = $uploadResult['path'] ?? null;
            $idCardPhotoDisk = $uploadResult['storage'] ?? config('filesystems.default');
        }

        // Cek setting verifikasi
        $setting          = GlobalSetting::get('verification', ['mode' => 'manual']);
        $hasNewPhoto      = $request->hasFile('id_card_photo');
        $hasExistingPhoto = $user->profile?->id_card_photo_path !== null;

        // Apakah perlu dispatch job auto-verifikasi ke queue?
        $dispatchAutoVerification = $setting['mode'] === 'auto' && ($hasNewPhoto || $hasExistingPhoto);

        // Simpan ke DB — selalu pending dulu; job queue yang akan update status akhir
        DB::transaction(function () use ($user, $request, $idCardPhotoPath, $idCardPhotoDisk) {
            $user->update([
                'name'                 => $request->name,
                'is_verified_by_admin' => 1, // pending
                'verified_by'          => $user->verified_by,
                'verified_at'          => null,
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone'                 => $request->phone,
                    'birth_date'            => $request->birth_date,
                    'gender'                => $request->gender,
                    'occupation'            => $request->occupation,
                    'address'               => $request->address,
                    'province_referensi_id' => $request->province_referensi_id,
                    'city_referensi_id'     => $request->city_referensi_id,
                    'district'              => $request->district,
                    'village'               => $request->village,
                    'postal_code'           => $request->postal_code,
                    'id_card_number'        => $request->id_card_number,
                    'id_card_photo_path'    => $idCardPhotoPath,
                    'id_card_photo_disk'    => $idCardPhotoDisk,
                    'verification_note'     => null,
                    'noted_by'              => null,
                    'noted_at'              => null,
                ]
            );
        });

        if ($dispatchAutoVerification) {
            // Job menangani OCR, retry maks 3x jika non-200, lalu update status & kirim notifikasi
            KtpAutoVerificationJob::dispatch(
                userId: $user->id,
                profileData: [
                    'name'           => $request->name,
                    'id_card_number' => $request->id_card_number,
                    'birth_date'     => $request->birth_date,
                    'gender'         => $request->gender,
                    'occupation'     => $request->occupation,
                    'district'       => $request->district,
                    'village'        => $request->village,
                ],
                photoPath: $idCardPhotoPath,
                photoDisk: $idCardPhotoDisk,
            );
        } else {
            // Mode manual → notifikasi admin untuk review
            $this->sendNotification($user, 1, $isRejected);
        }

        return redirect()->route('dashboard');
    }

    // =========================================================
    // NOTIFIKASI
    // =========================================================

    private function sendNotification(User $user, int $status, bool $isRejected): void
    {
        match ($status) {

            // Pending → notif ke semua admin
            1 => $this->notifyAdmins($user, $isRejected),

            // Auto verified → notif ke user
            2 => Notification::create([
                'type'            => Notification::TYPE_SUCCESS,
                'title'           => 'Profil Disetujui Otomatis',
                'message'         => 'Data KTP Anda cocok. Profil Anda telah diverifikasi secara otomatis.',
                'notifiable_id'   => $user->id,
                'notifiable_type' => User::class,
            ]),

            // Auto rejected → notif ke user
            3 => Notification::create([
                'type'            => Notification::TYPE_ERROR,
                'title'           => 'Verifikasi Otomatis Gagal',
                'message'         => 'Data yang Anda isi tidak cocok dengan foto KTP. Silakan periksa kembali data Anda.',
                'notifiable_id'   => $user->id,
                'notifiable_type' => User::class,
                'url'             => route('complete-profile.show'),
            ]),

            default => null,
        };
    }

    private function notifyAdmins(User $user, bool $isRejected): void
    {
        $admins = User::role('admin')->get();
        $action = $isRejected ? 'memperbaiki' : 'melengkapi';

        foreach ($admins as $admin) {
            Notification::create([
                'type'            => Notification::TYPE_INFO,
                'title'           => 'Profil Perlu Verifikasi',
                'message'         => "User {$user->name} telah {$action} profil dan menunggu verifikasi Anda.",
                'notifiable_id'   => $admin->id,
                'notifiable_type' => User::class,
                'sender_id'       => $user->id,
                'url'             => route('management-user.index', ['status' => 'pending']),
            ]);
        }
    }
}
