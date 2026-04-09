<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Concerns\HasAuditFields;


class UcoExport extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;


    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        't_poo_collection_id',
        'export_code',
        'refinery_name',
        'volume',
        'export_date',
        'locked_at',
        'iscc_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $casts = [
        'volume'      => 'decimal:2',
        'export_date' => 'date',
        'locked_at'   => 'datetime',
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

    public static function generateExportCode(): string
    {
        $year = date('Y');
        $last = self::withTrashed()
            ->where('export_code', 'like', "EXP-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING(export_code, -4) AS UNSIGNED) DESC')
            ->first();

        $next = $last ? (int) substr($last->export_code, -4) + 1 : 1;

        return sprintf('EXP-%s-%04d', $year, $next);
    }

    public function collection()
    {
        return $this->belongsTo(PooCollection::class, 't_poo_collection_id');
    }
}
