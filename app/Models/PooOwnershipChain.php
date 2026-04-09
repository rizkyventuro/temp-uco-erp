<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasAuditFields;


class PooOwnershipChain extends Model
{
    use HasFactory, HasAuditFields;


    protected $table = 't_poo_ownership_chains';

    protected $fillable = [
        't_poo_collection_id',
        'from_user_id',
        'to_user_id',
        'transaction_code',
        'type',
        'transferred_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $casts = [
        'type'           => 'integer',
        'transferred_at' => 'datetime',
    ];

    const TYPE_COLLECTION = 1;
    const TYPE_TRANSFER   = 2;

    const TYPE_MAP = [
        self::TYPE_COLLECTION => 'Pengambilan',
        self::TYPE_TRANSFER   => 'Transfer',
    ];

    // =================== ACCESSORS ===================

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_MAP[$this->type] ?? 'Unknown';
    }

    // =================== RELATIONS ===================

    public function pooCollection()
    {
        return $this->belongsTo(PooCollection::class, 't_poo_collection_id');
    }

    // null = origin (pengambilan pertama)
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // =================== SCOPES ===================

    public function scopeForCollection($query, string $collectionId)
    {
        return $query->where('t_poo_collection_id', $collectionId)
            ->orderBy('transferred_at');
    }

    // =================== STATIC HELPERS ===================

    public static function recordCollection(PooCollection $collection): self
    {
        return self::create([
            't_poo_collection_id' => $collection->id,
            'from_user_id'        => null, // origin
            'to_user_id'          => $collection->created_by,
            'transaction_code'    => $collection->transaction_code,
            'type'                => self::TYPE_COLLECTION,
            'transferred_at'      => $collection->collected_at,
        ]);
    }

    public static function recordTransfer(PooTransfer $transfer): void
    {
        foreach ($transfer->items as $item) {
            self::create([
                't_poo_collection_id' => $item->t_poo_collection_id,
                'from_user_id'        => $transfer->sender_id,
                'to_user_id'          => $transfer->receiver_id,
                'transaction_code'    => $transfer->transfer_code,
                'type'                => self::TYPE_TRANSFER,
                'transferred_at'      => $transfer->claimed_at,
            ]);
        }
    }
}
