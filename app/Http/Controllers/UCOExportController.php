<?php

namespace App\Http\Controllers;

use App\Models\PooCollection;
use App\Models\PooOwnershipChain;
use App\Models\UcoExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\AmazonServerService;
use Inertia\Inertia;

class UCOExportController extends Controller
{
    public function index(Request $request)
    {
        $readyBatches = PooCollection::with('masterPoo')
            ->notExported()
            ->when(
                $request->search_batch,
                fn($q, $s) =>
                $q->whereHas('masterPoo', fn($q2) => $q2->where('name', 'like', "%{$s}%"))
                    ->orWhere('transaction_code', 'like', "%{$s}%")
            )
            ->ownedBy(Auth::id())
            ->orderByDesc('collected_at')
            ->paginate($request->get('perPage_batch', 10))
            ->through(fn($c) => [
                'id'              => $c->id,
                'code'            => $c->transaction_code,
                'poo_name'        => $c->masterPoo->name,
                'collection_date' => $c->collected_at,
                'volume'          => $c->volume,
                'nilai'           => $c->nilai ?? 0,
                'status'          => 'Siap Export',
            ]);

        $history = UcoExport::with('collection.masterPoo')
            ->whereHas('collection', fn($q) => $q->where('current_owner_id', Auth::id()))
            ->when(
                $request->search_history,
                fn($q, $s) =>
                $q->where('export_code', 'like', "%{$s}%")
                    ->orWhereHas('collection.masterPoo', fn($q2) => $q2->where('name', 'like', "%{$s}%"))
            )
            ->orderByDesc('export_date')
            ->paginate($request->get('perPage_history', 10))
            ->through(fn($e) => [
                'id'          => $e->id,
                'code'        => $e->export_code,
                'poo_name'    => $e->collection->masterPoo->name ?? '-',
                'volume'      => (float) $e->volume,
                'exported_at' => $e->export_date->toDateString(),
            ]);

        return Inertia::render('admin/exports/ListExportUCO', [
            'readyBatches' => $readyBatches,
            'history'      => $history,
            'filters'      => $request->only(['search_batch', 'search_history', 'perPage_batch', 'perPage_history']),
        ]);
    }

    public function confirmation($collectionId)
    {
        $collection = PooCollection::with('masterPoo')->findOrFail($collectionId);

        if ($collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $chains = PooOwnershipChain::with(['fromUser', 'toUser'])
            ->forCollection($collectionId)
            ->get();

        $lastChain = $chains->last();

        $ownerships = $chains->map(function ($chain, $index) use ($collection, $lastChain) {
            $isCurrent = $chain->id === $lastChain?->id;

            if ($chain->type === PooOwnershipChain::TYPE_COLLECTION) {
                return [
                    'id'         => $index + 1,
                    'role'       => 'poo',
                    'name'       => $collection->masterPoo->name,
                    'company'    => $collection->masterPoo->name,
                    'location'   => $collection->masterPoo->address ?? '-',
                    'volume'     => (float) $collection->volume,
                    'owned_at'   => $chain->transferred_at->toDateString(),
                    'is_current' => $isCurrent,
                ];
            }

            // TYPE_TRANSFER → pengepul
            $userName    = $chain->toUser->name ?? '-';
            $userCompany = 'PT Minyak Sejahtera'; // TODO: ganti dengan $chain->toUser->company

            return [
                'id'         => $index + 1,
                'role'       => 'pengepul',
                'name'       => $userName . ' - ' . $userCompany,
                'company'    => $userCompany,
                'location'   => 'Tangerang', // TODO: ganti dengan $chain->toUser->address
                'volume'     => (float) $collection->volume,
                'owned_at'   => $chain->transferred_at->toDateString(),
                'is_current' => $isCurrent,
            ];
        })->values()->toArray();


        $refineries = [
            ['id' => 1, 'name' => 'PT Pertamina Refinery'],
            ['id' => 2, 'name' => 'PT Bio Energi Nusantara'],
            ['id' => 3, 'name' => 'Global Green Fuel Ltd'],
        ];

        return Inertia::render('admin/exports/KonfirmasiExportUCO', [
            'batch' => [
                'id'              => $collection->id,
                'code'            => $collection->transaction_code,
                'poo_name'        => $collection->masterPoo->name,
                'volume'          => (float) $collection->volume,
                'collection_date' => $collection->collected_at->toDateString(),
            ],
            'ownerships' => $ownerships,
            'refineries' => $refineries,
        ]);
    }

    public function generate(Request $request, $collectionId)
    {
        $request->validate([
            'refinery_name' => 'required|string|max:255',
        ]);

        $collection = PooCollection::with('masterPoo')->findOrFail($collectionId);

        if ($collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $export = DB::transaction(function () use ($request, $collection) {
            $export = UcoExport::create([
                't_poo_collection_id' => $collection->id,
                'export_code'         => UcoExport::generateExportCode(),
                'refinery_name'       => $request->refinery_name,
                'volume'              => $collection->volume,
                'export_date'         => now()->toDateString(),
                'locked_at'           => now(),
            ]);

            // Generate and save PDF to storage
            $iscc = $this->buildIsccData($export);
            $pdf = Pdf::loadView(
                'pdf.iscc-document',
                [
                    'iscc' => $iscc,
                    'isPreview' => true,
                ]
            )
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'defaultFont'          => 'DejaVu Serif',
                    'dpi'                  => 96,
                    'margin_top'           => 10,
                    'margin_bottom'        => 10,
                    'margin_left'          => 10,
                    'margin_right'         => 10,
                    'defaultPaperWidth'    => 210,   // mm
                    'defaultPaperHeight'   => 297,   // mm
                ]);

            $filenameExt = 'ISCC-' . $export->export_code;
            $uploadResult = AmazonServerService::uploadContent('exports', $pdf->output(), 'pdf', $filenameExt);
            
            $export->update([
                'iscc_path' => $uploadResult['path'] ?? null,
                'iscc_disk' => $uploadResult['storage'] ?? config('filesystems.default')
            ]);

            // Mark collection as exported
            $collection->markAsExported();

            return $export;
        });

        return redirect()->route('exports.success', $export->id);
    }

