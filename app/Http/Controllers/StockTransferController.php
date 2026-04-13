<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockTransferController extends Controller
{
    // ── Index ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sortable = ['transfer_number', 'volume', 'status', 'transferred_at', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('transfer_number', 'like', "%{$search}%")
                        ->orWhereHas('fromWarehouse', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('toWarehouse', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->from_warehouse_id, fn($q, $id) => $q->where('from_warehouse_id', $id))
            ->when($request->to_warehouse_id, fn($q, $id) => $q->where('to_warehouse_id', $id))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('per_page', 10))
            ->withQueryString()
            ->through(fn($t) => $this->formatTransfer($t));

        $stats = [
            'total'      => StockTransfer::count(),
            'pending'    => StockTransfer::pending()->count(),
            'in_transit' => StockTransfer::inTransit()->count(),
            'completed'  => StockTransfer::completed()->count(),
        ];

        $warehouses = Warehouse::with('city')
            ->orderBy('name')
            ->paginate($request->input('warehouse_page', 8), ['*'], 'warehouse_page')
            ->withQueryString()
            ->through(fn($g) => $this->formatWarehouse($g));

        $allWarehouses = Warehouse::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn($g) => ['id' => $g->id, 'label' => "{$g->name} ({$g->code})"]);

        return Inertia::render('stockTransfer/ListStockTransfer', [
            'transfers'     => $transfers,
            'warehouses'    => $warehouses,
            'allWarehouses' => $allWarehouses,
            'stats'         => $stats,
            'filters'       => $request->only([
                'search',
                'per_page',
                'status',
                'from_warehouse_id',
                'to_warehouse_id',
                'sort',
                'direction',
                'warehouse_page',
            ]),
        ]);
    }

    // ── Store (moved from WarehouseController) ─────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'volume'            => 'required|numeric|min:0.01',
            'transferred_at'    => 'nullable|date',
            'estimated_arrival' => 'nullable|date|after_or_equal:transferred_at',
            'officer'           => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            StockTransfer::create(array_merge($validated, [
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'status'          => 'Pending',
                'created_by'      => Auth::id(),
                'transferred_at'  => now(),
            ]));

            // TODO: deduct stock from source warehouse & add to destination
            // when a real inventory/stock table exists
        });

        return redirect()->back()->with('success', 'Stock transfer recorded successfully.');
    }

    // ── Update Status ──────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $transfer = StockTransfer::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,In Transit,Completed,Cancelled',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'Completed') {
            $data['completed_at'] = now();
        }

        DB::transaction(fn() => $transfer->update($data));

        return redirect()->back()->with('success', 'Transfer status updated successfully.');
    }

    // ── Destroy ────────────────────────────────────────────────

    public function destroy($id)
    {
        $transfer = StockTransfer::findOrFail($id);

        abort_if(
            $transfer->status === 'Completed',
            403,
            'Completed transfers cannot be deleted.'
        );

        DB::transaction(fn() => $transfer->delete());

        return redirect()->back()->with('success', 'Transfer deleted successfully.');
    }

    // ── Helpers ────────────────────────────────────────────────

    private function formatTransfer(StockTransfer $t): array
    {
        return [
            'id'                  => $t->id,
            'transfer_number'     => $t->transfer_number,
            'from_warehouse_id'   => $t->from_warehouse_id,
            'from_warehouse_name' => $t->fromWarehouse?->name,
            'from_warehouse_code' => $t->fromWarehouse?->code,
            'to_warehouse_id'     => $t->to_warehouse_id,
            'to_warehouse_name'   => $t->toWarehouse?->name,
            'to_warehouse_code'   => $t->toWarehouse?->code,
            'volume'              => (float) $t->volume,
            'status'              => $t->status,
            'notes'               => $t->notes,
            'transferred_at'      => $t->transferred_at?->translatedFormat('d M Y'),
            'completed_at'        => $t->completed_at?->translatedFormat('d M Y'),
            'created_by_name'     => $t->creator?->name,
        ];
    }

    private function formatWarehouse(Warehouse $g): array
    {
        $capacity  = (float) $g->capacity_max;
        $stock     = (float) ($g->current_stock ?? 0);
        $occupancy = $capacity > 0 ? round(($stock / $capacity) * 100) : 0;

        $statusLabel = match (true) {
            $occupancy >= 90 => 'Full',
            $occupancy >= 70 => 'Near Full',
            $occupancy >= 30 => 'Safe',
            default          => 'Low Stock',
        };

        $statusColor = match (true) {
            $occupancy >= 90 => 'red',
            $occupancy >= 70 => 'amber',
            $occupancy >= 30 => 'emerald',
            default          => 'rose',
        };

        return [
            'id'            => $g->id,
            'code'          => $g->code,
            'name'          => $g->name,
            'address'       => $g->address,
            'city_name'     => $g->city?->name,
            'type'          => $g->type,
            'is_active'     => $g->is_active,
            'capacity_max'  => $capacity,
            'current_stock' => $stock,
            'occupancy'     => $occupancy,
            'status_label'  => $statusLabel,
            'status_color'  => $statusColor,
        ];
    }
}
