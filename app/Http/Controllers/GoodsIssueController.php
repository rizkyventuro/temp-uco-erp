<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\GoodsIssue;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodsIssueController extends Controller
{
    // ── Index ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sortable = ['transaction_number', 'date', 'volume', 'selling_price', 'total_price', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'date';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $goodsIssues = GoodsIssue::with(['buyer', 'warehouse'])
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('buyer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('warehouse', fn($q) => $q->where('name', 'like', "%{$search}%"))
                )
            )
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->buyer_id, fn($q, $id) => $q->where('buyer_id', $id))
            ->when($request->warehouse_id, fn($q, $id) => $q->where('warehouse_id', $id))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->date_to,   fn($q, $d) => $q->whereDate('date', '<=', $d))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn($gi) => $this->formatGoodsIssue($gi));

        // ── Stats ──────────────────────────────────────────────
        $base = GoodsIssue::query();

        $totalUcoOut       = (clone $base)->whereIn('status', ['shipped', 'delivered'])->sum('volume');
        $totalUcoLastMonth = (clone $base)
            ->whereIn('status', ['shipped', 'delivered'])
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date',  now()->subMonth()->year)
            ->sum('volume');
        $totalUcoThisMonth = (clone $base)
            ->whereIn('status', ['shipped', 'delivered'])
            ->whereMonth('date', now()->month)
            ->whereYear('date',  now()->year)
            ->sum('volume');

        $trend = $totalUcoLastMonth > 0
            ? round((($totalUcoThisMonth - $totalUcoLastMonth) / $totalUcoLastMonth) * 100, 1)
            : 0;

        $totalSales      = (clone $base)->whereIn('status', ['shipped', 'delivered'])->sum('total_price');
        $totalTransactions = (clone $base)->count();

        $stats = [
            'total_uco_out'       => (float) $totalUcoOut,
            'total_uco_out_trend' => abs($trend),
            'total_uco_out_up'    => $trend >= 0,
            'total_sales'         => (float) $totalSales,
            'total_transactions'  => $totalTransactions,
        ];

        // ── Filter options ─────────────────────────────────────
        $buyers = Buyer::active()
            ->whereIn('id', GoodsIssue::distinct()->pluck('buyer_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = Warehouse::active()
            ->whereIn('id', GoodsIssue::distinct()->pluck('warehouse_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allBuyers     = Buyer::active()->orderBy('name')->get(['id', 'name', 'default_selling_price']);
        $allWarehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('goodsIssue/ListGoodsIssue', [
            'goodsIssues'   => $goodsIssues,
            'stats'         => $stats,
            'buyers'        => $buyers,
            'warehouses'    => $warehouses,
            'allBuyers'     => $allBuyers,
            'allWarehouses' => $allWarehouses,
            'filters'       => $request->only([
                'search',
                'perPage',
                'status',
                'buyer_id',
                'warehouse_id',
                'date_from',
                'date_to',
                'sort',
                'direction',
            ]),
        ]);
    }

    // ── Show ───────────────────────────────────────────────────

    public function show($id)
    {
        $gi = GoodsIssue::with(['buyer', 'warehouse'])->findOrFail($id);

        return Inertia::render('goodsIssue/ShowGoodsIssue', [
            'goodsIssue' => $this->formatGoodsIssue($gi),
        ]);
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'          => 'required|date',
            'buyer_id'      => 'required|exists:buyers,id',
            'warehouse_id'  => 'required|exists:warehouses,id',
            'volume'        => 'required|numeric|min:0.01',
            'selling_price' => 'required|numeric|min:0',
            'status'        => 'required|in:pending,shipped,delivered,cancelled',
            'notes'         => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated) {
            GoodsIssue::create(array_merge($validated, [
                'transaction_number' => GoodsIssue::generateTransactionNumber(),
                'total_price'        => $validated['volume'] * $validated['selling_price'],
                'created_by'         => Auth::id(),
            ]));
        });

        return redirect()->route('goods-issue.index')
            ->with('success', 'Barang keluar berhasil dicatat.');
    }

    // ── Update ─────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $gi = GoodsIssue::findOrFail($id);

        $validated = $request->validate([
            'date'          => 'sometimes|required|date',
            'buyer_id'      => 'sometimes|required|exists:buyers,id',
            'warehouse_id'  => 'sometimes|required|exists:warehouses,id',
            'volume'        => 'sometimes|required|numeric|min:0.01',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'status'        => 'sometimes|required|in:pending,shipped,delivered,cancelled',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $volume       = $validated['volume']        ?? $gi->volume;
        $sellingPrice = $validated['selling_price'] ?? $gi->selling_price;
        $validated['total_price'] = $volume * $sellingPrice;

        DB::transaction(fn() => $gi->update(array_merge($validated, ['updated_by' => Auth::id()])));

        return redirect()->back()
            ->with('success', 'Data barang keluar berhasil diperbarui.');
    }

    // ── Destroy ────────────────────────────────────────────────

    public function destroy($id)
    {
        $gi = GoodsIssue::findOrFail($id);

        DB::transaction(function () use ($gi) {
            $gi->update(['deleted_by' => Auth::id()]);
            $gi->delete();
        });

        return redirect()->back()
            ->with('success', 'Data barang keluar berhasil dihapus.');
    }

    // ── Update Status ──────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $gi = GoodsIssue::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,shipped,delivered,cancelled',
        ]);

        DB::transaction(fn() => $gi->update(array_merge($validated, ['updated_by' => Auth::id()])));

        return redirect()->back()
            ->with('success', 'Status barang keluar berhasil diperbarui.');
    }

    // ── Private Helpers ────────────────────────────────────────

    private function formatGoodsIssue(GoodsIssue $gi): array
    {
        return [
            'id'                 => $gi->id,
            'transaction_number' => $gi->transaction_number,
            'date'               => $gi->date?->format('Y-m-d'),
            'buyer_id'           => $gi->buyer_id,
            'buyer_name'         => $gi->buyer?->name,
            'warehouse_id'       => $gi->warehouse_id,
            'warehouse_name'     => $gi->warehouse?->name,
            'volume'             => (float) $gi->volume,
            'selling_price'      => (float) $gi->selling_price,
            'total_price'        => (float) $gi->total_price,
            'status'             => $gi->status,
            'status_label'       => $gi->status_label,
            'status_color'       => $gi->status_color,
            'notes'              => $gi->notes,
        ];
    }
}
