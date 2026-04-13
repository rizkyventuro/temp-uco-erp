<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashBankController extends Controller
{
    public function index(Request $request)
    {
        // ── Bank Accounts (dari DB) ────────────────────────────
        $bankAccounts = BankAccount::where('is_active', true)
            ->orderBy('created_at')
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'code'      => $a->code,
                'name'      => $a->name,
                'type'      => $a->type,
                'is_active' => $a->is_active,
                'notes'     => $a->notes,
                'balance'   => 0, // nanti dari relasi transaksi
            ]);

        // ── Dummy transactions (57 rows) ───────────────────────
        $accountNames = ['BCA', 'BCA', 'Mandiri', 'Kas Tunai'];

        $allTransactions = collect(range(1, 57))->map(function ($i) use ($accountNames) {
            $isIn = $i % 3 === 0;

            return [
                'id'          => (string) $i,
                'date'        => '03 Apr 2026',
                'description' => 'Penjualan ke PT Bioenergi (BK-042)',
                'type'        => $isIn ? 'in' : 'out',
                'account'     => $accountNames[($i - 1) % 4],
                'amount_in'   => $isIn ? 22_000_000 : null,
                'amount_out'  => !$isIn ? 5_400_000 : null,
                'balance'     => 215_000_000 - ($i - 1) * 1_000_000,
            ];
        });

        // ── Filtering ──────────────────────────────────────────
        $search        = $request->input('search', '');
        $typeFilter    = $request->input('type');
        $accountFilter = $request->input('account');

        $filtered = $allTransactions
            ->when($search, function ($col) use ($search) {
                $q = strtolower($search);
                return $col->filter(
                    fn($t) =>
                    str_contains(strtolower($t['description']), $q) ||
                        str_contains(strtolower($t['account']), $q)
                );
            })
            ->when($typeFilter, fn($col) => $col->filter(fn($t) => $t['type'] === $typeFilter))
            ->when($accountFilter, fn($col) => $col->filter(fn($t) => $t['account'] === $accountFilter));

        // ── Sorting ────────────────────────────────────────────
        $sortable = ['date', 'amount_in', 'amount_out', 'balance'];
        $sortCol  = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'date';
        $sortDir  = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $sorted = $sortDir === 'asc'
            ? $filtered->sortBy($sortCol)->values()
            : $filtered->sortByDesc($sortCol)->values();

        // ── Pagination (manual) ────────────────────────────────
        $perPage     = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);
        $total       = $sorted->count();
        $lastPage    = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $lastPage);
        $offset      = ($currentPage - 1) * $perPage;

        $pageData = $sorted->slice($offset, $perPage)->values();

        $from = $total === 0 ? null : $offset + 1;
        $to   = $total === 0 ? null : min($offset + $perPage, $total);

        $links = [];
        $links[] = [
            'url'    => $currentPage > 1 ? $request->fullUrlWithQuery(['page' => $currentPage - 1]) : null,
            'label'  => '&laquo; Previous',
            'active' => false,
        ];
        for ($p = 1; $p <= $lastPage; $p++) {
            $links[] = [
                'url'    => $request->fullUrlWithQuery(['page' => $p]),
                'label'  => (string) $p,
                'active' => $p === $currentPage,
            ];
        }
        $links[] = [
            'url'    => $currentPage < $lastPage ? $request->fullUrlWithQuery(['page' => $currentPage + 1]) : null,
            'label'  => 'Next &raquo;',
            'active' => false,
        ];

        $transactions = [
            'data'         => $pageData,
            'current_page' => $currentPage,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
            'total'        => $total,
            'from'         => $from,
            'to'           => $to,
            'links'        => $links,
        ];

        // ── Render ─────────────────────────────────────────────
        return Inertia::render('kasBank/ListKasBank', [
            'bankAccounts' => $bankAccounts,
            'transactions' => $transactions,
            'filters'      => $request->only(['search', 'perPage', 'type', 'account', 'sort', 'direction']),
        ]);
    }
}
