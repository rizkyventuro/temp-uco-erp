<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'view dashboard',
            'view dashboard admin',

            // Pengambilan POO
            'view pengambilan poo',
            'create pengambilan poo',
            'detail pengambilan poo',

            // Transfer UCO
            'view transfer',
            'create transfer',
            'detail transfer',
            'claim transfer',

            // Penjualan / Export
            'view penjualan',
            'create penjualan',
            'detail penjualan',
            'download penjualan',

            // Riwayat
            'view riwayat',

            // Master POO
            'view master poo',
            'create master poo',
            'edit master poo',
            'delete master poo',

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

            // Notification
            'view notification',
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
                // Dashboard
                'view dashboard admin',
                
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

                // Notification
                'view notification',
            ],

            'pengepul' => [
                // Dashboard
                'view dashboard',

                // Pengambilan POO
                'view pengambilan poo',
                'create pengambilan poo',
                'detail pengambilan poo',

                // Transfer UCO
                'view transfer',
                'create transfer',
                'detail transfer',
                'claim transfer',

                // Penjualan / Export
                'view penjualan',
                'create penjualan',
                'detail penjualan',
                'download penjualan',

                // Riwayat
                'view riwayat',

                // Master POO
                'view master poo',
                'create master poo',
                'edit master poo',
                'delete master poo',

                // Notification
                'view notification',
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

        // ── Default Users Role ─────────────────────────────────────
        $userRoles = [
            'admin@gmail.com'     => 'admin',
            'pengepul@gmail.com'  => 'pengepul',
            'pengepul2@gmail.com' => 'pengepul',
        ];

        foreach ($userRoles as $email => $role) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
            }
        }

        if ($this->command) {
            $this->command->newLine();
            $this->command->info("✅ Default users role selesai disinkronisasi.");
        }
    }
}
