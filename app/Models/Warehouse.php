<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'kode',
        'nama',
        'city_id',
        'tipe',
        'alamat',
        'pic',
        'telepon_pic',
        'kapasitas_maks',
        'stok_minimum',
        'harga_estimasi',
        'biaya_operasional',
        'is_active',
        'alasan_nonaktif',
        'catatan',
    ];

    protected $casts = [
        'kapasitas_maks'    => 'decimal:2',
        'stok_minimum'      => 'decimal:2',
        'harga_estimasi'    => 'decimal:2',
        'biaya_operasional' => 'decimal:2',
        'is_active'         => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ── Accessors ──────────────────────────────────────────────

    /**
     * Stok saat ini (dari tabel stok / inventory — sesuaikan dengan model stok Anda).
     * Sementara return 0 agar tidak error sebelum relasi stok dibuat.
     */
    public function getStokSaatIniAttribute(): float
    {
        // TODO: ganti dengan query real ke tabel stok
        return 0;
    }

    /**
     * Persentase utilisasi kapasitas.
     */
    public function getUtilisasiPersen(): float
    {
        if ((float) $this->kapasitas_maks <= 0) return 0;
        return round(($this->stok_saat_ini / (float) $this->kapasitas_maks) * 100, 1);
    }

    // ── Static Helpers ─────────────────────────────────────────

    public static function generateKode(): string
    {
        $last = self::withTrashed()->orderByDesc('id')->value('kode');
        if (!$last) return 'GDG-001';

        $num = (int) substr($last, 4);
        return 'GDG-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}