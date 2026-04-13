<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'volume',
        'status',
        'estimated_arrival',
        'officer',
        'notes',
        'created_by',
        'transferred_at',
        'completed_at',
    ];

    protected $casts = [
        'volume'         => 'decimal:2',
        'transferred_at' => 'datetime',
        'completed_at'   => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'In Transit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    // ── Static Helpers ─────────────────────────────────────────

    public static function generateTransferNumber(): string
    {
        $last = self::withTrashed()->orderByDesc('id')->value('transfer_number');
        if (!$last) return 'TRF-001';

        $num = (int) substr($last, 4);
        return 'TRF-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Completed'  => 'emerald',
            'In Transit' => 'blue',
            'Pending'    => 'amber',
            'Cancelled'  => 'rose',
            default      => 'gray',
        };
    }
}
