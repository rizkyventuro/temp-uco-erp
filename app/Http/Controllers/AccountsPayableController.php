<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        // ── Dummy data ─────────────────────────────────────────
        $statusPool   = ['lunas', 'parsial', 'parsial', 'parsial', 'belum_bayar'];
        $suppliers    = ['PT Sumber Rejeki', 'CV Maju Bersama', 'PT Agro Makmur', 'UD Berkah Jaya'];
        $dates        = ['03 Apr 2026', '10 Apr 2026', '15 Apr 2026'];
        $dueDates     = ['01 Apr 2026', '05 Apr 2026', '08 Apr 2026', '20 Apr 2026'];

        $allItems = collect(range(1, 57))->map(function ($i) use ($statusPool, $suppliers, $dates, $dueDates) {
            $status   = $statusPool[$i % count($statusPool)];
            $jumlah   = 60_000_000;
            $terbayar = match ($status) {
                'lunas'      => $jumlah,
                'parsial'    => 0,
                'belum_bayar' => 0,
                default      => 0,
            };
            $dueRaw    = $dueDates[$i % count($dueDates)];   // "01 Apr 2026" / "05 Apr 2026" / ...
            $isOverdue = in_array($dueRaw, ['01 Apr 2026']);
            $isNearDue = in_array($dueRaw, ['05 Apr 2026']);

            return [
                'id'          => (string) $i,
                'no_invoice'  => 'INV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'supplier'    => $suppliers[$i % count($suppliers)],
                'tanggal'     => $dates[$i % count($dates)],
                'jatuh_tempo' => $dueRaw,
                'jumlah'      => $jumlah,
                'terbayar'    => $terbayar,
                'sisa_utang'  => $jumlah - $terbayar,
                'status'      => $status,
                'is_overdue'  => $isOverdue,
                'is_near_due' => $isNearDue,
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
                        str_contains(strtolower($t['supplier']), $q)
                );
            })
            ->when($statusFilter, fn($col) => $col->filter(fn($t) => $t['status'] === $statusFilter));

        // ── Sorting ────────────────────────────────────────────
        $sortable = ['no_invoice', 'supplier', 'tanggal', 'jatuh_tempo', 'jumlah', 'terbayar', 'sisa_utang'];
        $sortCol  = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'tanggal';
        $sortDir  = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $sorted = $sortDir === 'asc'
            ? $filtered->sortBy($sortCol)->values()
            : $filtered->sortByDesc($sortCol)->values();

        // ── Stats ──────────────────────────────────────────────
        $belumLunas = $allItems->whereNotIn('status', ['lunas']);
        $nearDue    = $allItems->where('is_near_due', true)->where('status', '!=', 'lunas');
        $lunasBulan = $allItems->where('status', 'lunas');

        $stats = [
            'total_hutang'      => $belumLunas->sum('sisa_utang'),
            'total_tagihan'     => $belumLunas->count(),
            'jatuh_tempo_7hari' => $nearDue->sum('sisa_utang'),
            'jatuh_tempo_count' => $nearDue->count(),
            'lunas_bulan_ini'   => $lunasBulan->sum('jumlah'),
            'lunas_count'       => $lunasBulan->count(),
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

        return Inertia::render('accountsPayable/ListAccountsPayable', [
            'stats'   => $stats,
            'hutang'  => [
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
