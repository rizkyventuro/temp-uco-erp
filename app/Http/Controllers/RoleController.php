<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('permissions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => [
                'id'               => $r->id,
                'name'             => $r->name,
                'permissions_count' => $r->permissions_count,
                'permissions'      => $r->permissions->pluck('name'),
            ]);

        $permissions = Permission::all()
            ->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'lainnya')
            ->map(fn($group, $key) => [
                'group'  => $key,
                'items'  => $group->map(fn($p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                ]),
            ])
            ->values();

        return Inertia::render('ListRole', [
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }
}
