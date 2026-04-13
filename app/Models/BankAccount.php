<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BankAccount extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'name', 'type', 'is_active', 'notes'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
