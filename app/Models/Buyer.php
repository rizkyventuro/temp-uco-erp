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
        'code',
        'name',
        'type',
        'phone',
        'email',
        'city_id',
        'default_selling_price',
        'credit_limit',
        'payment_term_days',
        'pic',
        'npwp',
        'website',
        'address',
        'notes',
        'is_active',
        'inactive_reason',
        'foto_path',
        'foto_disk',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'default_selling_price' => 'decimal:2',
        'credit_limit'         => 'decimal:2',
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

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0] ?? ''))
            ->join('');
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()
            ->where('code', 'like', 'BUY-%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'BUY-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
