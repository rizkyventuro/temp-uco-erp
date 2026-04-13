<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceipt extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'goods_receipts';

    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'transaction_number',
        'date',
        'supplier_id',
        'warehouse_id',
        'volume',
        'purchase_price',
        'total_price',
        'status',          // lunas | belum_lunas
        'due_date',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date'           => 'date',
        'due_date'       => 'date',
        'volume'         => 'float',
        'purchase_price' => 'float',
        'total_price'    => 'float',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeLunas($query)
    {
        return $query->where('status', 'lunas');
    }

    public function scopeBelumLunas($query)
    {
        return $query->where('status', 'belum_lunas');
    }

    public function scopeJatuhTempo($query)
    {
        return $query->where('status', 'belum_lunas')
            ->whereDate('due_date', '<=', now());
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'lunas'       => 'Lunas',
            'belum_lunas' => 'Belum Lunas',
            default       => ucfirst($this->status),
        };
    }

    // ── Static Helpers ─────────────────────────────────────────

    public static function generateTransactionNumber(): string
    {
        $year = now()->format('y');

        $last = self::withTrashed()
            ->where('transaction_number', 'like', "#GR{$year}-%")
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        if (! $last) {
            return "#GR{$year}-0001";
        }

        $num = (int) substr($last, strrpos($last, '-') + 1);

        return "#GR{$year}-" . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
