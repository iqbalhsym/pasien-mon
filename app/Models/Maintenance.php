<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'equipment_id', 'jenis_pemeliharaan', 'tanggal_pelaksanaan',
        'tanggal_jadwal_berikutnya', 'tindakan_hasil', 'petugas',
        'diagnosa_gejala', 'lokasi_rawat', 'kondisi_klinis', 'metode_pembayaran'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
