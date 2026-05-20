<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Equipment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'equipments';
    protected $fillable = [
        'merk', 'type', 'serial_number', 'tanggal_lahir', 'lokasi', 'kondisi',
        'spesifikasi', 'tanggal_pengadaan', 'gambar', 'status_kepemilikan'
    ];

    public function calibrations()
    {
        return $this->hasMany(Calibration::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
}
