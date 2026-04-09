<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use App\Models\PermissionGroupItem;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name'  => 'Dashboard',
                'key'   => 'dashboard',
                'order' => 1,
                'permissions' => [
                    'view dashboard',
                ],
            ],
            [
                'name'  => 'Manajemen User',
                'key'   => 'management-user',
                'order' => 2,
                'permissions' => [
                    'view user',
                    'create user',
                    'edit user',
                    'delete user',
                    'verify user',
                ],
            ],
            [
                'name'  => 'Manajemen Role',
                'key'   => 'management-role',
                'order' => 3,
                'permissions' => [
                    'view role',
                    'create role',
                    'edit role',
                    'delete role',
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            $permissions = $groupData['permissions'];
            unset($groupData['permissions']);

            $group = PermissionGroup::updateOrCreate(
                ['key' => $groupData['key']],
                $groupData,
            );

            // Sync items — hapus lama, insert baru
            $group->items()->delete();
            foreach ($permissions as $permName) {
                PermissionGroupItem::create([
                    'permission_group_id' => $group->id,
                    'permission_name'     => $permName,
                ]);
            }
        }

        if (isset($this->command)) {
            $this->command->info('✅ Permission groups selesai disinkronisasi.');
        }
    }
}
