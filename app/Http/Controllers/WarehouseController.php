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
        $sortable = ['nama', 'kode', 'kapasitas_maks', 'biaya_operasional', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $warehouses = Warehouse::with('city')
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%")
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
            ->when($request->tipe, fn($q, $tipe) => $q->where('tipe', $tipe))
            ->when($request->city_id, fn($q, $cityId) => $q->where('city_id', $cityId))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 12))
            ->withQueryString()
            ->through(fn($g) => $this->formatWarehouse($g));

        $stats = [
            'total'    => Warehouse::count(),
            'aktif'    => Warehouse::active()->count(),
            'nonaktif' => Warehouse::inactive()->count(),
        ];

        $cities = City::whereIn('id', Warehouse::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('nama')
            ->get(['id', 'nama as name']);

        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/warehouse/ListWarehouse', [
            'warehouses'   => $warehouses,
            'stats'     => $stats,
            'cities'    => $cities,
            'allCities' => $allCities,
            'filters'   => $request->only(['search', 'perPage', 'status', 'tipe', 'city_id', 'sort', 'direction']),
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
        $warehouse    = Warehouse::with('city')->findOrFail($id);
        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/warehouse/ShowWarehouse', array_merge(
            $this->buildShowPayload($warehouse),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'               => 'required|string|max:255',
            'city_id'            => 'nullable|exists:cities,id',
            'tipe'               => 'nullable|in:Utama,Cabang,Transit,Sementara',
            'alamat'             => 'nullable|string',
            'pic'                => 'nullable|string|max:255',
            'telepon_pic'        => 'nullable|string|max:20',
            'kapasitas_maks'     => 'nullable|numeric|min:0',
            'stok_minimum'       => 'nullable|numeric|min:0',
            'harga_estimasi'     => 'nullable|numeric|min:0',
            'biaya_operasional'  => 'nullable|numeric|min:0',
            'catatan'            => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            Warehouse::create(array_merge($validated, [
                'kode'      => Warehouse::generateKode(),
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
            'nama'               => 'sometimes|required|string|max:255',
            'city_id'            => 'nullable|exists:cities,id',
            'tipe'               => 'nullable|in:Utama,Cabang,Transit,Sementara',
            'alamat'             => 'nullable|string',
            'pic'                => 'nullable|string|max:255',
            'telepon_pic'        => 'nullable|string|max:20',
            'kapasitas_maks'     => 'nullable|numeric|min:0',
            'stok_minimum'       => 'nullable|numeric|min:0',
            'harga_estimasi'     => 'nullable|numeric|min:0',
            'biaya_operasional'  => 'nullable|numeric|min:0',
            'catatan'            => 'nullable|string',
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
            'alasan_nonaktif' => 'nullable|string|max:255',
            'catatan'         => 'nullable|string',
        ]);

        $warehouse->update([
            'is_active'       => !$warehouse->is_active,
            'alasan_nonaktif' => $warehouse->is_active ? ($request->alasan_nonaktif ?? null) : null,
            'catatan'         => $warehouse->is_active ? ($request->catatan ?? null) : null,
        ]);

        return redirect()->back()->with(
            'success',
            $warehouse->is_active
                ? 'Warehouse berhasil diaktifkan.'
                : 'Warehouse berhasil dinonaktifkan.'
        );
    }

    // ── Transfer Stok ──────────────────────────────────────────

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'dari_warehouse_id' => 'required|exists:warehouses,id',
            'ke_warehouse_id'   => 'required|exists:warehouses,id|different:dari_warehouse_id',
            'volume'         => 'required|numeric|min:0.01',
            'catatan'        => 'nullable|string|max:500',
        ]);

        // TODO: implementasi logika transfer stok
        // Contoh: kurangi stok dari warehouse asal, tambah ke warehouse tujuan
        // Buat record di tabel transfer_stok

        DB::transaction(function () use ($validated) {
            // TransferStok::create($validated);
        });

        return redirect()->back()->with('success', 'Transfer stok berhasil dicatat.');
    }

    // ── Helpers ────────────────────────────────────────────────

    private function buildShowPayload(Warehouse $warehouse): array
    {
        // ── Static Stats (ganti dengan query real) ─────────────
        $stokSaatIni    = 32500;
        $kapasitasMaks  = (float) $warehouse->kapasitas_maks ?: 50000;
        $utilisasi      = $kapasitasMaks > 0 ? round(($stokSaatIni / $kapasitasMaks) * 100) : 0;
        $nilaiStok      = $stokSaatIni * ((float) $warehouse->harga_estimasi ?: 4400);
        $stokMinimum    = (float) $warehouse->stok_minimum ?: 5000;

        // ── Analitik Chart Dummy (12 bulan) ─────────────────────
        $analyticsChart = collect([
            ['label' => 'Jan', 'stok' => 28000, 'volume_masuk' => 8000,  'nilai' => 123200000],
            ['label' => 'Feb', 'stok' => 25000, 'volume_masuk' => 5000,  'nilai' => 110000000],
            ['label' => 'Mar', 'stok' => 22000, 'volume_masuk' => 3000,  'nilai' => 96800000],
            ['label' => 'Apr', 'stok' => 27000, 'volume_masuk' => 7000,  'nilai' => 118800000],
            ['label' => 'Mei', 'stok' => 35000, 'volume_masuk' => 12000, 'nilai' => 154000000],
            ['label' => 'Jun', 'stok' => 40000, 'volume_masuk' => 32000, 'nilai' => 176000000],
            ['label' => 'Jul', 'stok' => 38000, 'volume_masuk' => 18000, 'nilai' => 167200000],
            ['label' => 'Agu', 'stok' => 42000, 'volume_masuk' => 28000, 'nilai' => 184800000],
            ['label' => 'Sep', 'stok' => 39000, 'volume_masuk' => 30000, 'nilai' => 171600000],
            ['label' => 'Okt', 'stok' => 33000, 'volume_masuk' => 10000, 'nilai' => 145200000],
            ['label' => 'Nov', 'stok' => 36000, 'volume_masuk' => 15000, 'nilai' => 158400000],
            ['label' => 'Des', 'stok' => 32500, 'volume_masuk' => 38000, 'nilai' => 143000000],
        ]);

        // ── Riwayat Aktivitas Dummy ──────────────────────────────
        $riwayatAktivitas = [
            [
                'tab'         => 'Barang Masuk',
                'data'        => [
                    ['id' => '1', 'no_dokumen' => 'BM-2024-001', 'tanggal' => '28 Jun 2026', 'pihak' => 'Sumber Jaya', 'pihak_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '2', 'no_dokumen' => 'BM-2024-002', 'tanggal' => '28 Jun 2026', 'pihak' => 'Sumber Jaya', 'pihak_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Hutang'],
                    ['id' => '3', 'no_dokumen' => 'BM-2024-003', 'tanggal' => '28 Jun 2026', 'pihak' => 'Sumber Jaya', 'pihak_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '4', 'no_dokumen' => 'BM-2024-004', 'tanggal' => '28 Jun 2026', 'pihak' => 'Sumber Jaya', 'pihak_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                    ['id' => '5', 'no_dokumen' => 'BM-2024-005', 'tanggal' => '28 Jun 2026', 'pihak' => 'Sumber Jaya', 'pihak_email' => 'sumberjaya@gmail.com', 'total' => 5400000, 'status' => 'Lunas'],
                ],
            ],
            [
                'tab'  => 'Barang Keluar',
                'data' => [],
            ],
            [
                'tab'  => 'Transfer',
                'data' => [],
            ],
            [
                'tab'  => 'Opname',
                'data' => [],
            ],
        ];

        // ── Supplier & Buyer Aktif ───────────────────────────────
        $supplierAktif = [
            ['inisials' => 'SJ', 'nama' => 'Sumber Jaya',       'email' => 'sumberjaya@gmail.com'],
            ['inisials' => 'SJ', 'nama' => 'Sumber Jaya',       'email' => 'sumberjaya@gmail.com'],
        ];
        $buyerAktif = [
            ['inisials' => 'PB', 'nama' => 'PT Bioenergi Nusantara', 'email' => 'bioenergi@email.com'],
            ['inisials' => 'PB', 'nama' => 'PT Bioenergi Nusantara', 'email' => 'bioenergi@email.com'],
        ];

        // ── Activity Logs Dummy ──────────────────────────────────
        $activityLogs = [
            ['id' => '1', 'message' => 'Transfer 3.000 kg ke Warehouse Barat (TS-2024-013)', 'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'warning'],
            ['id' => '2', 'message' => 'Barang masuk 1.200 kg dari Sumber Jaya (BM-2024-008)', 'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'success'],
            ['id' => '3', 'message' => 'Transfer keluar 2.500 kg ke Warehouse Selatan (TS-2024-012)', 'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'info'],
        ];

        return [
            'warehouse' => $this->formatWarehouse($warehouse),

            'stats' => [
                'stok_saat_ini'    => number_format($stokSaatIni, 0, ',', '.'),
                'stok_label'       => round($utilisasi) . '% terisi',
                'kapasitas_maks'   => number_format($kapasitasMaks, 0, ',', '.'),
                'kapasitas_maks_sub' => 'kapasitas total',
                'tersedia'         => number_format($kapasitasMaks - $stokSaatIni, 0, ',', '.'),
                'tersedia_sub'     => 'bisa diisi lagi',
                'nilai_stok'       => 'Rp ' . number_format($nilaiStok, 0, ',', '.'),
                'nilai_sub'        => 'estimasi nilai',
                'utilisasi'        => $utilisasi,
                'utilisasi_label'  => $utilisasi >= 80 ? 'Penuh' : ($utilisasi >= 50 ? 'Normal' : 'Rendah'),
                'stok_minimum'     => number_format($stokMinimum, 0, ',', '.'),
            ],

            'analyticsChart'   => $analyticsChart,
            'riwayatAktivitas' => $riwayatAktivitas,
            'supplierAktif'    => $supplierAktif,
            'buyerAktif'       => $buyerAktif,
            'activityLogs'     => $activityLogs,

            'allWarehouses' => Warehouse::active()
                ->where('id', '!=', $warehouse->id)
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode'])
                ->map(fn($g) => ['id' => $g->id, 'label' => "{$g->nama} ({$g->kode})"]),

            'toggleUrl' => route('master-data.warehouse.toggle-status', $warehouse->id),
            'editUrl'   => route('master-data.warehouse.edit', $warehouse->id),
        ];
    }

    private function formatWarehouse(Warehouse $g): array
    {
        $kapasitas = (float) $g->kapasitas_maks;
        $stok      = (float) ($g->stok_saat_ini ?? 0);
        $utilisasi = $kapasitas > 0 ? round(($stok / $kapasitas) * 100) : 0;

        return [
            'id'                 => $g->id,
            'kode'               => $g->kode,
            'nama'               => $g->nama,
            'city_id'            => $g->city_id,
            'city_name'          => $g->city?->nama,
            'tipe'               => $g->tipe,
            'alamat'             => $g->alamat,
            'pic'                => $g->pic,
            'telepon_pic'        => $g->telepon_pic,
            'kapasitas_maks'     => $g->kapasitas_maks,
            'stok_minimum'       => $g->stok_minimum,
            'harga_estimasi'     => $g->harga_estimasi,
            'biaya_operasional'  => $g->biaya_operasional,
            'is_active'          => $g->is_active,
            'alasan_nonaktif'    => $g->alasan_nonaktif,
            'catatan'            => $g->catatan,
            'stok_saat_ini'      => $stok,
            'utilisasi'          => $utilisasi,
        ];
    }
}