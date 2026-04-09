<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Concerns\HasAuditFields;


class MasterPoo extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;


    protected $table = 'm_poos';

    
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'name',
        'address',
        'contact',
        'business_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $casts = [
        'business_type' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    const TYPE_RESTORAN     = 1;
    const TYPE_UMKM         = 2;
    const TYPE_RUMAH_TANGGA = 3;

    const TYPE_MAP = [
        self::TYPE_RESTORAN     => 'Restoran',
        self::TYPE_UMKM         => 'UMKM',
        self::TYPE_RUMAH_TANGGA => 'Rumah Tangga',
    ];

    // =================== STATIC HELPERS ===================

    public static function getBusinessTypeValue(string $label): int
    {
        return array_flip(self::TYPE_MAP)[$label] ?? self::TYPE_RESTORAN;
    }

    // =================== ACCESSORS ===================

    public function getTypeAttribute(): string
    {
        return self::TYPE_MAP[$this->business_type] ?? 'Restoran';
    }

    public function getTotalCollectedAttribute(): float
    {
        return (float) ($this->collections_sum_volume ?? 0);
    }

    // =================== RELATIONS ===================

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function collections()
    {
        return $this->hasMany(PooCollection::class, 'm_poo_id');
    }

    // =================== SCOPES ===================

    public function scopeRestoran($query)
    {
        return $query->where('business_type', self::TYPE_RESTORAN);
    }

    public function scopeUmkm($query)
    {
        return $query->where('business_type', self::TYPE_UMKM);
    }

    public function scopeRumahTangga($query)
    {
        return $query->where('business_type', self::TYPE_RUMAH_TANGGA);
    }
}
