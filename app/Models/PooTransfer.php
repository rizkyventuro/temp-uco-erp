<?php

namespace App\Models;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Concerns\HasAuditFields;


class PooTransfer extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;


    protected $table = 't_poo_transfers';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'transfer_code',
        'volume_requested',
        'volume_actual',
        'status',
        'claimed_at',
        'expires_at',   // ← baru
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $casts = [
        'volume_requested' => 'float',
        'volume_actual'    => 'float',
        'status'           => 'integer',
        'claimed_at'       => 'datetime',
        'expires_at'       => 'datetime', // ← baru
    ];

    const STATUS_PENDING   = 0;
    const STATUS_CLAIMED   = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_EXPIRED = 3;

    const STATUS_MAP = [
        self::STATUS_PENDING   => 'Pending',
        self::STATUS_CLAIMED   => 'Claimed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_EXPIRED   => 'Expired',
    ];

    const TRANSFER_LIFETIME_HOURS = 24;

    // =================== BOOT ===================

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->expires_at    = now()->addHours(self::TRANSFER_LIFETIME_HOURS);
        });
    }

    public static function makeHashids(): Hashids
    {
        return new Hashids(env('HASHIDS_SALT', 'fallback-salt'), 4);
    }

    public static function generateCode(string $Id, string $collectionDate): string
    {
        $hashids = self::makeHashids();

        // UUID/string → konsisten numeric (positive)
        $numericId = sprintf('%u', crc32($Id));

        // Encode dengan hashids
        $hashedId = $hashids->encode((int) $numericId);

        // Format tanggal YYMMDD
        $dateStr = \Carbon\Carbon::parse($collectionDate)->format('ymd');

        // Random 4 karakter (tanpa karakter ambigu O,0,I,1)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $random = '';
        for ($i = 0; $i < 4; $i++) {
            $random .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return "UCO-{$hashedId}-{$dateStr}-{$random}";
    }

    // =================== ACCESSORS ===================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_MAP[$this->status] ?? 'Unknown';
    }

    /**
     * URL lengkap yang di-encode ke dalam QR Code.
     * Penerima scan → langsung ke halaman claim.
     */
    public function getClaimUrlAttribute(): string
    {
        return route('transfers.claim', ['code' => $this->transfer_code]);
    }

    // =================== STATUS HELPERS ===================

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isClaimable(): bool
    {
        return $this->status === self::STATUS_PENDING
            && ! $this->isExpired()
            && ! $this->isUsed();
    }

    public function markAsUsed(int $receiverId): void
    {
        $this->update([
            'receiver_id' => $receiverId,
            'status'      => self::STATUS_CLAIMED,
            'claimed_at'  => now(),
        ]);
    }

    // =================== RELATIONS ===================

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function items()
    {
        return $this->hasMany(PooTransferItem::class, 't_poo_transfer_id');
    }

    public function collections()
    {
        return $this->belongsToMany(
            PooCollection::class,
            't_poo_transfer_items',
            't_poo_transfer_id',
            't_poo_collection_id'
        );
    }
}
