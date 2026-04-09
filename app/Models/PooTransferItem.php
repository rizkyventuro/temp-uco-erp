<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasAuditFields;


class PooTransferItem extends Model
{
    use HasFactory, HasAuditFields;


    protected $table = 't_poo_transfer_items';

    protected $fillable = [
        't_poo_transfer_id',
        't_poo_collection_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    // =================== RELATIONS ===================

    public function transfer()
    {
        return $this->belongsTo(PooTransfer::class, 't_poo_transfer_id');
    }

    public function pooCollection()
    {
        return $this->belongsTo(PooCollection::class, 't_poo_collection_id');
    }

    // Akses MasterPoo lewat PooCollection
    public function masterPoo()
    {
        return $this->hasOneThrough(
            MasterPoo::class,
            PooCollection::class,
            't_poo_collection_id', // FK di t_poo_transfer_items → t_poo_collections
            'id',                   // PK di m_poos
            'id',                   // PK di t_poo_transfer_items
            'm_poo_id'              // FK di t_poo_collections → m_poos
        );
    }
}
