<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\KtpOcrService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KtpAutoVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maksimal percobaan ketika OCR service mengembalikan non-200.
     */
    public int $tries = 3;

    public function __construct(
        private int    $userId,
        private array  $profileData,
        private string $photoPath,
        private string $photoDisk,
    ) {}

    // =========================================================
    // HANDLE
    // =========================================================

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $photoFile = $this->getPhotoFile();

        if (!$photoFile) {
            Log::warning('KtpAutoVerificationJob: gagal ambil foto dari storage', [
                'user_id' => $this->userId,
                'path'    => $this->photoPath,
                'disk'    => $this->photoDisk,
            ]);

            return;
        }

        // Jika OCR service mengembalikan non-200, KtpOcrService::extract() melempar
        // exception → queue otomatis retry hingga $tries kali.
        $ocrService = new KtpOcrService();
        $result     = $ocrService->extract($photoFile);

        $ocr = $result['data'] ?? [];

        [$status, $note] = $this->compareData($ocr);

        DB::transaction(function () use ($user, $status, $note) {
            $user->update([
                'is_verified_by_admin' => $status,
                'verified_at'          => $status === 2 ? now() : null,
            ]);

            $user->profile()->update([
                'verification_note' => $note,
                'noted_by'          => null,
                'noted_at'          => $note ? now() : null,
            ]);
        });

        $this->sendNotification($user, $status);
    }

    // =========================================================
    // FAILED — dipanggil setelah semua retry habis
    // =========================================================

    public function failed(\Throwable $exception): void
    {
        Log::error('KtpAutoVerificationJob: gagal setelah semua retry', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        // User tetap pending → kirim notifikasi ke admin untuk review manual
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            Notification::create([
                'type'            => Notification::TYPE_INFO,
                'title'           => 'Profil Perlu Verifikasi',
                'message'         => "User {$user->name} menunggu verifikasi (verifikasi otomatis gagal, perlu ditinjau manual).",
                'notifiable_id'   => $admin->id,
                'notifiable_type' => User::class,
                'sender_id'       => $user->id,
                'url'             => route('management-user.index', ['status' => 'pending']),
            ]);
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function getPhotoFile(): ?\Illuminate\Http\UploadedFile
    {
        $diskMap = ['local' => 'public'];
        $disk    = $diskMap[$this->photoDisk] ?? ($this->photoDisk ?? config('filesystems.default'));
        $path    = $this->photoPath;

        $ext      = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'ktp_') . '.' . $ext;

        try {
            $contents = Storage::disk($disk)->get($path);
        } catch (\Exception $e) {
            Log::warning('KtpAutoVerificationJob: gagal ambil file dari storage', [
                'user_id' => $this->userId,
                'path'    => $path,
                'disk'    => $disk,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }

        file_put_contents($tempPath, $contents);
        register_shutdown_function(fn() => @unlink($tempPath));

        $mimeMap  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $mimeType = $mimeMap[strtolower($ext)] ?? 'image/jpeg';

        return new \Illuminate\Http\UploadedFile(
            path: $tempPath,
            originalName: basename($path),
            mimeType: $mimeType,
            error: UPLOAD_ERR_OK,
            test: true,
        );
    }

    private function compareData(array $ocr): array
    {
        $normalize    = fn($str) => strtolower(trim(preg_replace('/\s+/', ' ', $str ?? '')));
        $normalizeNik = fn($str) => preg_replace('/\D/', '', $str ?? '');

        $inputBirthDate = Carbon::parse($this->profileData['birth_date'])->format('d-m-Y');
        $ocrBirthDate   = trim(preg_replace('/^[^,]+,\s*/', '', $ocr['tanggal_lahir'] ?? ''));

        $genderMap   = ['male' => 'laki-laki', 'female' => 'perempuan'];
        $inputGender = $genderMap[$this->profileData['gender']] ?? $this->profileData['gender'];

        $failed = [];

        // ── Wajib (4 field utama) ──────────────────────────────────────
        if ($normalizeNik($this->profileData['id_card_number']) !== $normalizeNik($ocr['nik'] ?? '')) {
            $failed[] = 'NIK';
        }

        if ($normalize($this->profileData['name']) !== $normalize($ocr['nama'] ?? '')) {
            $failed[] = 'Nama';
        }

        if ($inputBirthDate !== $normalize($ocrBirthDate)) {
            $failed[] = 'Tanggal Lahir';
        }

        if ($normalize($inputGender) !== $normalize($ocr['jenis_kelamin'] ?? '')) {
            $failed[] = 'Jenis Kelamin';
        }

        // ── Tambahan (hanya jika OCR mengembalikan nilai) ─────────────
        if (!empty($ocr['pekerjaan']) && !empty($this->profileData['occupation'])) {
            if ($normalize($this->profileData['occupation']) !== $normalize($ocr['pekerjaan'])) {
                $failed[] = 'Pekerjaan';
            }
        }

        if (!empty($ocr['kecamatan']) && !empty($this->profileData['district'])) {
            if ($normalize($this->profileData['district']) !== $normalize($ocr['kecamatan'])) {
                $failed[] = 'Kecamatan';
            }
        }

        if (!empty($ocr['kelurahan']) && !empty($this->profileData['village'])) {
            if ($normalize($this->profileData['village']) !== $normalize($ocr['kelurahan'])) {
                $failed[] = 'Kelurahan';
            }
        }

        if (empty($failed)) {
            return [2, null];
        }

        $note = 'Verifikasi otomatis gagal. Data tidak cocok dengan foto KTP: '
            . implode(', ', $failed) . '.';

        return [3, $note];
    }

    private function sendNotification(User $user, int $status): void
    {
        match ($status) {
            2 => Notification::create([
                'type'            => Notification::TYPE_SUCCESS,
                'title'           => 'Profil Disetujui Otomatis',
                'message'         => 'Data KTP Anda cocok. Profil Anda telah diverifikasi secara otomatis.',
                'notifiable_id'   => $user->id,
                'notifiable_type' => User::class,
            ]),
            3 => Notification::create([
                'type'            => Notification::TYPE_ERROR,
                'title'           => 'Verifikasi Otomatis Gagal',
                'message'         => 'Data yang Anda isi tidak cocok dengan foto KTP. Silakan periksa kembali data Anda.',
                'notifiable_id'   => $user->id,
                'notifiable_type' => User::class,
                'url'             => route('complete-profile.show'),
            ]),
            default => null,
        };
    }
}
