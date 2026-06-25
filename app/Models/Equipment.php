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
        'ners_pagi', 'ners_siang', 'ners_malam', 'visit_history', 'diagnosis_lokal', 'konsul_history'
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

    public static function resetDailyVisits()
    {
        $todayStr = now()->toDateString();
        $visited = self::where('visit_dpjp', 'Sudah')->get();
        foreach ($visited as $eq) {
            $hasToday = false;
            if ($eq->visit_history) {
                $history = json_decode($eq->visit_history, true) ?: [];
                foreach ($history as $timestamp) {
                    if (date('Y-m-d', strtotime($timestamp)) === $todayStr) {
                        $hasToday = true;
                        break;
                    }
                }
            }
            if (!$hasToday) {
                $eq->update(['visit_dpjp' => 'Belum']);
            }
        }

        // Reset Dokter Konsul (DPJP Konsul)
        $konsulVisited = self::where('dokter_konsul', 'like', '%[v]%')->get();
        foreach ($konsulVisited as $eq) {
            $rawDokterKonsul = $eq->dokter_konsul;
            $parts = explode(',', $rawDokterKonsul);
            $updatedParts = [];
            $changed = false;
            
            $historyMap = [];
            if ($eq->konsul_history) {
                $historyMap = json_decode($eq->konsul_history, true) ?: [];
            }
            
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') continue;
                
                if (strpos($part, '[v] ') === 0) {
                    $docName = substr($part, 4);
                    $hasToday = false;
                    if (isset($historyMap[$docName]) && !empty($historyMap[$docName])) {
                        foreach ($historyMap[$docName] as $timestamp) {
                            if (date('Y-m-d', strtotime($timestamp)) === $todayStr) {
                                $hasToday = true;
                                break;
                            }
                        }
                    }
                    if (!$hasToday) {
                        $part = '[ ] ' . $docName;
                        $changed = true;
                    }
                }
                $updatedParts[] = $part;
            }
            if ($changed) {
                $eq->update(['dokter_konsul' => implode(', ', $updatedParts)]);
            }
        }
    }
}
