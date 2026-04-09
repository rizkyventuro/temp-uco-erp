<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'city', 'province'];

    protected $appends = ['nama_stasiun'];

    public function getNamaStasiunAttribute()
    {
        return $this->name;
    }
}