    public function success($exportId)
    {
        $export = UcoExport::with('collection.masterPoo')->findOrFail($exportId);

        if ($export->collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('admin/exports/ExportSuccessUco', [
            'export' => [
                'id'            => $export->id,
                'batch_code'    => $export->export_code,
                'poo_name'      => $export->collection->masterPoo->name,
                'volume'        => (float) $export->volume,
                'exported_at'   => $export->export_date->toDateString(),
                'refinery_name' => $export->refinery_name,
                'iscc'          => $this->buildIsccData($export),
            ],
        ]);
    }

    public function previewIscc($id)
    {
        $export = UcoExport::with('collection.masterPoo')->findOrFail($id);

        if ($export->collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('pdf.iscc-document', [
            'iscc' => $this->buildIsccData($export),
            'isPreview' => false,
        ]);
    }

    public function download($exportId)
    {
        $export = UcoExport::with('collection.masterPoo')->findOrFail($exportId);

        if ($export->collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $iscc = $this->buildIsccData($export);
        $pdf = Pdf::loadView('pdf.iscc-document', [
            'iscc' => $iscc,
            'isPreview' => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'DejaVu Serif',
                'dpi'                  => 96,
                'margin_top'           => 10,
                'margin_bottom'        => 10,
                'margin_left'          => 10,
                'margin_right'         => 10,
                'defaultPaperWidth'    => 210,   // mm
                'defaultPaperHeight'   => 297,   // mm
            ]);

        $filename = 'ISCC-' . $export->export_code . '.pdf';

        // return $pdf->download($filename);

        return $pdf->download($filename);

        abort(404, 'Download belum tersedia.');
    }

    public function downloadStoredIscc($exportId)
    {
        $export = UcoExport::with('collection')->findOrFail($exportId);

        if ($export->collection->current_owner_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (!$export->iscc_path) {
            abort(404, 'File ISCC belum tersedia.');
        }

        $filename = 'ISCC-' . $export->export_code . '.pdf';

        if (Storage::disk('s3')->exists($export->iscc_path)) {
            return Storage::disk('s3')->download($export->iscc_path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (Storage::disk('public')->exists($export->iscc_path)) {
            return Storage::disk('public')->download($export->iscc_path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (Storage::disk('local')->exists($export->iscc_path)) {
            return Storage::disk('local')->download($export->iscc_path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        abort(404, 'File ISCC tidak ditemukan di storage.');
    }

    private function buildIsccData(UcoExport $export): array
{
    $poo        = $export->collection->masterPoo;
    $collection = $export->collection;

    $signatureBase64 = null;

    if ($collection->signature) {
        $disk = $collection->signature_disk ?? 'public';
        $path = $collection->signature;

        try {
            $storageDisk = match ($disk) {
                's3'    => Storage::disk('s3'),
                default => Storage::disk('public'),
            };

            if ($storageDisk->exists($path)) {
                $content = $storageDisk->get($path);
                $mime    = $storageDisk->mimeType($path);
                $signatureBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        } catch (\Throwable) {
            // Gagal ambil signature tidak menghentikan proses
        }
    }

    return [
        'poo_name'    => $poo->name,
        'poo_street'  => $poo->address ?? '-',
        'poo_city'    => '-',
        'poo_country' => 'Indonesia',
        'poo_phone'   => $poo->contact ?? '-',
        'uco_amount'  => number_format((float) $export->volume, 0, ',', '.') . ' Litres',
        'recipient'   => $export->refinery_name,
        'signatory'   => $poo->name . ' – Manager',
        'place_date'  => 'Indonesia, ' . $export->export_date->format('d F Y'),
        'signature'   => $signatureBase64,
    ];
}
}
