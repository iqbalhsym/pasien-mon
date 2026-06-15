<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wing extends Model
{
    protected $fillable = ['floor_id', 'name'];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
