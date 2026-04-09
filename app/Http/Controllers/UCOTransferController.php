<?php

namespace App\Http\Controllers;

use App\Models\PooCollection;
use App\Models\PooHistory;
use App\Models\PooOwnershipChain;
use App\Models\PooTransfer;
use App\Models\PooTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UCOTransferController extends Controller
{
    // List transfer + POO milik user
    public function index()
    {
        $collections = PooCollection::with('masterPoo')
            ->notExported()
            ->ownedBy(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($col) => [
                'id'           => $col->id,
                'transaction_code' => $col->transaction_code,
                'total_volume' => $col->volume,
            ]);

        return Inertia::render('admin/transfer/ListUCO', [
            'collections' => $collections,
        ]);
    }


    // =================== CONFIG ===================
    private const VOLUME_TOLERANCE_PERCENT = 5;
    private const VOLUME_TOLERANCE_ALLOW   = true;
    //  true  → sistem mencari kombinasi dalam rentang ±tol% dari target
    //  false → tidak menggunakan toleransi, sistem bebas pilih kombinasi paling mendekati target


    // =================== CREATE ===================

    public function create()
    {
        $userId = Auth::id();

        $collections = PooCollection::whereHas('masterPoo', fn($q) => $q->where('current_owner_id', $userId))
            ->notExported()
            ->get();

        $totalAvailableVolume = $collections->sum('volume');

        return Inertia::render('admin/transfer/KirimUCO', [
            'collections' => $collections->map(fn($col) => [
                'id'               => $col->id,
                'transaction_code' => $col->transaction_code,
                'volume'           => $col->volume,
            ]),
            'volumeTolerance'      => self::VOLUME_TOLERANCE_PERCENT,
            'toleranceAllow'       => self::VOLUME_TOLERANCE_ALLOW,
            'totalAvailableVolume' => $totalAvailableVolume,
        ]);
    }

    // =================== STORE ===================

    public function store(Request $request)
    {
        $userId = Auth::id();

        $request->validate([
            'mode'      => 'required|in:manual,volume',
            'poo_ids'   => 'required_if:mode,manual|nullable|array',
            'poo_ids.*' => 'exists:t_poo_collections,id',
            'volume'    => ['required_if:mode,volume', 'nullable', 'numeric', 'min:0.1'],
        ]);

        try {
            $transfer = DB::transaction(function () use ($request, $userId) {

                if ($request->mode === 'manual') {
                    $collections = PooCollection::whereIn('id', $request->poo_ids)
                        ->whereHas('masterPoo', fn($q) => $q->where('current_owner_id', $userId))
                        ->get();

                    if ($collections->isEmpty()) {
                        throw new \Exception('Tidak ada collection yang valid dipilih.');
                    }
                } else {
                    $target = (float) $request->volume;
                    $tol    = self::VOLUME_TOLERANCE_PERCENT / 100;

                    $candidates = PooCollection::whereHas('masterPoo', fn($q) => $q->where('current_owner_id', $userId))
                        ->orderBy('created_at', 'asc')
                        ->limit(20)
                        ->get();

                    if ($candidates->isEmpty()) {
                        throw new \Exception('Tidak ada collection yang tersedia.');
                    }

                    $collections = $this->bestFitCollections($candidates, $target, $tol);

                    if ($collections === null) {
                        throw new \Exception(
                            "Tidak ada kombinasi collection dalam rentang " .
                                number_format($target * (1 - $tol), 2) . " – " .
                                number_format($target * (1 + $tol), 2) . " L (±" . self::VOLUME_TOLERANCE_PERCENT . "%)."
                        );
                    }
                }

                $totalVolume   = $collections->sum('volume');
                $transferCode  = PooTransfer::generateCode((string) $userId, now()->toDateString());

                $transfer = PooTransfer::create([
                    'sender_id'        => $userId,
                    'receiver_id'      => null,
                    'transfer_code'    => $transferCode,
                    'volume_requested' => $request->mode === 'volume' ? (float) $request->volume : $totalVolume,
                    'volume_actual'    => $totalVolume,
                    'status'           => PooTransfer::STATUS_PENDING,
                    'expires_at'       => now()->addHours(PooTransfer::TRANSFER_LIFETIME_HOURS),
                ]);

                foreach ($collections as $col) {
                    PooTransferItem::create([
                        't_poo_transfer_id'   => $transfer->id,
                        't_poo_collection_id' => $col->id,
                    ]);
                }

                return $transfer;
            });

            return redirect()->route('transfers.show', $transfer->transfer_code);
        } catch (\Throwable $th) {
            dd($th->getMessage());
            return back()->withErrors(['volume' => $th->getMessage()]);
        }
    }

    // =================== SHOW (Halaman QR) ===================

    public function show($transfer_code)
    {
        $transfer = PooTransfer::with([
            'sender',
            'collections',
        ])->where('transfer_code', $transfer_code)->firstOrFail();

        if ($transfer->sender_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Tandai expired jika sudah lewat waktu
        if ($transfer->isExpired() && $transfer->status === PooTransfer::STATUS_PENDING) {
            DB::transaction(function () use ($transfer) {
                $transfer->update(['status' => PooTransfer::STATUS_EXPIRED]);
            });
        }


        return Inertia::render('admin/transfer/BerhasilKirimUCO', [
            'transfer' => [
                'id'            => $transfer->id,
                'transfer_code' => $transfer->transfer_code,
                'volume_actual' => $transfer->volume_actual,
                'status'        => $transfer->status,
                'status_label'  => $transfer->status_label,
                'sender_name'   => $transfer->sender->name,
                'expires_at'    => $transfer->expires_at?->toISOString(),
                'is_claimable'  => $transfer->isClaimable(),
                'poos'          => $transfer->collections->map(fn($col) => [
                    'id'   => $col->id,
                    'transaction_code' => $col->transaction_code,
                    'volume' => $col->volume,
                ]),
                'created_at'    => $transfer->created_at->format('d F Y'),
            ],
        ]);
    }

    // =================== CLAIM (Form terima - scan QR / ketik kode) ===================

    public function claim(Request $request)
    {
        $transfer    = null;
        $claimError  = null;

        if ($request->has('code') && $request->code) {
            $found = PooTransfer::with(['sender', 'collections.masterPoo'])
                ->where('transfer_code', $request->code)
                ->first();

            if (! $found) {
                $claimError = 'Kode transfer tidak ditemukan.';
            } elseif ($found->isUsed()) {
                $claimError = 'Kode transfer ini sudah pernah digunakan.';
            } elseif ($found->isExpired()) {
                // Pastikan status ter-update
                DB::transaction(function () use ($found) {
                    $found->update(['status' => PooTransfer::STATUS_EXPIRED]);
                });
                $claimError = 'Kode transfer sudah kadaluarsa (lebih dari 24 jam).';

            } else {
                $transfer = $found;
            }
        }

        return Inertia::render('admin/transfer/TerimaUCO', [
            'transfer' => $transfer ? [
                'id'            => $transfer->id,
                'transfer_code' => $transfer->transfer_code,
                'volume_actual' => $transfer->volume_actual,
                'status'        => $transfer->status,
                'status_label'  => $transfer->status_label,
                'sender_name'   => $transfer->sender->name,
                'expires_at'    => $transfer->expires_at?->toISOString(),
                'poos'          => $transfer->collections->map(fn($col) => [
                    'transaction_code' => $col->transaction_code,
                    'volume' => $col->volume,
                ]),
            ] : null,
            'searchCode' => $request->code,
            'claimError' => $claimError,
        ]);
    }

    // =================== CONFIRM CLAIM ===================

    public function confirmClaim(Request $request)
    {
        $request->validate([
            'transfer_code' => 'required|exists:t_poo_transfers,transfer_code',
        ]);



        $transfer = PooTransfer::where('transfer_code', $request->transfer_code)->firstOrFail();

        if (! $transfer->isClaimable()) {
            $msg = $transfer->isUsed()
                ? 'Transfer ini sudah diklaim.'
                : 'Transfer sudah kadaluarsa atau tidak valid.';

            return back()->withErrors(['claim' => $msg]);
        }

        DB::transaction(function () use ($transfer) {
            $receiverId = Auth::id();

            PooCollection::whereIn('id', $transfer->collections->pluck('id'))
                ->update(['current_owner_id' => $receiverId]);

            $transfer->update([
                'receiver_id' => $receiverId,
                'status'      => PooTransfer::STATUS_CLAIMED,
                'claimed_at'  => now(),
            ]);

            // Tambahkan ini
            $transfer->refresh();
            PooHistory::recordTransfer($transfer);
            PooOwnershipChain::recordTransfer($transfer);
        });

        return redirect()->route('transfers.index')
            ->with('success', 'Transfer berhasil diterima!');
    }



    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = Auth::id();

        $request->validate([
            'volume' => ['required', 'numeric', 'min:0.1'],
        ]);

        $target = (float) $request->volume;
        $tol    = self::VOLUME_TOLERANCE_PERCENT / 100;

        $candidates = PooCollection::whereHas('masterPoo', fn($q) => $q->where('current_owner_id', $userId))
            ->notExported()
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'success'     => false,
                'message'     => 'Tidak ada collection yang tersedia.',
                'collections' => [],
            ]);
        }

        $collections = $this->bestFitCollections($candidates, $target, $tol);

        if ($collections === null) {
            $min = number_format($target * (1 - $tol), 2);
            $max = number_format($target * (1 + $tol), 2);

            return response()->json([
                'success'     => false,
                'message'     => "Tidak ada kombinasi collection dalam rentang {$min} – {$max} L (±" . self::VOLUME_TOLERANCE_PERCENT . "%).",
                'collections' => [],
            ]);
        }

        return response()->json([
            'success'      => true,
            'message'      => null,
            'collections'  => $collections->map(fn($col) => [
                'id'               => $col->id,
                'transaction_code' => $col->transaction_code,
                'volume'           => $col->volume,
            ])->values(),
            'total_volume' => $collections->sum('volume'),
        ]);
    }

    // =================== BEST FIT ALGORITHM ===================

    // private function bestFitCollections($candidates, float $target, float $tol)
    // {
    //     $items     = $candidates->values()->all();
    //     $n         = count($items);
    //     $bestDiff  = PHP_FLOAT_MAX;
    //     $bestSubset = null;

    //     // Iterasi semua subset (max 20 items = 2^20 = ~1jt, masih aman)
    //     for ($mask = 1; $mask < (1 << $n); $mask++) {
    //         $sum    = 0;
    //         $subset = [];

    //         for ($i = 0; $i < $n; $i++) {
    //             if ($mask & (1 << $i)) {
    //                 $sum    += $items[$i]->volume;
    //                 $subset[] = $items[$i];
    //             }
    //         }

    //         if (self::VOLUME_TOLERANCE_ALLOW) {
    //             $min = $target * (1 - $tol);
    //             $max = $target * (1 + $tol);

    //             if ($sum >= $min && $sum <= $max) {
    //                 $diff = abs($sum - $target);
    //                 if ($diff < $bestDiff) {
    //                     $bestDiff   = $diff;
    //                     $bestSubset = $subset;
    //                 }
    //             }
    //         } else {
    //             // Tanpa toleransi — pilih yang paling mendekati
    //             $diff = abs($sum - $target);
    //             if ($diff < $bestDiff) {
    //                 $bestDiff   = $diff;
    //                 $bestSubset = $subset;
    //             }
    //         }
    //     }

    //     if ($bestSubset === null) {
    //         return null; // Hanya terjadi jika toleranceAllow=true dan tidak ada subset dalam range
    //     }

    //     return collect($bestSubset);
    // }







    // =================== BEST FIT ===================
    /**
     * Cari kombinasi collection yang totalnya paling mendekati target.
     *
     * VOLUME_TOLERANCE_ALLOW = true
     *   → Hanya subset yang totalnya dalam rentang (target - tol%) s/d (target + tol%) yang valid.
     *   → Dari yang valid, ambil yang diff-nya terkecil.
     *   → Jika tidak ada subset dalam range → return null.
     *
     * VOLUME_TOLERANCE_ALLOW = false
     *   → Tidak ada batasan range, semua subset valid.
     *   → Langsung ambil subset dengan diff terkecil dari target.
     *   → Tidak akan pernah return null selama ada kandidat.
     *
     * Contoh ALLOW=true, tol 20%, target 13L, tersedia [25L, 12L]:
     *   Range: 10.4 – 15.6 L
     *   → 12L ✅ diff=1 → terpilih
     *   → 25L ❌ di luar range
     *
     * Contoh ALLOW=false, target 13L, tersedia [25L, 12L]:
     *   → 12L diff=1, 25L diff=12 → terpilih: 12L (paling mendekati)
     */
    private function bestFitCollections(
        \Illuminate\Support\Collection $candidates,
        float $target,
        float $tol
    ): ?\Illuminate\Support\Collection {
        $items      = $candidates->values()->all();
        $n          = count($items);
        $bestDiff   = PHP_FLOAT_MAX;
        $bestSubset = null;

        // Hitung range hanya jika toleransi aktif
        $useRange   = self::VOLUME_TOLERANCE_ALLOW;
        $minAllowed = $useRange ? $target * (1 - $tol) : null;
        $maxAllowed = $useRange ? $target * (1 + $tol) : null;

        for ($mask = 1; $mask < (1 << $n); $mask++) {
            $subset = [];
            $total  = 0.0;

            for ($i = 0; $i < $n; $i++) {
                if ($mask & (1 << $i)) {
                    $subset[] = $items[$i];
                    $total   += (float) $items[$i]->volume;
                }
            }

            // Jika toleransi aktif, skip subset di luar range
            if ($useRange && ($total < $minAllowed || $total > $maxAllowed)) {
                continue;
            }

            $diff = abs($total - $target);
            if ($diff < $bestDiff) {
                $bestDiff   = $diff;
                $bestSubset = $subset;
            }
        }

        return $bestSubset !== null ? collect($bestSubset) : null;
    }
}
