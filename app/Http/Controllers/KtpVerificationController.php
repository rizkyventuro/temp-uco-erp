<?php

namespace App\Http\Controllers;

use App\Services\KtpOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class KtpVerificationController extends Controller
{
    public function index()
    {
        return Inertia::render('Ktp/Index');
    }

    public function extract(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:10240',
        ]);

        try {
            $service = new KtpOcrService();
            $result = $service->extract($request->file('foto'));

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function extractForProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validasi: harus ada salah satu — photo ATAU ocr_data
        $request->validate([
            'photo'    => ['required_without:ocr_data', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'ocr_data' => ['required_without:photo', 'array'],
        ]);
        $file = $request->file('photo'); // atau parameter yang masuk
        \Log::info('KTP file info', [
            'size'      => $file->getSize(),
            'mime'      => $file->getMimeType(),
            'width_x_height' => getimagesize($file->getRealPath()),
        ]);

        try {
            // ── Ambil data OCR: dari foto baru atau dari ocr_data yang dikirim ──
            if ($request->hasFile('photo')) {
                $result = (new KtpOcrService())->extract($request->file('photo'));
                $ocr    = $result['data'] ?? [];
            } else {
                // Pakai ocr_data yang dikirim langsung dari frontend
                $ocr = $request->input('ocr_data', []);
            }

            // ── VALIDASI FIELD WAJIB ───────────────────────────────────
            $missingFields = [];
            if (empty($ocr['nama']))          $missingFields[] = 'Nama';
            if (empty($ocr['tanggal_lahir'])) $missingFields[] = 'Tanggal Lahir';
            if (empty($ocr['jenis_kelamin'])) $missingFields[] = 'Jenis Kelamin';

            if (!empty($missingFields)) {
                return response()->json([
                    'success'        => false,
                    'retry'          => true,
                    'missing_fields' => $missingFields,
                    'message'        => 'Data KTP tidak lengkap: ' . implode(', ', $missingFields)
                        . '. Silakan ambil foto ulang.',
                ], 422);
            }

            // ── NIK ────────────────────────────────────────────────────
            $nik = preg_replace('/\D/', '', $ocr['nik'] ?? '');
            if (!empty($nik) && strlen($nik) !== 16) {
                $nik = null;
            }

            // ── GENDER ─────────────────────────────────────────────────
            $genderRaw = strtolower(trim($ocr['jenis_kelamin'] ?? ''));
            $gender = match (true) {
                str_contains($genderRaw, 'laki')      => 'male',
                str_contains($genderRaw, 'perempuan') => 'female',
                default                               => null,
            };

            // ── TANGGAL LAHIR — multi-format fallback ───────────────────
            $birthDate = null;
            if (!empty($ocr['tanggal_lahir'])) {
                $rawDate = trim(preg_replace('/^[^,]+,\s*/', '', $ocr['tanggal_lahir']));
                foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-m-y'] as $format) {
                    try {
                        $birthDate = \Carbon\Carbon::createFromFormat($format, $rawDate)->format('Y-m-d');
                        break;
                    } catch (\Exception) {
                        continue;
                    }
                }
            }

            // ── PROVINSI & KOTA dari NIK ────────────────────────────────
            // $province = null;
            // $city     = null;

            // if (!empty($nik) && strlen($nik) === 16) {
            //     $kodeKota     = substr($nik, 0, 4);
            //     $kodeProvinsi = substr($nik, 0, 2);

            //     $city = \App\Models\City::where('kode', 'LIKE', $kodeKota . '%')
            //         ->orWhere('referensi_id', 'LIKE', $kodeKota . '%')
            //         ->first(['referensi_id', 'nama', 'province_referensi_id']);

            //     if ($city) {
            //         $province = \App\Models\Province::where('referensi_id', $city->province_referensi_id)
            //             ->orWhere('kode', 'LIKE', $kodeProvinsi . '%')
            //             ->first(['referensi_id', 'nama']);
            //     }
            // }

            // // Fallback dari field OCR
            // if (!$province && !empty($ocr['provinsi'])) {
            //     $province = \App\Models\Province::whereRaw('LOWER(nama) LIKE ?', [
            //         '%' . strtolower(trim($ocr['provinsi'])) . '%'
            //     ])->first(['referensi_id', 'nama']);
            // }

            // if (!$city && !empty($ocr['kota'])) {
            //     $cityQuery = \App\Models\City::whereRaw('LOWER(nama) LIKE ?', [
            //         '%' . strtolower(trim($ocr['kota'])) . '%'
            //     ]);
            //     if ($province) {
            //         $cityQuery->where('province_referensi_id', $province->referensi_id);
            //     }
            //     $city = $cityQuery->first(['referensi_id', 'nama', 'province_referensi_id']);

            //     if (!$province && $city) {
            //         $province = \App\Models\Province::where('referensi_id', $city->province_referensi_id)
            //             ->first(['referensi_id', 'nama']);
            //     }
            // }

            return response()->json([
                'success' => true,
                'data'    => [
                    'name'                  => $ocr['nama']      ?? null,
                    'id_card_number'        => $nik ?: null,
                    'birth_date'            => $birthDate,
                    'gender'                => $gender,
                    'occupation'            => $ocr['pekerjaan'] ?? null,
                    'district'              => $ocr['kecamatan'] ?? null,
                    'village'               => $ocr['kel_desa']  ?? null,
                    'postal_code'           => $ocr['kode_pos']  ?? null,
                    // 'province_referensi_id' => $province?->referensi_id,
                    // 'province_nama'         => $province?->nama,
                    // 'city_referensi_id'     => $city?->referensi_id,
                    // 'city_nama'             => $city?->nama,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error membaca foto KTP', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca foto KTP. Pastikan foto jelas dan tidak buram.',
            ], 422);
        }
    }
}
