<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodsReceiptController extends Controller
{
    // ── Index ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sortable = ['transaction_number', 'date', 'volume', 'purchase_price', 'total_price', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'date';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $goodsReceipts = GoodsReceipt::with(['supplier', 'warehouse'])
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('warehouse', fn($q) => $q->where('name', 'like', "%{$search}%"))
                )
            )
            ->when($request->status, function ($q, $status) {
                match ($status) {
                    'lunas'       => $q->where('status', 'lunas'),
                    'belum_lunas' => $q->where('status', 'belum_lunas'),
                    default       => null,
                };
            })
            ->when($request->supplier_id, fn($q, $id) => $q->where('supplier_id', $id))
            ->when($request->warehouse_id, fn($q, $id) => $q->where('warehouse_id', $id))
            ->when($request->date_from,   fn($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->date_to,     fn($q, $d) => $q->whereDate('date', '<=', $d))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn($gr) => $this->formatGoodsReceipt($gr));

        // ── Stats ──────────────────────────────────────────────
        $base = GoodsReceipt::query();

        $totalUcoIn        = (clone $base)->sum('volume');
        $totalUcoLastMonth = (clone $base)
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date',  now()->subMonth()->year)
            ->sum('volume');
        $totalUcoThisMonth = (clone $base)
            ->whereMonth('date', now()->month)
            ->whereYear('date',  now()->year)
            ->sum('volume');

        $trend = $totalUcoLastMonth > 0
            ? round((($totalUcoThisMonth - $totalUcoLastMonth) / $totalUcoLastMonth) * 100, 1)
            : 0;

        $totalPurchase     = (clone $base)->sum('total_price');
        $totalDebt         = (clone $base)->belumLunas()->sum('total_price');
        $dueDateCount      = (clone $base)->jatuhTempo()->count();
        $dueDateNominal    = (clone $base)->jatuhTempo()->sum('total_price');

        $stats = [
            'total_uco_in'       => (float) $totalUcoIn,
            'total_uco_in_trend' => abs($trend),
            'total_uco_in_up'    => $trend >= 0,
            'total_purchase'     => (float) $totalPurchase,
            'total_debt'         => (float) $totalDebt,
            'due_date_count'     => $dueDateCount,
            'due_date_nominal'   => (float) $dueDateNominal,
        ];

        // ── Filter options ─────────────────────────────────────
        $suppliers = Supplier::active()
            ->whereIn('id', GoodsReceipt::distinct()->pluck('supplier_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = Warehouse::active()
            ->whereIn('id', GoodsReceipt::distinct()->pluck('warehouse_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allSuppliers  = Supplier::active()->orderBy('name')
            ->get(['id', 'name', 'payment_term', 'payment_term_days', 'default_purchase_price']);
        $allWarehouses = Warehouse::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('goodsReceipt/ListGoodsReceipt', [
            'goodsReceipts' => $goodsReceipts,
            'stats'         => $stats,
            'suppliers'     => $suppliers,
            'warehouses'    => $warehouses,
            'allSuppliers'  => $allSuppliers,
            'allWarehouses' => $allWarehouses,
            'filters'       => $request->only([
                'search',
                'perPage',
                'status',
                'supplier_id',
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
        $gr = GoodsReceipt::with(['supplier', 'warehouse'])->findOrFail($id);

        return Inertia::render('goodsReceipt/ShowGoodsReceipt', [
            'goodsReceipt' => $this->formatGoodsReceipt($gr),
        ]);
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'           => 'required|date',
            'supplier_id'    => 'required|exists:suppliers,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'volume'         => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
            'status'         => 'required|in:lunas,belum_lunas',
            'due_date'       => [
                'nullable',
                'date',
                'after_or_equal:date',
                'required_if:status,belum_lunas',
            ],
            'notes'          => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated) {
            GoodsReceipt::create(array_merge($validated, [
                'transaction_number' => GoodsReceipt::generateTransactionNumber(),
                'total_price'        => $validated['volume'] * $validated['purchase_price'],
                'created_by'         => Auth::id(),
            ]));
        });

        return redirect()->route('goods-receipt.index')
            ->with('success', 'Barang masuk berhasil dicatat.');
    }

    // ── Update ─────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $gr = GoodsReceipt::findOrFail($id);

        $validated = $request->validate([
            'date'           => 'sometimes|required|date',
            'supplier_id'    => 'sometimes|required|exists:suppliers,id',
            'warehouse_id'   => 'sometimes|required|exists:warehouses,id',
            'volume'         => 'sometimes|required|numeric|min:0.01',
            'purchase_price' => 'sometimes|required|numeric|min:0',
            'status'         => 'sometimes|required|in:lunas,belum_lunas',
            'due_date'       => 'nullable|date|after_or_equal:date',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $volume        = $validated['volume']         ?? $gr->volume;
        $purchasePrice = $validated['purchase_price'] ?? $gr->purchase_price;
        $validated['total_price'] = $volume * $purchasePrice;

        if (isset($validated['status']) && $validated['status'] === 'lunas') {
            $validated['due_date'] = null;
        }

        DB::transaction(fn() => $gr->update(array_merge($validated, ['updated_by' => Auth::id()])));

        return redirect()->back()
            ->with('success', 'Data barang masuk berhasil diperbarui.');
    }

    // ── Destroy ────────────────────────────────────────────────

    public function destroy($id)
    {
        $gr = GoodsReceipt::findOrFail($id);

        DB::transaction(function () use ($gr) {
            $gr->update(['deleted_by' => Auth::id()]);
            $gr->delete();
        });

        return redirect()->back()
            ->with('success', 'Data barang masuk berhasil dihapus.');
    }

    // ── Update Status ──────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $gr = GoodsReceipt::findOrFail($id);

        $validated = $request->validate([
            'status'   => 'required|in:lunas,belum_lunas',
            'due_date' => 'nullable|date|required_if:status,belum_lunas',
        ]);

        if ($validated['status'] === 'lunas') {
            $validated['due_date'] = null;
        }

        DB::transaction(fn() => $gr->update(array_merge($validated, ['updated_by' => Auth::id()])));

        return redirect()->back()
            ->with('success', 'Status pembayaran berhasil diperbarui.');
    }

    // ── Private Helpers ────────────────────────────────────────

    private function formatGoodsReceipt(GoodsReceipt $gr): array
    {
        return [
            'id'                 => $gr->id,
            'transaction_number' => $gr->transaction_number,
            'date'               => $gr->date?->format('Y-m-d'),
            'supplier_id'        => $gr->supplier_id,
            'supplier_name'      => $gr->supplier?->name,
            'warehouse_id'       => $gr->warehouse_id,
            'warehouse_name'     => $gr->warehouse?->name,
            'volume'             => (float) $gr->volume,
            'purchase_price'     => (float) $gr->purchase_price,
            'total_price'        => (float) $gr->total_price,
            'status'             => $gr->status,
            'status_label'       => $gr->status_label,
            'due_date'           => $gr->due_date?->format('Y-m-d'),
            'notes'              => $gr->notes,
        ];
    }
}
