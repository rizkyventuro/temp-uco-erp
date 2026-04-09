<?php

namespace App\Http\Controllers;

use App\Models\MasterPoo;
use App\Models\PooCollection;
use App\Models\PooHistory;
use App\Models\PooOwnershipChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\AmazonServerService;
use Inertia\Inertia;

class PooCollectionController extends Controller
{
    public function index()
    {
        $poos = MasterPoo::with('creator')
            ->withSum(['collections as collections_sum_volume' => function ($q) {
                $q->notExported();
            }], 'volume')
            ->withMax('collections', 'collected_at')
            ->where('created_by', Auth::id())
            ->orderByDesc('collections_max_collected_at')
            ->limit(8)
            ->get()
            ->map(fn($poo) => [
                'id'              => $poo->id,
                'name'            => $poo->name,
                'address'         => $poo->address,
                'contact'         => $poo->contact,
                'type'            => $poo->type,
                'total_collected' => $poo->collections_sum_volume ?? 0,
            ]);

        return Inertia::render('admin/pooCollections/ListPooCollections', [
            'poos' => $poos,
        ]);
    }

    public function create($pooId)
    {
        $poo = MasterPoo::findOrFail($pooId);

        if ($poo->created_by !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('admin/pooCollections/CreatePooCollection', [
            'poo' => [
                'id'         => $poo->id,
                'name'       => $poo->name,
                'address'    => $poo->address,
                'type_label' => $poo->type,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'm_poo_id'     => 'required|exists:m_poos,id',
            'volume'       => 'required|numeric|min:0.1',
            'collected_at' => 'required|date',
            'photo'        => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'signature'    => 'nullable|string',
            'notes'        => 'nullable|string|max:500',
        ]);

        // Upload foto
        $photoPath = null;
        $photoDisk = null;
        if ($request->hasFile('photo')) {
            $uploadResult = AmazonServerService::upload(
                'collections/photos',
                $request->file('photo'),
                Str::uuid()->toString()
            );
            $photoPath = $uploadResult['path'] ?? null;
            $photoDisk = $uploadResult['storage'] ?? config('filesystems.default');
        }

        // Simpan signature base64
        $signaturePath = null;
        $signatureDisk = null;
        if ($request->filled('signature')) {
            $base64       = preg_replace('/^data:image\/\w+;base64,/', '', $request->signature);
            $uploadResult = AmazonServerService::uploadContent(
                'collections/signatures',
                base64_decode($base64),
                'webp',
                Str::uuid()->toString()
            );
            $signaturePath = $uploadResult['path'] ?? null;
            $signatureDisk = $uploadResult['storage'] ?? config('filesystems.default');
        }

        $generateCode = PooCollection::generateCode(
            $request->m_poo_id,
            $request->collected_at
        );

        $data = DB::transaction(function () use ($request, $photoPath, $signaturePath, $photoDisk, $signatureDisk, $generateCode) {
            $data = PooCollection::create([
                'created_by'       => Auth::id(),
                'current_owner_id' => Auth::id(),
                'm_poo_id'         => $request->m_poo_id,
                'transaction_code' => $generateCode,
                'volume'           => $request->volume,
                'collected_at'     => $request->collected_at,
                'photo'            => $photoPath,
                'photo_disk'       => $photoDisk,
                'signature'        => $signaturePath,
                'signature_disk'   => $signatureDisk,
                'notes'            => $request->notes,
            ]);

            PooHistory::recordCollection($data);
            PooOwnershipChain::recordCollection($data);

            return $data;
        });

        return redirect()->route('collections.success', ['collactId' => $data->id]);
    }

    public function success($collectionId)
    {
        $collection = PooCollection::with(['masterPoo', 'creator'])->findOrFail($collectionId);

        if ($collection->created_by !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('admin/pooCollections/SuccessCollectPoo', [
            'collection' => [
                'code'          => $collection->transaction_code,
                'poo_id'        => $collection->masterPoo->id,
                'poo_name'      => $collection->masterPoo->name,
                'volume'        => (float) $collection->volume,
                'collected_at'  => $collection->collected_at->format('d F Y'),
                'photo_url'     => $collection->photo_url,       // ← dari accessor
                'signature_url' => $collection->signature_url,   // ← dari accessor
                'notes'         => $collection->notes,
                'created_by'    => $collection->creator?->name,
            ],
        ]);
    }
}