<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'kode',
        'nama',
        'telepon',
        'email',
        'city_id',
        'kapasitas_per_bulan',
        'harga_beli_default',
        'bank',
        'no_rekening',
        'atas_nama',
        'npwp',
        'pic',
        'alamat',
        'catatan',
        'termin',
        'termin_hari',
        'is_active',
        'alasan_nonaktif',
        'foto_path',
        'foto_disk',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'kapasitas_per_bulan' => 'float',
        'harga_beli_default'  => 'float',
        'termin_hari'         => 'integer',
        'is_active'           => 'boolean',
    ];

    // ── Boot: auto-fill audit columns ─────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
        });

        static::updating(function (self $model) {
            $model->updated_by = Auth::id();
        });

        static::deleting(function (self $model) {
            $model->deleted_by = Auth::id();
            $model->saveQuietly();
        });
    }

    // ── Relations ──────────────────────────────────────────────

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto_path) {
            return null;
        }

        $disk = $this->foto_disk ?? config('filesystems.default');

        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->foto_path, now()->addHour());
        }

        return Storage::disk($disk)->url($this->foto_path);
    }

    public function getTerminLabelAttribute(): string
    {
        if ($this->termin === 'tempo' && $this->termin_hari) {
            return "Tempo ({$this->termin_hari} hari)";
        }

        return 'Cash';
    }

    public function getInisialsAttribute(): string
    {
        return collect(explode(' ', trim($this->nama)))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0] ?? ''))
            ->join('');
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

    // ── Helpers ────────────────────────────────────────────────

    public static function generateKode(): string
    {
        $last = self::withTrashed()
            ->orderByDesc('created_at')
            ->value('kode');

        if (! $last) {
            return 'SUP-001';
        }

        $number = (int) substr($last, 4);

        return 'SUP-' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);
    }
}
