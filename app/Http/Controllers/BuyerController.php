<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\City;
use App\Services\AmazonServerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BuyerController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['nama', 'kode', 'harga_jual_default', 'total_penjualan', 'total_piutang', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $buyers = Buyer::with('city')
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
            ->when($request->tipe, fn($q, $tipe) => $q->where('tipe', $tipe))
            ->when($request->city_id, fn($q, $cityId) => $q->where('city_id', $cityId))
            ->orderBy($sortCol, $sortDir)
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn($b) => $this->formatBuyer($b));

        $stats = [
            'total'    => Buyer::count(),
            'aktif'    => Buyer::active()->count(),
            'nonaktif' => Buyer::inactive()->count(),
        ];

        $cities = City::whereIn('id', Buyer::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('nama')
            ->get(['id', 'nama as name']);

        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/buyer/ListBuyer', [
            'buyers'    => $buyers,
            'stats'     => $stats,
            'cities'    => $cities,
            'allCities' => $allCities,
            'filters'   => $request->only(['search', 'perPage', 'status', 'tipe', 'city_id', 'sort', 'direction']),
        ]);
    }

    public function show(Request $request, $id)
    {
        $buyer = Buyer::with('city')->findOrFail($id);

        return Inertia::render('masterData/buyer/ShowBuyer', $this->buildShowPayload($buyer));
    }

    public function edit($id)
    {
        $buyer     = Buyer::with('city')->findOrFail($id);
        $allCities = City::orderBy('nama')->get(['id', 'nama as name']);

        return Inertia::render('masterData/buyer/ShowBuyer', array_merge(
            $this->buildShowPayload($buyer),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    private function buildShowPayload(Buyer $buyer): array
    {
        // ── Static Stats (ganti dengan query real nanti) ─────────────
        $totalPenjualan  = 520000000;
        $jumlahTransaksi = 24;
        $totalVolume     = 118181;
        $hargaRataRata   = 4400;
        $piutangAktif    = 22000000;
        $rating          = 4.8;

        $ratingLabel = match (true) {
            $rating >= 4.5 => 'Bagus',
            $rating >= 3.5 => 'Cukup Bagus',
            $rating >= 2.5 => 'Cukup',
            default        => 'Kurang',
        };

        // ── Volume Chart Dummy (12 bulan) ────────────────────────────
        $volumeChart = collect([
            ['label' => 'Jan', 'volume' => 8000],
            ['label' => 'Feb', 'volume' => 5000],
            ['label' => 'Mar', 'volume' => 3000],
            ['label' => 'Apr', 'volume' => 7000],
            ['label' => 'Mei', 'volume' => 12000],
            ['label' => 'Jun', 'volume' => 32000],
            ['label' => 'Jul', 'volume' => 18000],
            ['label' => 'Agu', 'volume' => 28000],
            ['label' => 'Sep', 'volume' => 30000],
            ['label' => 'Okt', 'volume' => 10000],
            ['label' => 'Nov', 'volume' => 15000],
            ['label' => 'Des', 'volume' => 38000],
        ]);

        // ── Dummy Transaksi ──────────────────────────────────────────
        $transactions = [
            ['id' => '1', 'no_dokumen' => 'BM-2024-001', 'tanggal' => '28 Jun 2026', 'gudang' => 'Gudang Pusat', 'volume' => 1200, 'harga' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '2', 'no_dokumen' => 'BM-2024-002', 'tanggal' => '28 Jun 2026', 'gudang' => 'Gudang Pusat', 'volume' => 1200, 'harga' => 4500, 'total' => 5400000, 'status' => 'Hutang'],
            ['id' => '3', 'no_dokumen' => 'BM-2024-003', 'tanggal' => '28 Jun 2026', 'gudang' => 'Gudang Pusat', 'volume' => 1200, 'harga' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '4', 'no_dokumen' => 'BM-2024-004', 'tanggal' => '28 Jun 2026', 'gudang' => 'Gudang Pusat', 'volume' => 1200, 'harga' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '5', 'no_dokumen' => 'BM-2024-005', 'tanggal' => '28 Jun 2026', 'gudang' => 'Gudang Pusat', 'volume' => 1200, 'harga' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
        ];

        $txPaginated = [
            'data'         => $transactions,
            'current_page' => 1,
            'last_page'    => 1,
            'total'        => count($transactions),
        ];

        // ── Ringkasan Piutang ────────────────────────────────────────
        $sudahDiterima  = $totalPenjualan - $piutangAktif;
        $persenDiterima = round(($piutangAktif / $totalPenjualan) * 100);

        // ── Produk yang Dibeli Dummy ─────────────────────────────────
        $produkDibeli = [
            ['nama' => 'Minyak Jelantah Grade A', 'persen' => 60],
            ['nama' => 'Minyak Jelantah Grade B', 'persen' => 40],
        ];

        // ── Activity Logs Dummy ──────────────────────────────────────
        $activityLogs = [
            ['id' => '1', 'message' => 'Penjualan BK-042 Rp 22 Jt — Piutang dicatat', 'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'warning'],
            ['id' => '2', 'message' => 'Piutang BK-030 Rp 35.2 Jt dilunasi',          'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'success'],
            ['id' => '3', 'message' => "Buyer dinonaktifkan — Tidak aktif beroperasi",  'user' => 'Admin Kartono', 'waktu' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'danger'],
        ];

        return [
            'buyer' => $this->formatBuyer($buyer),

            'stats' => [
                'total_penjualan'     => 'Rp ' . number_format($totalPenjualan, 0, ',', '.'),
                'total_penjualan_sub' => $jumlahTransaksi . ' transaksi',
                'piutang_aktif'       => 'Rp ' . number_format($piutangAktif, 0, ',', '.'),
                'piutang_aktif_sub'   => $piutangAktif > 0 ? 'Tidak dibayar' : 'Tidak ada piutang',
                'harga_rata_rata'     => number_format($hargaRataRata, 0, ',', '.'),
                'harga_rata_rata_sub' => 'per kg (Rp)',
                'total_volume'        => number_format($totalVolume, 0, ',', '.'),
                'total_volume_sub'    => 'kg terjual',
                'rating'              => number_format($rating, 1),
                'rating_sub'          => $ratingLabel,
            ],

            'volumeChart'  => $volumeChart,
            'transactions' => $txPaginated,

            'ringkasanPiutang' => [
                'total_penjualan' => 'Rp ' . number_format($totalPenjualan, 0, ',', '.'),
                'sudah_diterima'  => 'Rp ' . number_format($sudahDiterima, 0, ',', '.'),
                'piutang_aktif'   => 'Rp ' . number_format($piutangAktif, 0, ',', '.'),
                'persen_diterima' => $persenDiterima,
            ],

            'produkDibeli' => $produkDibeli,
            'activityLogs' => $activityLogs,

            'toggleUrl' => route('master-data.buyer.toggle-status', $buyer->id),
            'editUrl'   => route('master-data.buyer.edit', $buyer->id),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'               => 'required|string|max:255',
            'tipe'               => 'nullable|in:PT,CV,UD,Perorangan',
            'telepon'            => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'city_id'            => 'nullable|exists:cities,id',
            'harga_jual_default' => 'nullable|numeric|min:0',
            'limit_kredit'       => 'nullable|numeric|min:0',
            'termin_hari'        => 'nullable|integer|min:1',
            'pic'                => 'nullable|string|max:255',
            'npwp'               => 'nullable|string|max:30',
            'website'            => 'nullable|string|max:255',
            'alamat'             => 'nullable|string',
            'catatan'            => 'nullable|string',
            'foto'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        [$fotoPath, $fotoDisk] = $this->uploadFoto($request);

        DB::transaction(function () use ($validated, $fotoPath, $fotoDisk) {
            Buyer::create(array_merge($validated, [
                'kode'      => Buyer::generateKode(),
                'is_active' => true,
                'foto_path' => $fotoPath,
                'foto_disk' => $fotoDisk,
            ]));
        });

        return redirect()->route('master-data.buyer.index')
            ->with('success', 'Buyer berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $buyer = Buyer::findOrFail($id);

        $validated = $request->validate([
            'nama'               => 'sometimes|required|string|max:255',
            'tipe'               => 'nullable|in:PT,CV,UD,Perorangan',
            'telepon'            => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'city_id'            => 'nullable|exists:cities,id',
            'harga_jual_default' => 'nullable|numeric|min:0',
            'limit_kredit'       => 'nullable|numeric|min:0',
            'termin_hari'        => 'nullable|integer|min:1',
            'pic'                => 'nullable|string|max:255',
            'npwp'               => 'nullable|string|max:30',
            'website'            => 'nullable|string|max:255',
            'alamat'             => 'nullable|string',
            'catatan'            => 'nullable|string',
            'foto'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            [$validated['foto_path'], $validated['foto_disk']] = $this->uploadFoto($request);
        }

        DB::transaction(fn() => $buyer->update($validated));

        return redirect()->route('master-data.buyer.index')
            ->with('success', 'Buyer berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $buyer = Buyer::findOrFail($id);
        DB::transaction(fn() => $buyer->delete());

        return redirect()->back()->with('success', 'Buyer berhasil dihapus.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $buyer = Buyer::findOrFail($id);

        $request->validate([
            'alasan_nonaktif' => 'nullable|string|max:255',
            'catatan'         => 'nullable|string',
        ]);

        $buyer->update([
            'is_active'       => !$buyer->is_active,
            'alasan_nonaktif' => $buyer->is_active ? ($request->alasan_nonaktif ?? null) : null,
            'catatan'         => $buyer->is_active ? ($request->catatan ?? null) : null,
        ]);

        return redirect()->back()->with(
            'success',
            $buyer->is_active
                ? 'Buyer berhasil diaktifkan.'
                : 'Buyer berhasil dinonaktifkan.'
        );
    }

    private function uploadFoto(Request $request): array
    {
        if (!$request->hasFile('foto')) return [null, null];

        $upload = AmazonServerService::upload(
            'buyers/photos',
            $request->file('foto'),
            Str::uuid()->toString()
        );

        return [$upload['path'] ?? null, $upload['storage'] ?? config('filesystems.default')];
    }

    private function formatBuyer(Buyer $b): array
    {
        return [
            'id'                 => $b->id,
            'kode'               => $b->kode,
            'nama'               => $b->nama,
            'tipe'               => $b->tipe,
            'telepon'            => $b->telepon,
            'email'              => $b->email,
            'city_id'            => $b->city_id,
            'city_name'          => $b->city?->nama,
            'harga_jual_default' => $b->harga_jual_default,
            'limit_kredit'       => $b->limit_kredit,
            'termin_hari'        => $b->termin_hari,
            'pic'                => $b->pic,
            'npwp'               => $b->npwp,
            'website'            => $b->website,
            'alamat'             => $b->alamat,
            'catatan'            => $b->catatan,
            'is_active'          => $b->is_active,
            'alasan_nonaktif'    => $b->alasan_nonaktif,
            'foto_url'           => $b->foto_url,
            'inisials'           => $b->inisials,
        ];
    }
}
