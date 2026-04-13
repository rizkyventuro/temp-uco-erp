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
        $sortable = ['name', 'code', 'default_purchase_price', 'monthly_capacity', 'created_at'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $suppliers = Supplier::with('city')
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
            ->when(
                $request->payment_term,
                fn($q, $pt) =>
                $q->where('payment_term', $pt)
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
            'active'   => Supplier::active()->count(),
            'inactive' => Supplier::inactive()->count(),
        ];

        $cities = City::whereIn('id', Supplier::whereNotNull('city_id')->pluck('city_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/supplier/ListSupplier', [
            'suppliers' => $suppliers,
            'stats'     => $stats,
            'cities'    => $cities,
            'allCities' => $allCities,
            'filters'   => $request->only(['search', 'perPage', 'status', 'payment_term', 'city_id', 'sort', 'direction']),
        ]);
    }

    // ── Show ───────────────────────────────────────────────────────

    public function show(Request $request, $id)
    {
        $supplier = Supplier::with('city')->findOrFail($id);

        return Inertia::render('masterData/supplier/ShowSupplier', $this->buildShowPayload($supplier));
    }

    // ── Edit ───────────────────────────────────────────────────

    public function edit($id)
    {
        $supplier  = Supplier::with('city')->findOrFail($id);
        $allCities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('masterData/supplier/ShowSupplier', array_merge(
            $this->buildShowPayload($supplier),
            ['openEditModal' => true, 'allCities' => $allCities]
        ));
    }

    // ── Show payload builder ───────────────────────────────────

    private function buildShowPayload(Supplier $supplier): array
    {
        // ── Static Stats ────────────────────────────────────────────
        $totalPurchase   = 125000000;
        $transactionCount = 24;
        $totalVolume     = 18500;
        $avgPrice        = 6800;
        $activeDebt      = 15000000;
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

        // ── Dummy Transactions ──────────────────────────────────────
        $transactions = [
            [
                'id'             => 1,
                'document_number' => 'TRX-001',
                'date'           => '01 Jan 2026',
                'warehouse'      => 'Gudang A',
                'volume'         => 1000,
                'price'          => 6500,
                'total'          => 6500000,
                'status'         => 'Lunas',
            ],
            [
                'id'             => 2,
                'document_number' => 'TRX-002',
                'date'           => '05 Jan 2026',
                'warehouse'      => 'Gudang B',
                'volume'         => 800,
                'price'          => 7000,
                'total'          => 5600000,
                'status'         => 'Hutang',
            ],
            [
                'id'             => 3,
                'document_number' => 'TRX-003',
                'date'           => '10 Jan 2026',
                'warehouse'      => 'Gudang A',
                'volume'         => 1200,
                'price'          => 6800,
                'total'          => 8160000,
                'status'         => 'Pending',
            ],
        ];

        $txPaginated = [
            'data'         => $transactions,
            'current_page' => 1,
            'last_page'    => 1,
            'total'        => count($transactions),
        ];

        // ── Debt Summary ────────────────────────────────────────────
        $totalPaid   = $totalPurchase - $activeDebt;
        $paidPercent = round(($totalPaid / $totalPurchase) * 100);

        // ── Warehouse Distribution Dummy ────────────────────────────
        $warehouseDistribution = [
            ['name' => 'Gudang A', 'percent' => 60],
            ['name' => 'Gudang B', 'percent' => 30],
            ['name' => 'Gudang C', 'percent' => 10],
        ];

        // ── Activity Logs Dummy ─────────────────────────────────────
        $activityLogs = [
            [
                'id'      => 1,
                'message' => 'Supplier dibuat',
                'user'    => 'Admin',
                'time'    => now()->subDays(2)->translatedFormat('d M Y H:i'),
                'type'    => 'success',
            ],
            [
                'id'      => 2,
                'message' => 'Update data supplier',
                'user'    => 'Admin',
                'time'    => now()->subDay()->translatedFormat('d M Y H:i'),
                'type'    => 'info',
            ],
            [
                'id'      => 3,
                'message' => 'Supplier dinonaktifkan',
                'user'    => 'System',
                'time'    => now()->subHours(5)->translatedFormat('d M Y H:i'),
                'type'    => 'warning',
            ],
        ];

        return [
            'supplier' => $this->formatSupplier($supplier),

            'stats' => [
                'total_purchase'     => 'Rp ' . number_format($totalPurchase, 0, ',', '.'),
                'total_purchase_sub' => $transactionCount . ' transaksi',
                'active_debt'        => 'Rp ' . number_format($activeDebt, 0, ',', '.'),
                'active_debt_sub'    => 'Ada hutang',
                'avg_price'          => number_format($avgPrice, 0, ',', '.'),
                'avg_price_sub'      => 'per kg (Rp)',
                'total_volume'       => number_format($totalVolume, 0, ',', '.'),
                'total_volume_sub'   => 'kg diterima',
                'rating'             => number_format($rating, 1),
                'rating_sub'         => $ratingLabel,
            ],

            'volumeChart'  => $volumeChart,
            'transactions' => $txPaginated,

            'debtSummary' => [
                'total_purchase' => 'Rp ' . number_format($totalPurchase, 0, ',', '.'),
                'total_paid'     => 'Rp ' . number_format($totalPaid, 0, ',', '.'),
                'active_debt'    => 'Rp ' . number_format($activeDebt, 0, ',', '.'),
                'paid_percent'   => $paidPercent,
            ],

            'warehouseDistribution' => $warehouseDistribution,
            'activityLogs'          => $activityLogs,

            'toggleUrl' => route('master-data.supplier.toggle-status', $supplier->id),
            'editUrl'   => route('master-data.supplier.edit', $supplier->id),
        ];
    }

    // ── Store ──────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'phone'                  => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'city_id'                => 'nullable|exists:cities,id',
            'monthly_capacity'       => 'nullable|numeric|min:0',
            'default_purchase_price' => 'nullable|numeric|min:0',
            'bank'                   => 'nullable|string|max:100',
            'account_number'         => 'nullable|string|max:50',
            'account_holder'         => 'nullable|string|max:255',
            'npwp'                   => 'nullable|string|max:30',
            'pic'                    => 'nullable|string|max:255',
            'address'                => 'nullable|string',
            'notes'                  => 'nullable|string',
            'payment_term'           => 'required|in:cash,tempo',
            'payment_term_days'      => 'nullable|integer|min:1|required_if:payment_term,tempo',
            'foto'                   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        [$fotoPath, $fotoDisk] = $this->uploadFoto($request);

        DB::transaction(function () use ($validated, $fotoPath, $fotoDisk) {
            Supplier::create(array_merge($validated, [
                'code'      => Supplier::generateCode(),
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
            'name'                   => 'sometimes|required|string|max:255',
            'phone'                  => 'nullable|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'city_id'                => 'nullable|exists:cities,id',
            'monthly_capacity'       => 'nullable|numeric|min:0',
            'default_purchase_price' => 'nullable|numeric|min:0',
            'bank'                   => 'nullable|string|max:100',
            'account_number'         => 'nullable|string|max:50',
            'account_holder'         => 'nullable|string|max:255',
            'npwp'                   => 'nullable|string|max:30',
            'pic'                    => 'nullable|string|max:255',
            'address'                => 'nullable|string',
            'notes'                  => 'nullable|string',
            'payment_term'           => 'sometimes|required|in:cash,tempo',
            'payment_term_days'      => 'nullable|integer|min:1',
            'foto'                   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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
            'inactive_reason' => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        $supplier->update([
            'is_active'       => ! $supplier->is_active,
            'inactive_reason' => $supplier->is_active ? ($request->inactive_reason ?? null) : null,
            'notes'           => $supplier->is_active ? ($request->notes ?? null) : null,
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
            'id'                     => $s->id,
            'code'                   => $s->code,
            'name'                   => $s->name,
            'phone'                  => $s->phone,
            'email'                  => $s->email,
            'city_id'                => $s->city_id,
            'city_name'              => $s->city?->name,
            'monthly_capacity'       => $s->monthly_capacity,
            'default_purchase_price' => $s->default_purchase_price,
            'bank'                   => $s->bank,
            'account_number'         => $s->account_number,
            'account_holder'         => $s->account_holder,
            'npwp'                   => $s->npwp,
            'pic'                    => $s->pic,
            'address'                => $s->address,
            'notes'                  => $s->notes,
            'payment_term'           => $s->payment_term,
            'payment_term_days'      => $s->payment_term_days,
            'payment_term_label'     => $s->payment_term_label,
            'is_active'              => $s->is_active,
            'inactive_reason'        => $s->inactive_reason,
            'photo_url'              => $s->foto_url,
            'initials'               => $s->initials,
        ];
    }
}
