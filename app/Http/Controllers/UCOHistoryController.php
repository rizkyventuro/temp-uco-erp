<?php

namespace App\Http\Controllers;

use App\Models\PooHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UCOHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // ── Paginated histories (untuk list + infinite scroll) ────────
        $query = PooHistory::with('counterpart')
            ->forUser($userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('month')) {
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$request->month]);
        }

        $paginated = $query->paginate(15);

        $grouped = $paginated->getCollection()
            ->map(fn($h) => [
                'id'               => $h->id,
                'transaction_code' => $h->transaction_code,
                'volume'           => $h->volume,
                'type'             => $h->type,
                'type_label'       => $h->type_label,
                'counterpart_name' => $h->counterpart?->name,
                'created_at'       => $h->created_at->toISOString(),
                'created_at_label' => $h->created_at->translatedFormat('d F Y, H:i'),
            ])
            ->groupBy(fn($h) => \Carbon\Carbon::parse($h['created_at'])->translatedFormat('F Y'))
            ->map(fn($items, $label) => [
                'label' => $label,
                'items' => $items->values(),
            ])
            ->values();

        // ── Summary (tidak terpengaruh pagination, hanya filter month) ──
        $summaryQuery = PooHistory::forUser($userId);

        if ($request->filled('month')) {
            $summaryQuery->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$request->month]);
        }

        $allHistories = $summaryQuery->get();
        $summary = [
            'total_collection'   => $allHistories->where('type', PooHistory::TYPE_COLLECTION)->sum('volume'),
            'total_transfer_out' => $allHistories->where('type', PooHistory::TYPE_TRANSFER_OUT)->sum('volume'),
            'total_transfer_in'  => $allHistories->where('type', PooHistory::TYPE_TRANSFER_IN)->sum('volume'),
        ];

        return Inertia::render('admin/history/RiwayatTransaksi', [
            'grouped'     => $grouped,
            'summary'     => $summary,
            'filters'     => [
                'type'  => $request->type,
                'month' => $request->month,
            ],
            'nextPageUrl' => $paginated->nextPageUrl(),
            'hasMore'     => $paginated->hasMorePages(),
        ]);
    }
}
