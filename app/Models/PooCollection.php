<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Services\AmazonServerService;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class PooCollection extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;


    protected $table = 't_poo_collections';

    const STATUS_COLLECTED = 1;
    const STATUS_EXPORTED  = 2;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'm_poo_id',
        'current_owner_id',
        'transaction_code',
        'created_by',
        'volume',
        'collected_at',
        'photo',
        'signature',
        'notes',
        'status',
        'updated_by',
        'deleted_by',
        'signature_disk',
        'photo_disk'
    ];


    protected $casts = [
        'volume'       => 'float',
        'collected_at' => 'date',
        'status'       => 'integer',
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

    // =================== RELATIONS ===================

    public function masterPoo()
    {
        return $this->belongsTo(MasterPoo::class, 'm_poo_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentOwner()
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    // =================== SCOPE ===================
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('current_owner_id', $userId);
    }

    public function scopeNotExported($query)
    {
        return $query->where('status', '!=', self::STATUS_EXPORTED);
    }

    // =================== HELPERS ===================
    public function markAsExported(): void
    {
        $this->update(['status' => self::STATUS_EXPORTED]);
    }


    // =================== ACCESSORS ===================

    public function getPhotoUrlAttribute(): ?string
    {
        return AmazonServerService::resolveUrl($this->photo, $this->photo_disk);
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return AmazonServerService::resolveUrl($this->signature, $this->signature_disk);
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

        return "POO-{$hashedId}-{$dateStr}-{$random}";
    }
}
