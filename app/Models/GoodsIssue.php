<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsIssue extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'goods_issues';

    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'transaction_number',
        'date',
        'buyer_id',
        'warehouse_id',
        'volume',
        'selling_price',
        'total_price',
        'status',          // pending | shipped | delivered | cancelled
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date'          => 'date',
        'volume'        => 'float',
        'selling_price' => 'float',
        'total_price'   => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending',
            'shipped'   => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'amber',
            'shipped'   => 'blue',
            'delivered' => 'emerald',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }

    // ── Static Helpers ─────────────────────────────────────────

    public static function generateTransactionNumber(): string
    {
        $year = now()->format('y');

        $last = self::withTrashed()
            ->where('transaction_number', 'like', "#GI{$year}-%")
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        if (! $last) {
            return "#GI{$year}-0001";
        }

        $num = (int) substr($last, strrpos($last, '-') + 1);

        return "#GI{$year}-" . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
