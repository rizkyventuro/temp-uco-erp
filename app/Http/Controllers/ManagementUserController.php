<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Settings\ProfileController;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Services\AmazonServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManagementUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('perPage', 10))
            ->withQueryString()
            ->through(fn($user) => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'is_active'            => $user->is_active,
                'profile_photo_url'    => ProfileController::resolvePhotoUrl($user),
                'roles'                => $user->roles->map(fn($role) => [
                    'id'   => $role->id,
                    'name' => $role->name,
                ]),
            ]);

        return Inertia::render('managementUser/ListManagementUser', [
            'users'        => $users,
            'filters'      => $request->only(['search', 'perPage', 'status']),
            'pendingCount' => 0,
        ]);
    }

    public function create()
    {
        return Inertia::render('managementUser/CreateUser', [
            'roles'            => $this->getRoles(),
            'permissionGroups' => $this->getPermissionGroups(),
        ]);
    }

    public function edit($id)
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);

        $rolePermissions = $user->roles->flatMap(fn($r) => $r->permissions->pluck('name'))->unique()->values();
        $directPermissions = $user->permissions->pluck('name');

        return Inertia::render('managementUser/EditUser', [
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'is_active'   => $user->is_active,
                'role'        => $user->roles->first()?->name,
                'role_permissions'   => $rolePermissions,
                'direct_permissions' => $directPermissions,
            ],
            'roles'            => $this->getRoles(),
            'permissionGroups' => $this->getPermissionGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password'      => ['required', Rules\Password::defaults()],
            'role'          => 'required|string|exists:roles,name',
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $photoPath = null;
        $photoDisk = null;
        if ($request->hasFile('profile_photo')) {
            $uploadResult = AmazonServerService::upload('users/photos', $request->file('profile_photo'), Str::uuid()->toString());
            $photoPath    = $uploadResult['path'] ?? null;
            $photoDisk    = $uploadResult['storage'] ?? config('filesystems.default');
        }

        DB::transaction(function () use ($request, $photoPath, $photoDisk) {
            $user = User::create([
                'name'               => $request->name,
                'email'              => $request->email,
                'password'           => Hash::make($request->password),
                'profile_photo_path' => $photoPath,
                'profile_photo_disk' => $photoDisk,
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]);

            $user->syncRoles([$request->role]);

            // Hanya simpan permission yang di LUAR bawaan role (direct permissions)
            $role = Role::findByName($request->role);
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $extraPermissions = array_diff($request->permissions ?? [], $rolePermissions);
            $user->syncPermissions($extraPermissions);
        });

        return redirect()->route('management-user.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name'          => 'sometimes|required|string|max:255',
            'email'         => 'sometimes|required|string|lowercase|email|max:255|unique:' . User::class . ',email,' . $user->id,
            'is_active'     => 'sometimes|boolean',
            'role'          => 'sometimes|required|string|exists:roles,name',
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
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
            $uploadResult       = AmazonServerService::upload('users/photos', $request->file('profile_photo'), Str::uuid()->toString());
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

                // Hanya simpan permission di luar bawaan role
                $role = Role::findByName($request->role);
                $rolePermissions  = $role->permissions->pluck('name')->toArray();
                $extraPermissions = array_diff($request->permissions ?? [], $rolePermissions);
                $user->syncPermissions($extraPermissions);
            }
        });

        return redirect()->route('management-user.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        DB::transaction(fn() => $user->delete());

        return redirect()->back();
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getRoles(): \Illuminate\Support\Collection
    {
        return Role::with('permissions')->get()->map(fn($role) => [
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name')->values(),
        ]);
    }

    private function getPermissionGroups(): \Illuminate\Support\Collection
    {
        $allPermissions = Permission::all()->keyBy('name');

        return PermissionGroup::with('items')
            ->orderBy('order')
            ->get()
            ->map(fn($group) => [
                'id'   => $group->id,
                'name' => $group->name,
                'key'  => $group->key,
                'permissions' => $group->items
                    ->filter(fn($item) => $allPermissions->has($item->permission_name))
                    ->map(fn($item) => [
                        'name' => $item->permission_name,
                    ])
                    ->values(),
            ]);
    }
}
