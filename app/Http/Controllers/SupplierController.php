<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Supplier;
use App\Services\AmazonServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SupplierController extends Controller
{
    // ── Index ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sortable = ['nama', 'kode', 'harga_beli_default', 'kapasitas_per_bulan', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $suppliers = Supplier::with('city')
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%")
                )
            )
            ->when($request->status, function ($q, $status) {
                match ($status) {
                    'active'   => $q->where('is_active', true),
                    'inactive' => $q->where('is_active', false),
                    default    => null,
                };
            })
            ->when(
                $request->termin,
                fn($q, $termin) =>
                $q->where('termin', $termin)
            )
            ->when(
                $request->city_id,
                fn($q, $cityId) =>
                $q->where('city_id', $cityId)
            )
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn($s) => $this->formatSupplier($s));

        $stats = [
            'total'    => Supplier::count(),
            'aktif'    => Supplier::active()->count(),
            'nonaktif' => Supplier::inactive()->count(),
        ];

        // Hanya kota yang dipakai supplier (untuk filter)
        $cities = City::whereIn('id', Supplier::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('nama')
            ->get(['id', 'nama as name']);

        // Semua kota (untuk dropdown form)
        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/supplier/ListSupplier', [
            'suppliers' => $suppliers,
            'stats'     => $stats,
            'cities'    => $cities,
            'allCities' => $allCities,
            'filters'   => $request->only(['search', 'perPage', 'status', 'termin', 'city_id', 'sort', 'direction']),
        ]);
    }

    // ── Show ───────────────────────────────────────────────────────

    // public function show(Request $request, $id)
    // {
    //     $supplier = Supplier::with('city')->findOrFail($id);

    //     // ── Stat cards ─────────────────────────────────────────────
    //     $transactions = \App\Models\BarangMasuk::where('supplier_id', $supplier->id);

    //     $totalPembelian    = (clone $transactions)->sum('total_harga');
    //     $jumlahTransaksi   = (clone $transactions)->count();
    //     $totalVolume       = (clone $transactions)->sum('volume');
    //     $hargaRataRata     = $jumlahTransaksi > 0
    //         ? (clone $transactions)->avg('harga_beli')
    //         : 0;

    //     // Hutang aktif dari tabel AP (accounts payable)
    //     $hutangAktif = \App\Models\HutangAP::where('supplier_id', $supplier->id)
    //         ->where('status', '!=', 'lunas')
    //         ->sum('sisa_hutang') ?? 0;

    //     // Rating (rata-rata dari tabel rating jika ada, atau dummy)
    //     $rating = \App\Models\SupplierRating::where('supplier_id', $supplier->id)->avg('nilai') ?? 0;
    //     $ratingLabel = match (true) {
    //         $rating >= 4.5 => 'Sangat Bagus',
    //         $rating >= 3.5 => 'Bagus',
    //         $rating >= 2.5 => 'Cukup',
    //         $rating >= 1.5 => 'Kurang',
    //         default        => 'Buruk',
    //     };

    //     // ── Volume chart (12 bulan terakhir) ───────────────────────
    //     $volumeChart = collect(range(11, 0))->map(function ($monthsAgo) use ($supplier) {
    //         $date  = now()->subMonths($monthsAgo);
    //         $volume = \App\Models\BarangMasuk::where('supplier_id', $supplier->id)
    //             ->whereYear('tanggal', $date->year)
    //             ->whereMonth('tanggal', $date->month)
    //             ->sum('volume');

    //         return [
    //             'label'  => $date->translatedFormat('M'),
    //             'volume' => (float) $volume,
    //         ];
    //     })->values();

    //     // ── Transaksi paginated ────────────────────────────────────
    //     $txPaginated = \App\Models\BarangMasuk::with('gudang')
    //         ->where('supplier_id', $supplier->id)
    //         ->orderByDesc('tanggal')
    //         ->paginate(5)
    //         ->through(fn($bm) => [
    //             'id'         => $bm->id,
    //             'no_dokumen' => $bm->no_dokumen,
    //             'tanggal'    => \Carbon\Carbon::parse($bm->tanggal)->translatedFormat('d M Y'),
    //             'gudang'     => $bm->gudang?->nama ?? '—',
    //             'volume'     => (float) $bm->volume,
    //             'harga'      => (float) $bm->harga_beli,
    //             'total'      => (float) $bm->total_harga,
    //             'status'     => $bm->status_pembayaran ?? 'Lunas',
    //         ]);

    //     // ── Ringkasan hutang ───────────────────────────────────────
    //     $totalDibayar  = $totalPembelian - $hutangAktif;
    //     $persenLunas   = $totalPembelian > 0
    //         ? round(($totalDibayar / $totalPembelian) * 100)
    //         : 100;

    //     // ── Distribusi gudang ──────────────────────────────────────
    //     $gudangDistribusi = \App\Models\BarangMasuk::with('gudang')
    //         ->where('supplier_id', $supplier->id)
    //         ->selectRaw('gudang_id, SUM(volume) as total_volume')
    //         ->groupBy('gudang_id')
    //         ->get()
    //         ->map(fn($row) => [
    //             'nama'   => $row->gudang?->nama ?? 'Unknown',
    //             'persen' => 0, // dihitung di bawah
    //             '_vol'   => (float) $row->total_volume,
    //         ]);

    //     $totalVol = $gudangDistribusi->sum('_vol');
    //     $gudangDistribusi = $gudangDistribusi->map(fn($g) => [
    //         'nama'   => $g['nama'],
    //         'persen' => $totalVol > 0 ? round(($g['_vol'] / $totalVol) * 100) : 0,
    //     ])->values();

    //     // ── Activity logs ──────────────────────────────────────────
    //     $activityLogs = \App\Models\ActivityLog::where('subject_type', Supplier::class)
    //         ->where('subject_id', $supplier->id)
    //         ->orderByDesc('created_at')
    //         ->limit(10)
    //         ->get()
    //         ->map(fn($log) => [
    //             'id'      => $log->id,
    //             'message' => $log->description,
    //             'user'    => $log->causer?->name ?? 'System',
    //             'waktu'   => \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y H:i'),
    //             'type'    => $log->properties['type'] ?? 'info',
    //         ]);

    //     return Inertia::render('masterData/supplier/ShowSupplier', [
    //         'supplier' => $this->formatSupplier($supplier),

    //         'stats' => [
    //             'total_pembelian'     => 'Rp ' . number_format($totalPembelian, 0, ',', '.'),
    //             'total_pembelian_sub' => $jumlahTransaksi . ' transaksi',
    //             'hutang_aktif'        => 'Rp ' . number_format($hutangAktif, 0, ',', '.'),
    //             'hutang_aktif_sub'    => $hutangAktif > 0 ? 'Ada hutang' : 'Tidak ada hutang',
    //             'harga_rata_rata'     => number_format($hargaRataRata, 0, ',', '.'),
    //             'harga_rata_rata_sub' => 'per kg (Rp)',
    //             'total_volume'        => number_format($totalVolume, 0, ',', '.'),
    //             'total_volume_sub'    => 'kg diterima',
    //             'rating'              => number_format($rating, 1),
    //             'rating_sub'          => $ratingLabel,
    //         ],

    //         'volumeChart'  => $volumeChart,
    //         'transactions' => $txPaginated,

    //         'ringkasanHutang' => [
    //             'total_pembelian' => 'Rp ' . number_format($totalPembelian, 0, ',', '.'),
    //             'total_dibayar'   => 'Rp ' . number_format($totalDibayar, 0, ',', '.'),
    //             'hutang_aktif'    => 'Rp ' . number_format($hutangAktif, 0, ',', '.'),
    //             'persen_lunas'    => $persenLunas,
    //         ],

    //         'gudangDistribusi' => $gudangDistribusi,
    //         'activityLogs'     => $activityLogs,

    //         'toggleUrl' => route('master-data.supplier.toggle-status', $supplier->id),
    //         'editUrl'   => route('master-data.supplier.edit', $supplier->id),
    //     ]);
    // }

    public function show(Request $request, $id)
    {
        $supplier = Supplier::with('city')->findOrFail($id);

        return Inertia::render('masterData/supplier/ShowSupplier', $this->buildShowPayload($supplier));
    }

    // ── Edit ───────────────────────────────────────────────────

    public function edit($id)
    {
        $supplier  = Supplier::with('city')->findOrFail($id);
        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/supplier/ShowSupplier', array_merge(
            $this->buildShowPayload($supplier),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    // ── Show payload builder ───────────────────────────────────

    private function buildShowPayload(Supplier $supplier): array
    {
        // ── Static Stats ────────────────────────────────────────────
        $totalPembelian  = 125000000;
        $jumlahTransaksi = 24;
        $totalVolume     = 18500;
        $hargaRataRata   = 6800;
        $hutangAktif     = 15000000;
        $rating          = 4.3;

        $ratingLabel = match (true) {
            $rating >= 4.5 => 'Sangat Bagus',
            $rating >= 3.5 => 'Bagus',
            $rating >= 2.5 => 'Cukup',
            $rating >= 1.5 => 'Kurang',
            default        => 'Buruk',
        };

        // ── Volume Chart Dummy (12 bulan) ───────────────────────────
        $volumeChart = collect([
            ['label' => 'Mei', 'volume' => 1200],
            ['label' => 'Jun', 'volume' => 1500],
            ['label' => 'Jul', 'volume' => 1800],
            ['label' => 'Agu', 'volume' => 1700],
            ['label' => 'Sep', 'volume' => 2000],
            ['label' => 'Okt', 'volume' => 2200],
            ['label' => 'Nov', 'volume' => 2100],
            ['label' => 'Des', 'volume' => 2500],
            ['label' => 'Jan', 'volume' => 2300],
            ['label' => 'Feb', 'volume' => 1900],
            ['label' => 'Mar', 'volume' => 2100],
            ['label' => 'Apr', 'volume' => 2400],
        ]);

        // ── Dummy Transaksi ─────────────────────────────────────────
        $transactions = [
            [
                'id' => 1,
                'no_dokumen' => 'TRX-001',
                'tanggal' => '01 Jan 2026',
                'gudang' => 'Gudang A',
                'volume' => 1000,
                'harga' => 6500,
                'total' => 6500000,
                'status' => 'Lunas',
            ],
            [
                'id' => 2,
                'no_dokumen' => 'TRX-002',
                'tanggal' => '05 Jan 2026',
                'gudang' => 'Gudang B',
                'volume' => 800,
                'harga' => 7000,
                'total' => 5600000,
                'status' => 'Hutang',
            ],
            [
                'id' => 3,
                'no_dokumen' => 'TRX-003',
                'tanggal' => '10 Jan 2026',
                'gudang' => 'Gudang A',
                'volume' => 1200,
                'harga' => 6800,
                'total' => 8160000,
                'status' => 'Pending',
            ],
        ];

        // Simulasi pagination
        $txPaginated = [
            'data' => $transactions,
            'current_page' => 1,
            'last_page' => 1,
            'total' => count($transactions),
        ];

        // ── Ringkasan Hutang ────────────────────────────────────────
        $totalDibayar = $totalPembelian - $hutangAktif;
        $persenLunas  = round(($totalDibayar / $totalPembelian) * 100);

        // ── Gudang Distribusi Dummy ─────────────────────────────────
        $gudangDistribusi = [
            ['nama' => 'Gudang A', 'persen' => 60],
            ['nama' => 'Gudang B', 'persen' => 30],
            ['nama' => 'Gudang C', 'persen' => 10],
        ];

        // ── Activity Logs Dummy ─────────────────────────────────────
        $activityLogs = [
            [
                'id' => 1,
                'message' => 'Supplier dibuat',
                'user' => 'Admin',
                'waktu' => now()->subDays(2)->translatedFormat('d M Y H:i'),
                'type' => 'success',
            ],
            [
                'id' => 2,
                'message' => 'Update data supplier',
                'user' => 'Admin',
                'waktu' => now()->subDay()->translatedFormat('d M Y H:i'),
                'type' => 'info',
            ],
            [
                'id' => 3,
                'message' => 'Supplier dinonaktifkan',
                'user' => 'System',
                'waktu' => now()->subHours(5)->translatedFormat('d M Y H:i'),
                'type' => 'warning',
            ],
        ];

        return [
            'supplier' => $this->formatSupplier($supplier),

            'stats' => [
                'total_pembelian'     => 'Rp ' . number_format($totalPembelian, 0, ',', '.'),
                'total_pembelian_sub' => $jumlahTransaksi . ' transaksi',
                'hutang_aktif'        => 'Rp ' . number_format($hutangAktif, 0, ',', '.'),
                'hutang_aktif_sub'    => 'Ada hutang',
                'harga_rata_rata'     => number_format($hargaRataRata, 0, ',', '.'),
                'harga_rata_rata_sub' => 'per kg (Rp)',
                'total_volume'        => number_format($totalVolume, 0, ',', '.'),
                'total_volume_sub'    => 'kg diterima',
                'rating'              => number_format($rating, 1),
                'rating_sub'          => $ratingLabel,
            ],

            'volumeChart'  => $volumeChart,
            'transactions' => $txPaginated,

            'ringkasanHutang' => [
                'total_pembelian' => 'Rp ' . number_format($totalPembelian, 0, ',', '.'),
                'total_dibayar'   => 'Rp ' . number_format($totalDibayar, 0, ',', '.'),
                'hutang_aktif'    => 'Rp ' . number_format($hutangAktif, 0, ',', '.'),
                'persen_lunas'    => $persenLunas,
            ],

            'gudangDistribusi' => $gudangDistribusi,
            'activityLogs'     => $activityLogs,

            'toggleUrl' => route('master-data.supplier.toggle-status', $supplier->id),
            'editUrl'   => route('master-data.supplier.edit', $supplier->id),
        ];
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'                => 'required|string|max:255',
            'telepon'             => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:255',
            'city_id'             => 'nullable|exists:cities,id',
            'kapasitas_per_bulan' => 'nullable|numeric|min:0',
            'harga_beli_default'  => 'nullable|numeric|min:0',
            'bank'                => 'nullable|string|max:100',
            'no_rekening'         => 'nullable|string|max:50',
            'atas_nama'           => 'nullable|string|max:255',
            'npwp'                => 'nullable|string|max:30',
            'pic'                 => 'nullable|string|max:255',
            'alamat'              => 'nullable|string',
            'catatan'             => 'nullable|string',
            'termin'              => 'required|in:cash,tempo',
            'termin_hari'         => 'nullable|integer|min:1|required_if:termin,tempo',
            'foto'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        [$fotoPath, $fotoDisk] = $this->uploadFoto($request);

        DB::transaction(function () use ($validated, $fotoPath, $fotoDisk) {
            Supplier::create(array_merge($validated, [
                'kode'      => Supplier::generateKode(),
                'is_active' => true,
                'foto_path' => $fotoPath,
                'foto_disk' => $fotoDisk,
            ]));
        });

        return redirect()->route('master-data.supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    // ── Update ─────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'nama'                => 'sometimes|required|string|max:255',
            'telepon'             => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:255',
            'city_id'             => 'nullable|exists:cities,id',
            'kapasitas_per_bulan' => 'nullable|numeric|min:0',
            'harga_beli_default'  => 'nullable|numeric|min:0',
            'bank'                => 'nullable|string|max:100',
            'no_rekening'         => 'nullable|string|max:50',
            'atas_nama'           => 'nullable|string|max:255',
            'npwp'                => 'nullable|string|max:30',
            'pic'                 => 'nullable|string|max:255',
            'alamat'              => 'nullable|string',
            'catatan'             => 'nullable|string',
            'termin'              => 'sometimes|required|in:cash,tempo',
            'termin_hari'         => 'nullable|integer|min:1',
            'foto'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            [$validated['foto_path'], $validated['foto_disk']] = $this->uploadFoto($request);
        }

        DB::transaction(fn() => $supplier->update($validated));

        return redirect()->route('master-data.supplier.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    // ── Destroy ────────────────────────────────────────────────

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        DB::transaction(fn() => $supplier->delete());

        return redirect()->back()
            ->with('success', 'Supplier berhasil dihapus.');
    }

    // ── Toggle Status ──────────────────────────────────────────

    public function toggleStatus(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'alasan_nonaktif' => 'nullable|string|max:255',
            'catatan'         => 'nullable|string',
        ]);

        $supplier->update([
            'is_active'       => ! $supplier->is_active,
            'alasan_nonaktif' => $supplier->is_active ? ($request->alasan_nonaktif ?? null) : null,
            'catatan'         => $supplier->is_active ? ($request->catatan ?? null) : null,
        ]);

        return redirect()->back()->with(
            'success',
            $supplier->is_active
                ? 'Supplier berhasil diaktifkan.'
                : 'Supplier berhasil dinonaktifkan.'
        );
    }

    // ── Helpers ────────────────────────────────────────────────

    private function uploadFoto(Request $request): array
    {
        if (! $request->hasFile('foto')) {
            return [null, null];
        }

        $upload = AmazonServerService::upload(
            'suppliers/photos',
            $request->file('foto'),
            Str::uuid()->toString()
        );

        return [$upload['path'] ?? null, $upload['storage'] ?? config('filesystems.default')];
    }

    private function formatSupplier(Supplier $s): array
    {
        return [
            'id'                  => $s->id,
            'kode'                => $s->kode,
            'nama'                => $s->nama,
            'telepon'             => $s->telepon,
            'email'               => $s->email,
            'city_id'             => $s->city_id,
            'city_name'           => $s->city?->nama,
            'kapasitas_per_bulan' => $s->kapasitas_per_bulan,
            'harga_beli_default'  => $s->harga_beli_default,
            'bank'                => $s->bank,
            'no_rekening'         => $s->no_rekening,
            'atas_nama'           => $s->atas_nama,
            'npwp'                => $s->npwp,
            'pic'                 => $s->pic,
            'alamat'              => $s->alamat,
            'catatan'             => $s->catatan,
            'termin'              => $s->termin,
            'termin_hari'         => $s->termin_hari,
            'termin_label'        => $s->termin_label,
            'is_active'           => $s->is_active,
            'alasan_nonaktif'     => $s->alasan_nonaktif,
            'foto_url'            => $s->foto_url,
            'inisials'            => $s->inisials,
        ];
    }
}
