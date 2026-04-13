<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsReceivableController extends Controller
{
    public function index(Request $request)
    {
        // ── Dummy data ─────────────────────────────────────────
        $statusPool = ['lunas', 'sebagian', 'sebagian', 'sebagian', 'belum_bayar'];
        $buyers     = ['PT Sumber Rejeki', 'CV Maju Bersama', 'PT Agro Makmur', 'UD Berkah Jaya'];
        $dates      = ['03 Apr 2026', '10 Apr 2026', '15 Apr 2026'];
        $dueDates   = ['08 Apr 2026', '08 Apr 2026', '15 Apr 2026', '20 Apr 2026'];

        $allItems = collect(range(1, 57))->map(function ($i) use ($statusPool, $buyers, $dates, $dueDates) {
            $status   = $statusPool[$i % count($statusPool)];
            $jumlah   = 60_000_000;
            $diterima = match ($status) {
                'lunas'      => $jumlah,
                'sebagian'   => 0,
                'belum_bayar' => 0,
                default      => 0,
            };
            $dueRaw    = $dueDates[$i % count($dueDates)];
            $isOverdue = $dueRaw === '08 Apr 2026' && $status !== 'lunas'; // sudah lewat

            return [
                'id'          => (string) $i,
                'no_invoice'  => 'INV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'buyer'       => $buyers[$i % count($buyers)],
                'tanggal'     => $dates[$i % count($dates)],
                'jatuh_tempo' => $dueRaw,
                'jumlah'      => $jumlah,
                'diterima'    => $diterima,
                'sisa'        => $jumlah - $diterima,
                'status'      => $status,
                'is_overdue'  => $isOverdue,
            ];
        });

        // ── Filtering ──────────────────────────────────────────
        $search       = $request->input('search', '');
        $statusFilter = $request->input('status');

        $filtered = $allItems
            ->when($search, function ($col) use ($search) {
                $q = strtolower($search);
                return $col->filter(
                    fn($t) =>
                    str_contains(strtolower($t['no_invoice']), $q) ||
                        str_contains(strtolower($t['buyer']), $q)
                );
            })
            ->when($statusFilter, fn($col) => $col->filter(fn($t) => $t['status'] === $statusFilter));

        // ── Sorting ────────────────────────────────────────────
        $sortable = ['no_invoice', 'buyer', 'tanggal', 'jatuh_tempo', 'jumlah', 'diterima', 'sisa'];
        $sortCol  = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'tanggal';
        $sortDir  = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $sorted = $sortDir === 'asc'
            ? $filtered->sortBy($sortCol)->values()
            : $filtered->sortByDesc($sortCol)->values();

        // ── Stats ──────────────────────────────────────────────
        $aktif       = $allItems->whereNotIn('status', ['lunas']);
        $overdue     = $allItems->where('is_overdue', true);
        $lunasBulan  = $allItems->where('status', 'lunas');

        $stats = [
            'total_piutang'      => $aktif->sum('sisa'),
            'total_tagihan'      => $aktif->count(),
            'lewat_jatuh_tempo'  => $overdue->sum('sisa'),
            'lewat_count'        => $overdue->count(),
            'diterima_bulan_ini' => $lunasBulan->sum('jumlah'),
            'diterima_pct'       => 15, // dummy persentase
        ];

        // ── Pagination ─────────────────────────────────────────
        $perPage     = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);
        $total       = $sorted->count();
        $lastPage    = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $lastPage);
        $offset      = ($currentPage - 1) * $perPage;

        $pageData = $sorted->slice($offset, $perPage)->values();
        $from     = $total === 0 ? null : $offset + 1;
        $to       = $total === 0 ? null : min($offset + $perPage, $total);

        $links   = [];
        $links[] = [
            'url'    => $currentPage > 1 ? $request->fullUrlWithQuery(['page' => $currentPage - 1]) : null,
            'label'  => '&laquo; Previous',
            'active' => false,
        ];
        for ($p = 1; $p <= $lastPage; $p++) {
            $links[] = ['url' => $request->fullUrlWithQuery(['page' => $p]), 'label' => (string) $p, 'active' => $p === $currentPage];
        }
        $links[] = [
            'url'    => $currentPage < $lastPage ? $request->fullUrlWithQuery(['page' => $currentPage + 1]) : null,
            'label'  => 'Next &raquo;',
            'active' => false,
        ];

        return Inertia::render('accountsReceivable/ListAccountsReceivable', [
            'stats'   => $stats,
            'piutang' => [
                'data'         => $pageData,
                'current_page' => $currentPage,
                'last_page'    => $lastPage,
                'per_page'     => $perPage,
                'total'        => $total,
                'from'         => $from,
                'to'           => $to,
                'links'        => $links,
            ],
            'filters' => $request->only(['search', 'perPage', 'status', 'sort', 'direction']),
        ]);
    }
}
