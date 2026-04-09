<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'view dashboard',

            // Management User
            'view user',
            'create user',
            'edit user',
            'delete user',
            'verify user',

            // Role
            'view role',
            'create role',
            'edit role',
            'delete role'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $deletedPermissions = Permission::whereNotIn('name', $permissions)->get();
        foreach ($deletedPermissions as $permission) {
            $permission->roles()->detach();
            $permission->users()->detach();
            $permission->delete();
        }

        $totalPermissions   = count($permissions);
        $removedPermissions = $deletedPermissions->count();

        // ── Roles ──────────────────────────────────────────────────
        $roles = [
            'admin' => [
                // Management User
                'view user',
                'create user',
                'edit user',
                'delete user',
                'verify user',

                // Role
                'view role',
                'create role',
                'edit role',
                'delete role',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $role->wasRecentlyCreated ? $created++ : $updated++;

            $role->syncPermissions($rolePermissions);
        }

        $deletedRoles = Role::whereNotIn('name', array_keys($roles))->get();
        $removedRoles = $deletedRoles->count();

        foreach ($deletedRoles as $role) {
            $role->users()->detach();
            $role->delete();
        }

        // ── Cache & Summary ────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($this->command) {
            $this->command->info("✅ Permission selesai disinkronisasi.");
            $this->command->info("   Total aktif : {$totalPermissions}");
            $this->command->warn("   Dihapus     : {$removedPermissions}");
            $this->command->newLine();
            $this->command->info("✅ Role selesai disinkronisasi.");
            $this->command->info("   Dibuat  : {$created}");
            $this->command->info("   Updated : {$updated}");
            $this->command->warn("   Dihapus : {$removedRoles}");
        }

        // ── Default Users ──────────────────────────────────────────
        $defaultUsers = [
            [
                'name'                => 'Admin',
                'email'               => 'admin@gmail.com',
                'password'            => Hash::make('admin'),
                'email_verified_at'   => now(),
            ],
        ];

        foreach ($defaultUsers as $userData) {
            \App\Models\User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        if ($this->command) {
            $this->command->newLine();
            $this->command->info("✅ Default users selesai disinkronisasi.");
        }
    }
}
