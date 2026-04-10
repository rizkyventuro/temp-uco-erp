<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Buyer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'telepon',
        'email',
        'city_id',
        'harga_jual_default',
        'limit_kredit',
        'termin_hari',
        'pic',
        'npwp',
        'website',
        'alamat',
        'catatan',
        'is_active',
        'alasan_nonaktif',
        'foto_path',
        'foto_disk',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'harga_jual_default' => 'decimal:2',
        'limit_kredit'       => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) return null;
        $disk = $this->foto_disk ?? config('filesystems.default');
        return Storage::disk($disk)->url($this->foto_path);
    }

    public function getInisialsAttribute(): string
    {
        return collect(explode(' ', $this->nama))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0] ?? ''))
            ->join('');
    }

    public static function generateKode(): string
    {
        $last = static::withTrashed()
            ->where('kode', 'like', 'BUY-%')
            ->orderByDesc('kode')
            ->value('kode');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'BUY-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
