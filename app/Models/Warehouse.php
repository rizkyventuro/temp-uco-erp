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
        'code',
        'name',
        'city_id',
        'type',
        'address',
        'pic',
        'pic_phone',
        'capacity_max',
        'min_stock',
        'price_estimate',
        'operating_cost',
        'is_active',
        'inactive_reason',
        'notes',
    ];

    protected $casts = [
        'capacity_max'   => 'decimal:2',
        'min_stock'      => 'decimal:2',
        'price_estimate' => 'decimal:2',
        'operating_cost' => 'decimal:2',
        'is_active'      => 'boolean',
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
     * Current stock (from inventory table — replace with real query when available).
     */
    public function getCurrentStockAttribute(): float
    {
        // TODO: replace with real query against inventory table
        return 0;
    }

    /**
     * Capacity occupancy percentage.
     */
    public function getOccupancyPercent(): float
    {
        if ((float) $this->capacity_max <= 0) return 0;
        return round(($this->current_stock / (float) $this->capacity_max) * 100, 1);
    }

    // ── Static Helpers ─────────────────────────────────────────

    public static function generateCode(): string
    {
        $last = self::withTrashed()->orderByDesc('id')->value('code');
        if (!$last) return 'GDG-001';

        $num = (int) substr($last, 4);
        return 'GDG-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}