<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Settings\ProfileController;
use App\Mail\UserVerifiedMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\AmazonServerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ManagementUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'profile'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status === 'pending', function ($query) {
                $query->where('is_verified_by_admin', 1);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('perPage', 10))
            ->withQueryString()
            ->through(fn($user) => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'is_active'            => $user->is_active,
                'is_verified_by_admin' => $user->is_verified_by_admin,
                'profile_photo_url'    => ProfileController::resolvePhotoUrl($user),
                'profile'              => $user->profile ? [
                    'phone'             => $user->profile->phone,
                    'birth_date'        => $user->profile->birth_date?->format('Y-m-d'),
                    'gender'            => $user->profile->gender,
                    'occupation'        => $user->profile->occupation,
                    'address'           => $user->profile->address,
                    'province'          => $user->profile->province,
                    'city'              => $user->profile->city,
                    'district'          => $user->profile->district,
                    'village'           => $user->profile->village,
                    'postal_code'       => $user->profile->postal_code,
                    'id_card_number'    => $user->profile->id_card_number,
                    'id_card_photo_url' => $user->profile->id_card_photo_path
                        ? \Storage::disk($user->profile->id_card_photo_disk ?? 'public')
                        ->temporaryUrl($user->profile->id_card_photo_path, now()->addMinutes(30))
                        : null,
                ] : null,
                'roles' => $user->roles->map(fn($role) => [
                    'id'   => $role->id,
                    'name' => $role->name,
                ]),
            ]);


        // status 1 = menunggu verifikasi
        $pendingCount = User::where('is_verified_by_admin', 1)->count();

        return Inertia::render('admin/managementUser/ListManagementUser', [
            'users'        => $users,
            'roles'        => Role::all(['id', 'name']),
            'filters'      => $request->only(['search', 'perPage', 'status']),
            'pendingCount' => $pendingCount,
        ]);
    }

    public function verify(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->update([
                'is_verified_by_admin' => 2, // disetujui
                'verified_by' => Auth::user()->id ?? null,
                'verified_at' => now()
            ]);

            // Reset verification_note jika ada
            $user->profile()->update([
                'verification_note' => null,
                'noted_by'          => null,
                'noted_at'          => null,
            ]);

            Mail::to($user->email)->send(new UserVerifiedMail($user));
        });

        // Notifikasi ke user
        Notification::create([
            'type'            => Notification::TYPE_SUCCESS,
            'title'           => 'Profil Disetujui',
            'message'         => 'Profil Anda telah diverifikasi dan disetujui oleh admin.',
            'notifiable_id'   => $user->id,
            'notifiable_type' => User::class,
        ]);

        return back()->with('success', 'User berhasil diverifikasi.');
    }

    public function reject(Request $request, User $user)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'is_verified_by_admin' => 3, // ditolak
            ]);

            $user->profile()->update([
                'verification_note' => $request->note,
                'noted_by'          => Auth::id(),
                'noted_at'          => now(),
            ]);
        });

        // Notifikasi ke user
        Notification::create([
            'type'            => Notification::TYPE_ERROR,
            'title'           => 'Profil Ditolak',
            'message'         => 'Profil Anda ditolak oleh admin. Silakan periksa catatan dan perbaiki data Anda.',
            'notifiable_id'   => $user->id,
            'notifiable_type' => User::class,
            'url'             => route('complete-profile.show'),
        ]);

        return back()->with('success', 'Profil user berhasil ditolak.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password'      => ['required', Rules\Password::defaults()],
            'role'          => 'required|string|exists:roles,name',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $photoPath = null;
        $photoDisk = null;
        if ($request->hasFile('profile_photo')) {
            $uploadResult = AmazonServerService::upload('users/photos', $request->file('profile_photo'), Str::uuid()->toString());
            $photoPath = $uploadResult['path'] ?? null;
            $photoDisk = $uploadResult['storage'] ?? config('filesystems.default');
        }

        DB::transaction(function () use ($request, $photoPath, $photoDisk) {
            $user = User::create([
                'name'               => $request->name,
                'email'              => $request->email,
                'password'           => Hash::make($request->password),
                'profile_photo_path' => $photoPath,
                'profile_photo_disk' => $photoDisk,
                'is_active'          => true,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$request->role]);
        });

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name'          => 'sometimes|required|string|max:255',
            'email'         => 'sometimes|required|string|lowercase|email|max:255|unique:' . User::class . ',email,' . $user->id,
            'is_active'     => 'sometimes|boolean',
            'role'          => 'sometimes|required|string|exists:roles,name',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', Rules\Password::defaults()];
        }

        $request->validate($rules);

        $data = $request->only(['name', 'email', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            $uploadResult = AmazonServerService::upload('users/photos', $request->file('profile_photo'), Str::uuid()->toString());
            $data['profile_photo_path'] = $uploadResult['path'] ?? null;
            $data['profile_photo_disk'] = $uploadResult['storage'] ?? config('filesystems.default');
        }

        if ($request->has('is_active')) {
            $data['failed_login_attempts'] = 0;
            $data['suspended_until']       = null;
        }

        DB::transaction(function () use ($user, $data, $request) {
            $user->update($data);

            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }
        });

        return redirect()->back();
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        DB::transaction(function () use ($user) {
            $user->delete();
        });


        return redirect()->back();
    }
}
