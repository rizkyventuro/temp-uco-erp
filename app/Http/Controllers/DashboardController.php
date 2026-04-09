<?php

namespace App\Http\Controllers;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PooCollection;
use App\Models\PooTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // if ($user->can(PermissionEnum::VIEW_DASHBOARD_ADMIN->value)) {
            return Inertia::render('admin/Dashboard', $this->dataDashboard());
        // }

        // if ($user->can(PermissionEnum::VIEW_DASHBOARD->value)) {
        //     return Inertia::render('user/Dashboard');
        // }

        abort(403);
    }
    private function dataDashboard(): array
    {
        // ── Recent Pending Users ───────────────────────────────────
        $recentUsers = User::with('roles')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'role'  => $u->roles->first()?->name ?? '—',
            ]);

        return compact(
            'recentUsers'
        );
    }
}
