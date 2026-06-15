<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['id', 'room_id', 'bed_number', 'status', 'is_active', 'equipment_id'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
