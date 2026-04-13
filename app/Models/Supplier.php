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
        'code',
        'name',
        'phone',
        'email',
        'city_id',
        'monthly_capacity',
        'default_purchase_price',
        'bank',
        'account_number',
        'account_holder',
        'npwp',
        'pic',
        'address',
        'notes',
        'payment_term',
        'payment_term_days',
        'is_active',
        'inactive_reason',
        'foto_path',
        'foto_disk',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'monthly_capacity'       => 'float',
        'default_purchase_price' => 'float',
        'payment_term_days'      => 'integer',
        'is_active'              => 'boolean',
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

    public function getPaymentTermLabelAttribute(): string
    {
        if ($this->payment_term === 'tempo' && $this->payment_term_days) {
            return "Tempo ({$this->payment_term_days} days)";
        }

        return 'Cash';
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))
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

    public static function generateCode(): string
    {
        $last = self::withTrashed()
            ->orderByDesc('created_at')
            ->value('code');

        if (! $last) {
            return 'SUP-001';
        }

        $number = (int) substr($last, 4);

        return 'SUP-' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);
    }
}
