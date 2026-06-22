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
        'merk', 'type', 'serial_number', 'tanggal_lahir', 'lokasi', 'lantai', 'kondisi',
        'spesifikasi', 'tanggal_pengadaan', 'jam', 'gambar', 'status_kepemilikan',
        'registered_date', 'los_aktual', 'dpjp_utama', 'dpjp_raber', 'dokter_konsul',
        'visit_dpjp', 'planning_pasien', 'rencana_pulang', 'npja', 'ews',
        'tingkat_ketergantungan', 'ners_bertugas', 'alkes_invasif', 'tindakan_detail',
        'gender', 'guarantor', 'hak_kelas',
        'billing_aktual', 'pagu_budget', 'persentase_pagu', 'kategori_pasien',
        'target_los', 'notes_num', 'notes_case_manager', 'riw_lab', 'riw_rad',
        'riw_obat', 'rencana_prosedur', 'rencana_diagnostik', 'rencana_konsul',
        'ners_pagi', 'ners_siang', 'ners_malam'
    ];

    public function calibrations()
    {
        return $this->hasMany(Calibration::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function bed()
    {
        return $this->hasOne(Bed::class);
    }
}
