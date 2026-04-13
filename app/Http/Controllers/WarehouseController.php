<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    // ── Index ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sortable = ['name', 'code', 'capacity_max', 'operating_cost', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $warehouses = Warehouse::with('city')
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('pic', 'like', "%{$search}%")
                )
            )
            ->when($request->status, function ($q, $status) {
                match ($status) {
                    'active'   => $q->where('is_active', true),
                    'inactive' => $q->where('is_active', false),
                    default    => null,
                };
            })
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->city_id, fn($q, $cityId) => $q->where('city_id', $cityId))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 12))
            ->withQueryString()
            ->through(fn($g) => $this->formatWarehouse($g));

        $stats = [
            'total'    => Warehouse::count(),
            'active'   => Warehouse::active()->count(),
            'inactive' => Warehouse::inactive()->count(),
        ];

        $cities = City::whereIn('id', Warehouse::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/warehouse/ListWarehouse', [
            'warehouses' => $warehouses,
            'stats'      => $stats,
            'cities'     => $cities,
            'allCities'  => $allCities,
            'filters'    => $request->only(['search', 'perPage', 'status', 'type', 'city_id', 'sort', 'direction']),
        ]);
    }

    // ── Show ───────────────────────────────────────────────────

    public function show(Request $request, $id)
    {
        $warehouse = Warehouse::with('city')->findOrFail($id);

        return Inertia::render('masterData/warehouse/ShowWarehouse', $this->buildShowPayload($warehouse));
    }

    // ── Edit ───────────────────────────────────────────────────

    public function edit($id)
    {
        $warehouse = Warehouse::with('city')->findOrFail($id);
        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/warehouse/ShowWarehouse', array_merge(
            $this->buildShowPayload($warehouse),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'city_id'        => 'nullable|exists:cities,id',
            'type'           => 'nullable|in:Utama,Cabang,Transit,Sementara',
            'address'        => 'nullable|string',
            'pic'            => 'nullable|string|max:255',
            'pic_phone'      => 'nullable|string|max:20',
            'capacity_max'   => 'nullable|numeric|min:0',
            'min_stock'      => 'nullable|numeric|min:0',
            'price_estimate' => 'nullable|numeric|min:0',
            'operating_cost' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            Warehouse::create(array_merge($validated, [
                'code'      => Warehouse::generateCode(),
                'is_active' => true,
            ]));
        });

        return redirect()->route('master-data.warehouse.index')
            ->with('success', 'Warehouse berhasil ditambahkan.');
    }

    // ── Update ─────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'city_id'        => 'nullable|exists:cities,id',
            'type'           => 'nullable|in:Utama,Cabang,Transit,Sementara',
            'address'        => 'nullable|string',
            'pic'            => 'nullable|string|max:255',
            'pic_phone'      => 'nullable|string|max:20',
            'capacity_max'   => 'nullable|numeric|min:0',
            'min_stock'      => 'nullable|numeric|min:0',
            'price_estimate' => 'nullable|numeric|min:0',
            'operating_cost' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        DB::transaction(fn() => $warehouse->update($validated));

        return redirect()->route('master-data.warehouse.index')
            ->with('success', 'Warehouse berhasil diperbarui.');
    }

    // ── Destroy ────────────────────────────────────────────────

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        DB::transaction(fn() => $warehouse->delete());

        return redirect()->back()->with('success', 'Warehouse berhasil dihapus.');
    }

    // ── Toggle Status ──────────────────────────────────────────

    public function toggleStatus(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'inactive_reason' => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        $warehouse->update([
            'is_active'       => !$warehouse->is_active,
            'inactive_reason' => $warehouse->is_active ? ($request->inactive_reason ?? null) : null,
            'notes'           => $warehouse->is_active ? ($request->notes ?? null) : null,
        ]);

        return redirect()->back()->with(
            'success',
            $warehouse->is_active
                ? 'Warehouse berhasil diaktifkan.'
                : 'Warehouse berhasil dinonaktifkan.'
        );
    }

    // ── Transfer Stock ─────────────────────────────────────────

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'volume'            => 'required|numeric|min:0.01',
            'notes'             => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            // TODO: implement stock transfer logic
        });

        return redirect()->back()->with('success', 'Transfer stok berhasil dicatat.');
    }

    // ── Helpers ────────────────────────────────────────────────

    private function buildShowPayload(Warehouse $warehouse): array
    {
        $currentStock  = 32500;
        $capacityMax   = (float) $warehouse->capacity_max ?: 50000;
        $occupancy     = $capacityMax > 0 ? round(($currentStock / $capacityMax) * 100) : 0;
        $stockValue    = $currentStock * ((float) $warehouse->price_estimate ?: 4400);
        $minStock      = (float) $warehouse->min_stock ?: 5000;

        $analyticsChart = collect([
            ['label' => 'Jan', 'stock' => 28000, 'volume_in' => 8000,  'value' => 123200000],
            ['label' => 'Feb', 'stock' => 25000, 'volume_in' => 5000,  'value' => 110000000],
            ['label' => 'Mar', 'stock' => 22000, 'volume_in' => 3000,  'value' => 96800000],
            ['label' => 'Apr', 'stock' => 27000, 'volume_in' => 7000,  'value' => 118800000],
            ['label' => 'Mei', 'stock' => 35000, 'volume_in' => 12000, 'value' => 154000000],
            ['label' => 'Jun', 'stock' => 40000, 'volume_in' => 32000, 'value' => 176000000],
            ['label' => 'Jul', 'stock' => 38000, 'volume_in' => 18000, 'value' => 167200000],
            ['label' => 'Agu', 'stock' => 42000, 'volume_in' => 28000, 'value' => 184800000],
            ['label' => 'Sep', 'stock' => 39000, 'volume_in' => 30000, 'value' => 171600000],
            ['label' => 'Okt', 'stock' => 33000, 'volume_in' => 10000, 'value' => 145200000],
            ['label' => 'Nov', 'stock' => 36000, 'volume_in' => 15000, 'value' => 158400000],
            ['label' => 'Des', 'stock' => 32500, 'volume_in' => 38000, 'value' => 143000000],
        ]);

        $activityHistory = [
            [
                'tab'  => 'Barang Masuk',
                'data' => [
                    ['id' => '1', 'document_number' => 'BM-2024-001', 'date' => '28 Jun 2026', 'party' => 'Sumber Jaya', 'party_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '2', 'document_number' => 'BM-2024-002', 'date' => '28 Jun 2026', 'party' => 'Sumber Jaya', 'party_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Hutang'],
                    ['id' => '3', 'document_number' => 'BM-2024-003', 'date' => '28 Jun 2026', 'party' => 'Sumber Jaya', 'party_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '4', 'document_number' => 'BM-2024-004', 'date' => '28 Jun 2026', 'party' => 'Sumber Jaya', 'party_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '5', 'document_number' => 'BM-2024-005', 'date' => '28 Jun 2026', 'party' => 'Sumber Jaya', 'party_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                ],
            ],
            ['tab' => 'Barang Keluar', 'data' => []],
            ['tab' => 'Transfer',      'data' => []],
            ['tab' => 'Opname',        'data' => []],
        ];

        $activeSuppliers = [
            ['initials' => 'SJ', 'name' => 'Sumber Jaya',       'email' => 'sumberjaya@gmail.com'],
            ['initials' => 'SJ', 'name' => 'Sumber Jaya',       'email' => 'sumberjaya@gmail.com'],
        ];
        $activeBuyers = [
            ['initials' => 'PB', 'name' => 'PT Bioenergi Nusantara', 'email' => 'bioenergi@email.com'],
            ['initials' => 'PB', 'name' => 'PT Bioenergi Nusantara', 'email' => 'bioenergi@email.com'],
        ];

        $activityLogs = [
            ['id' => '1', 'message' => 'Transfer 3.000 kg ke Warehouse Barat (TS-2024-013)', 'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'warning'],
            ['id' => '2', 'message' => 'Barang masuk 1.200 kg dari Sumber Jaya (BM-2024-008)', 'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'success'],
            ['id' => '3', 'message' => 'Transfer keluar 2.500 kg ke Warehouse Selatan (TS-2024-012)', 'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'info'],
        ];

        return [
            'warehouse' => $this->formatWarehouse($warehouse),

            'stats' => [
                'current_stock'      => number_format($currentStock, 0, ',', '.'),
                'stock_label'        => round($occupancy) . '% terisi',
                'capacity_max'       => number_format($capacityMax, 0, ',', '.'),
                'capacity_max_sub'   => 'kapasitas total',
                'available'          => number_format($capacityMax - $currentStock, 0, ',', '.'),
                'available_sub'      => 'bisa diisi lagi',
                'stock_value'        => 'Rp ' . number_format($stockValue, 0, ',', '.'),
                'stock_value_sub'    => 'estimasi nilai',
                'occupancy'          => $occupancy,
                'occupancy_label'    => $occupancy >= 80 ? 'Penuh' : ($occupancy >= 50 ? 'Normal' : 'Rendah'),
                'min_stock'          => number_format($minStock, 0, ',', '.'),
            ],

            'analyticsChart'  => $analyticsChart,
            'activityHistory' => $activityHistory,
            'activeSuppliers' => $activeSuppliers,
            'activeBuyers'    => $activeBuyers,
            'activityLogs'    => $activityLogs,

            'allWarehouses' => Warehouse::active()
                ->where('id', '!=', $warehouse->id)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn($g) => ['id' => $g->id, 'label' => "{$g->name} ({$g->code})"]),

            'toggleUrl' => route('master-data.warehouse.toggle-status', $warehouse->id),
            'editUrl'   => route('master-data.warehouse.edit', $warehouse->id),
        ];
    }

    private function formatWarehouse(Warehouse $g): array
    {
        $capacity  = (float) $g->capacity_max;
        $stock     = (float) ($g->current_stock ?? 0);
        $occupancy = $capacity > 0 ? round(($stock / $capacity) * 100) : 0;

        return [
            'id'             => $g->id,
            'code'           => $g->code,
            'name'           => $g->name,
            'city_id'        => $g->city_id,
            'city_name'      => $g->city?->name,
            'type'           => $g->type,
            'address'        => $g->address,
            'pic'            => $g->pic,
            'pic_phone'      => $g->pic_phone,
            'capacity_max'   => $g->capacity_max,
            'min_stock'      => $g->min_stock,
            'price_estimate' => $g->price_estimate,
            'operating_cost' => $g->operating_cost,
            'is_active'      => $g->is_active,
            'inactive_reason' => $g->inactive_reason,
            'notes'          => $g->notes,
            'current_stock'  => $stock,
            'occupancy'      => $occupancy,
        ];
    }
}
