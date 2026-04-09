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

        if ($user->can(PermissionEnum::VIEW_DASHBOARD_ADMIN->value)) {
            return Inertia::render('admin/Dashboard', $this->adminData());
        }

        if ($user->can(PermissionEnum::VIEW_DASHBOARD->value)) {
            return Inertia::render('user/Dashboard');
        }

        abort(403);
    }
    private function adminData(): array
    {
        // ── User Stats ─────────────────────────────────────────────
        $userStats = [
            'total'    => User::count(),
            'active'   => User::where('is_active', true)->count(),
            'pending'  => User::where('is_verified_by_admin', 1)->count(),
            'rejected' => User::where('is_verified_by_admin', 3)->count(),
        ];

        // ── User Growth (12 bulan terakhir) ────────────────────────
        $userGrowth = User::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'label' => \Carbon\Carbon::createFromDate($row->year, $row->month, 1)
                    ->translatedFormat('M Y'),
                'total' => $row->total,
            ]);

        // ── Verifikasi Status ──────────────────────────────────────
        $verificationStatus = [
            ['label' => 'Belum Lengkap', 'value' => User::where('is_verified_by_admin', 0)->count(), 'color' => '#94a3b8'],
            ['label' => 'Menunggu',      'value' => User::where('is_verified_by_admin', 1)->count(), 'color' => '#f59e0b'],
            ['label' => 'Disetujui',     'value' => User::where('is_verified_by_admin', 2)->count(), 'color' => '#007C95'],
            ['label' => 'Ditolak',       'value' => User::where('is_verified_by_admin', 3)->count(), 'color' => '#ef4444'],
        ];

        // ── Transaksi per bulan (12 bulan terakhir) ────────────────
        $startDate = now()->subMonths(11)->startOfMonth();

        $months = collect(range(0, 11))->map(
            fn($i) => $startDate->copy()->addMonths($i)
        );

        $collections = PooCollection::select(
            DB::raw('YEAR(collected_at) as year'),
            DB::raw('MONTH(collected_at) as month'),
            DB::raw('SUM(volume) as total')
        )
            ->where('collected_at', '>=', $startDate)
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($r) => "{$r->year}-{$r->month}");

        $transfers = PooTransfer::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(volume_actual) as total')
        )
            ->where('created_at', '>=', $startDate)
            ->where('status', 1)
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn($r) => "{$r->year}-{$r->month}");

        $transactionChart = $months->map(fn($m) => [
            'label'       => $m->translatedFormat('M Y'),
            'pengambilan' => (float) ($collections->get("{$m->year}-{$m->month}")?->total ?? 0),
            'transfer'    => (float) ($transfers->get("{$m->year}-{$m->month}")?->total ?? 0),
        ])->values();

        // ── Recent Pending Users ───────────────────────────────────
        $recentUsers = User::with('roles')
            ->where('is_verified_by_admin', 1)
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
            'userStats',
            'userGrowth',
            'verificationStatus',
            'transactionChart',
            'recentUsers'
        );
    }
}
