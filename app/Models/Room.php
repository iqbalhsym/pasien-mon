<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'wing_id', 'name', 'class', 'total_beds'];

    public function wing()
    {
        return $this->belongsTo(Wing::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }
}
