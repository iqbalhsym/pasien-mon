<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Calibration extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'equipment_id', 'tanggal_kalibrasi', 'tanggal_kalibrasi_berikutnya', 
        'penyedia_jasa', 'sertifikat'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
