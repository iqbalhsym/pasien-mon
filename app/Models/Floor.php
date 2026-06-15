<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $fillable = ['name'];

    public function wings()
    {
        return $this->hasMany(Wing::class);
    }
}
