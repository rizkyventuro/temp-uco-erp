<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'birth_date',
        'gender',
        'occupation',
        'address',
        'province_referensi_id',
        'city_referensi_id',
        'district',
        'village',
        'postal_code',
        'id_card_number',
        'id_card_photo_path',
        'id_card_photo_disk',
        'verification_note',
        'noted_by',
        'noted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'noted_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function noter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'noted_by');
    }
}
