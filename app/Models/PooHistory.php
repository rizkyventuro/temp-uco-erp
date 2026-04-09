<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasAuditFields;


class PooHistory extends Model
{
    use HasFactory, HasAuditFields;


    protected $table = 't_poo_histories';

    protected $fillable = [
        'user_id',
        'counterpart_id',
        'transaction_code',
        'volume',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $casts = [
        'volume' => 'float',
        'type'   => 'integer',
    ];

    const TYPE_COLLECTION   = 1;
    const TYPE_TRANSFER_OUT = 2;
    const TYPE_TRANSFER_IN  = 3;

    const TYPE_MAP = [
        self::TYPE_COLLECTION   => 'Pengambilan',
        self::TYPE_TRANSFER_OUT => 'Transfer Keluar',
        self::TYPE_TRANSFER_IN  => 'Transfer Masuk',
    ];

    // =================== ACCESSORS ===================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_MAP[$this->type] ?? 'Unknown';
    }

    // =================== RELATIONS ===================

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // pengirim atau penerima (hanya untuk transfer)
    public function counterpart()
    {
        return $this->belongsTo(User::class, 'counterpart_id');
    }

    // =================== SCOPES ===================

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCollections($query)
    {
        return $query->where('type', self::TYPE_COLLECTION);
    }

    public function scopeTransferOut($query)
    {
        return $query->where('type', self::TYPE_TRANSFER_OUT);
    }

    public function scopeTransferIn($query)
    {
        return $query->where('type', self::TYPE_TRANSFER_IN);
    }

    // =================== STATIC HELPERS ===================

    public static function recordCollection(PooCollection $collection): self
    {
        return self::create([
            'user_id'          => $collection->created_by,
            'counterpart_id'   => null,
            'transaction_code' => $collection->transaction_code,
            'volume'           => $collection->volume,
            'type'             => self::TYPE_COLLECTION,
        ]);
    }

    public static function recordTransfer(PooTransfer $transfer): void
    {
        // Catat untuk sender
        self::create([
            'user_id'          => $transfer->sender_id,
            'counterpart_id'   => $transfer->receiver_id,
            'transaction_code' => $transfer->transfer_code,
            'volume'           => $transfer->volume_actual,
            'type'             => self::TYPE_TRANSFER_OUT,
        ]);

        // Catat untuk receiver
        self::create([
            'user_id'          => $transfer->receiver_id,
            'counterpart_id'   => $transfer->sender_id,
            'transaction_code' => $transfer->transfer_code,
            'volume'           => $transfer->volume_actual,
            'type'             => self::TYPE_TRANSFER_IN,
        ]);
    }
}
