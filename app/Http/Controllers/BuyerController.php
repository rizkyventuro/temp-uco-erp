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
        $sortable = ['name', 'code', 'default_selling_price', 'total_sales', 'total_receivable', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $buyers = Buyer::with('city')
            ->when(
                $request->search,
                fn($q, $search) =>
                $q->where(
                    fn($q) =>
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
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
            ->paginate($request->input('perPage', 10))
            ->withQueryString()
            ->through(fn($b) => $this->formatBuyer($b));

        $stats = [
            'total'    => Buyer::count(),
            'active'   => Buyer::active()->count(),
            'inactive' => Buyer::inactive()->count(),
        ];

        $cities = City::whereIn('id', Buyer::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/buyer/ListBuyer', [
            'buyers'    => $buyers,
            'stats'     => $stats,
            'cities'    => $cities,
            'allCities' => $allCities,
            'filters'   => $request->only(['search', 'perPage', 'status', 'type', 'city_id', 'sort', 'direction']),
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
        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/buyer/ShowBuyer', array_merge(
            $this->buildShowPayload($buyer),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    private function buildShowPayload(Buyer $buyer): array
    {
        $totalSales      = 520000000;
        $transactionCount = 24;
        $totalVolume     = 118181;
        $avgPrice        = 4400;
        $activeReceivable = 22000000;
        $rating          = 4.8;

        $ratingLabel = match (true) {
            $rating >= 4.5 => 'Bagus',
            $rating >= 3.5 => 'Cukup Bagus',
            $rating >= 2.5 => 'Cukup',
            default        => 'Kurang',
        };

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

        $transactions = [
            ['id' => '1', 'document_number' => 'BM-2024-001', 'date' => '28 Jun 2026', 'warehouse' => 'Gudang Pusat', 'volume' => 1200, 'price' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '2', 'document_number' => 'BM-2024-002', 'date' => '28 Jun 2026', 'warehouse' => 'Gudang Pusat', 'volume' => 1200, 'price' => 4500, 'total' => 5400000, 'status' => 'Hutang'],
            ['id' => '3', 'document_number' => 'BM-2024-003', 'date' => '28 Jun 2026', 'warehouse' => 'Gudang Pusat', 'volume' => 1200, 'price' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '4', 'document_number' => 'BM-2024-004', 'date' => '28 Jun 2026', 'warehouse' => 'Gudang Pusat', 'volume' => 1200, 'price' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
            ['id' => '5', 'document_number' => 'BM-2024-005', 'date' => '28 Jun 2026', 'warehouse' => 'Gudang Pusat', 'volume' => 1200, 'price' => 4500, 'total' => 5400000, 'status' => 'Lunas'],
        ];

        $txPaginated = [
            'data'         => $transactions,
            'current_page' => 1,
            'last_page'    => 1,
            'total'        => count($transactions),
        ];

        $alreadyReceived  = $totalSales - $activeReceivable;
        $receivablePercent = round(($activeReceivable / $totalSales) * 100);

        $productsBought = [
            ['name' => 'Minyak Jelantah Grade A', 'percent' => 60],
            ['name' => 'Minyak Jelantah Grade B', 'percent' => 40],
        ];

        $activityLogs = [
            ['id' => '1', 'message' => 'Penjualan BK-042 Rp 22 Jt — Piutang dicatat', 'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'warning'],
            ['id' => '2', 'message' => 'Piutang BK-030 Rp 35.2 Jt dilunasi',          'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'success'],
            ['id' => '3', 'message' => "Buyer dinonaktifkan — Tidak aktif beroperasi",  'user' => 'Admin Kartono', 'time' => now()->subHours(2)->translatedFormat('d M Y H:i'), 'type' => 'danger'],
        ];

        return [
            'buyer' => $this->formatBuyer($buyer),

            'stats' => [
                'total_sales'          => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                'total_sales_sub'      => $transactionCount . ' transaksi',
                'active_receivable'    => 'Rp ' . number_format($activeReceivable, 0, ',', '.'),
                'active_receivable_sub' => $activeReceivable > 0 ? 'Tidak dibayar' : 'Tidak ada piutang',
                'avg_price'            => number_format($avgPrice, 0, ',', '.'),
                'avg_price_sub'        => 'per kg (Rp)',
                'total_volume'         => number_format($totalVolume, 0, ',', '.'),
                'total_volume_sub'     => 'kg terjual',
                'rating'               => number_format($rating, 1),
                'rating_sub'           => $ratingLabel,
            ],

            'volumeChart'  => $volumeChart,
            'transactions' => $txPaginated,

            'receivableSummary' => [
                'total_sales'       => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                'already_received'  => 'Rp ' . number_format($alreadyReceived, 0, ',', '.'),
                'active_receivable' => 'Rp ' . number_format($activeReceivable, 0, ',', '.'),
                'receivable_percent' => $receivablePercent,
            ],

            'productsBought' => $productsBought,
            'activityLogs'   => $activityLogs,

            'toggleUrl' => route('master-data.buyer.toggle-status', $buyer->id),
            'editUrl'   => route('master-data.buyer.edit', $buyer->id),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'type'                  => 'nullable|in:PT,CV,UD,Perorangan',
            'phone'                 => 'nullable|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'city_id'               => 'nullable|exists:cities,id',
            'default_selling_price' => 'nullable|numeric|min:0',
            'credit_limit'          => 'nullable|numeric|min:0',
            'payment_term_days'     => 'nullable|integer|min:1',
            'pic'                   => 'nullable|string|max:255',
            'npwp'                  => 'nullable|string|max:30',
            'website'               => 'nullable|string|max:255',
            'address'               => 'nullable|string',
            'notes'                 => 'nullable|string',
            'foto'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        [$fotoPath, $fotoDisk] = $this->uploadFoto($request);

        DB::transaction(function () use ($validated, $fotoPath, $fotoDisk) {
            Buyer::create(array_merge($validated, [
                'code'      => Buyer::generateCode(),
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
            'name'                  => 'sometimes|required|string|max:255',
            'type'                  => 'nullable|in:PT,CV,UD,Perorangan',
            'phone'                 => 'nullable|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'city_id'               => 'nullable|exists:cities,id',
            'default_selling_price' => 'nullable|numeric|min:0',
            'credit_limit'          => 'nullable|numeric|min:0',
            'payment_term_days'     => 'nullable|integer|min:1',
            'pic'                   => 'nullable|string|max:255',
            'npwp'                  => 'nullable|string|max:30',
            'website'               => 'nullable|string|max:255',
            'address'               => 'nullable|string',
            'notes'                 => 'nullable|string',
            'foto'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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
            'inactive_reason' => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        $buyer->update([
            'is_active'       => !$buyer->is_active,
            'inactive_reason' => $buyer->is_active ? ($request->inactive_reason ?? null) : null,
            'notes'           => $buyer->is_active ? ($request->notes ?? null) : null,
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
            'id'                    => $b->id,
            'code'                  => $b->code,
            'name'                  => $b->name,
            'type'                  => $b->type,
            'phone'                 => $b->phone,
            'email'                 => $b->email,
            'city_id'               => $b->city_id,
            'city_name'             => $b->city?->name,
            'default_selling_price' => $b->default_selling_price,
            'credit_limit'          => $b->credit_limit,
            'payment_term_days'     => $b->payment_term_days,
            'pic'                   => $b->pic,
            'npwp'                  => $b->npwp,
            'website'               => $b->website,
            'address'               => $b->address,
            'notes'                 => $b->notes,
            'is_active'             => $b->is_active,
            'inactive_reason'       => $b->inactive_reason,
            'photo_url'             => $b->foto_url,
            'initials'              => $b->initials,
        ];
    }
}
