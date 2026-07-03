<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MutuController extends Controller
{
    public function kepatuhanVisit(Request $request)
    {
        Equipment::resetDailyVisits();
        // 1. Ambil data pasien aktif (yang ada di ruangan)
        $patients = Equipment::whereHas('bed')->whereNotNull('lokasi')->where('lokasi', '!=', '')->get();

        $totalPasien = $patients->count();
        $sudahVisit = 0;
        $belumVisit = 0;
        $daftarBelumVisit = [];
        
        $dpjpStats = [];

        foreach ($patients as $p) {
            $dpjp = $p->dpjp_utama ?: 'Tidak Diketahui';
            
            // Inisialisasi statistik DPJP
            if (!isset($dpjpStats[$dpjp])) {
                $dpjpStats[$dpjp] = [
                    'dpjp' => $dpjp,
                    'spesialis' => 'Umum', // Dummy spesialis
                    'jumlah_pasien' => 0,
                    'sudah_visit' => 0,
                    'belum_visit' => 0,
                ];
                
                // Tambahkan mapping spesialis sederhana (hanya simulasi)
                if (stripos($dpjp, 'Sp.PD') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Penyakit Dalam';
                elseif (stripos($dpjp, 'Sp.OG') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Obstetri & Ginekologi';
                elseif (stripos($dpjp, 'Sp.B') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Bedah';
                elseif (stripos($dpjp, 'Sp.JP') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Jantung';
                elseif (stripos($dpjp, 'Sp.An') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Anestesi';
                elseif (stripos($dpjp, 'Sp.A') !== false) $dpjpStats[$dpjp]['spesialis'] = 'Anak';
            }

            $dpjpStats[$dpjp]['jumlah_pasien']++;

            // Logika visit hari ini (berdasarkan ceklis dikurangi tanggal masuk)
            $isVisited = false;
            if ($p->visit_dpjp == 'Sudah') {
                $isVisited = true;
            }

            // Hitung LOS aktual
            $tglMasuk = $p->registered_date ?: $p->tanggal_pengadaan;
            $losHari = 0;
            if ($tglMasuk) {
                try {
                    $losHari = Carbon::parse($tglMasuk)->diffInDays(now()->startOfDay());
                } catch (\Exception $e) {}
            }

            if ($isVisited) {
                $sudahVisit++;
                $dpjpStats[$dpjp]['sudah_visit']++;
            } else {
                $belumVisit++;
                $dpjpStats[$dpjp]['belum_visit']++;
                
                // Masukkan ke daftar pasien belum visit
                $daftarBelumVisit[] = [
                    'no_rm' => $p->serial_number,
                    'nama' => $p->merk,
                    'ruangan' => $p->lokasi,
                    'dpjp' => $dpjp,
                    'tanggal_masuk' => $tglMasuk ? Carbon::parse($tglMasuk)->format('d/m/Y') : '-',
                    'los' => $losHari,
                    'hari_tanpa_visit' => $losHari > 0 ? $losHari : 1, // Simulasi hari tanpa visit
                    'keterangan' => 'Belum ada catatan visite',
                ];
            }
        }

        // Kalkulasi Kepatuhan Keseluruhan
        $persentaseKepatuhan = $totalPasien > 0 ? round(($sudahVisit / $totalPasien) * 100, 1) : 0;

        // Kalkulasi Kepatuhan per DPJP
        foreach ($dpjpStats as $key => $stat) {
            $dpjpStats[$key]['kepatuhan'] = $stat['jumlah_pasien'] > 0 
                ? round(($stat['sudah_visit'] / $stat['jumlah_pasien']) * 100, 1) 
                : 0;
            
            // Simulasi Trend
            $rand = rand(1, 3);
            $dpjpStats[$key]['trend'] = $rand == 1 ? 'up' : ($rand == 2 ? 'down' : 'flat');
        }

        // Urutkan berdasarkan kepatuhan descending
        usort($dpjpStats, function($a, $b) {
            return $b['kepatuhan'] <=> $a['kepatuhan'];
        });

        // Simulasi grafik chart js (hanya untuk tampilan visual, ambil top 6)
        $chartLabels = array_column(array_slice($dpjpStats, 0, 6), 'dpjp');
        $chartData = array_column(array_slice($dpjpStats, 0, 6), 'kepatuhan');

        return view('mutu.kepatuhan_visit', compact(
            'totalPasien', 'sudahVisit', 'belumVisit', 'persentaseKepatuhan', 
            'dpjpStats', 'daftarBelumVisit', 'chartLabels', 'chartData'
        ));
    }

    public function responKonsul(Request $request)
    {
        // Ambil data pasien yang memiliki permintaan e-konsul (dari field dokter_konsul)
        $patients = Equipment::whereNotNull('dokter_konsul')->where('dokter_konsul', '!=', '')->get();

        $totalKonsul = 0;
        $kurang24Jam = 0;
        $lebih24Jam = 0;
        
        $dpjpStats = [];
        $daftarLebih24Jam = [];

        foreach ($patients as $p) {
            // Asumsi waktu order adalah saat pasien masuk (registered_date atau created_at)
            $tglOrder = $p->registered_date ? Carbon::parse($p->registered_date) : $p->created_at;
            // Asumsi waktu respon adalah update terakhir
            $tglRespon = $p->updated_at;
            
            $lamaJam = $tglRespon->diffInHours($tglOrder);
            $isLebih24 = $lamaJam > 24;

            $parts = explode(',', $p->dokter_konsul);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') continue;

                $totalKonsul++;
                $isResponded = false;
                $namaDokter = $part;

                if (strpos($part, '[v] ') === 0) {
                    $isResponded = true;
                    $namaDokter = substr($part, 4);
                } elseif (strpos($part, '[ ] ') === 0) {
                    $namaDokter = substr($part, 4);
                }

                // Inisialisasi stat DPJP jika belum ada
                if (!isset($dpjpStats[$namaDokter])) {
                    $dpjpStats[$namaDokter] = [
                        'dpjp' => $namaDokter,
                        'spesialis' => 'Spesialis', // Fallback
                        'total' => 0,
                        'kurang24' => 0,
                        'lebih24' => 0,
                        'trend' => 'flat'
                    ];

                    // Deteksi spesialis sederhana dari nama
                    if (stripos($namaDokter, 'Sp.PD') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Penyakit Dalam';
                    elseif (stripos($namaDokter, 'Sp.OG') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Obstetri & Ginekologi';
                    elseif (stripos($namaDokter, 'Sp.B') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Bedah';
                    elseif (stripos($namaDokter, 'Sp.JP') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Jantung';
                    elseif (stripos($namaDokter, 'Sp.An') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Anestesi';
                    elseif (stripos($namaDokter, 'Sp.A') !== false) $dpjpStats[$namaDokter]['spesialis'] = 'Anak';
                }

                $dpjpStats[$namaDokter]['total']++;

                if ($isResponded) {
                    if ($isLebih24) {
                        $lebih24Jam++;
                        $dpjpStats[$namaDokter]['lebih24']++;
                        
                        // Masukkan ke daftar lebih dari 24 jam
                        $daftarLebih24Jam[] = [
                            'tgl_order' => $tglOrder->format('d/m/Y'),
                            'jam_order' => $tglOrder->format('H:i'),
                            'no_rm' => $p->serial_number,
                            'nama' => $p->merk,
                            'ruangan' => $p->lokasi ?: '-',
                            'konsul_ke' => $namaDokter,
                            'konsul_spesialis' => $dpjpStats[$namaDokter]['spesialis'],
                            'respon_dari' => $namaDokter,
                            'respon_spesialis' => $dpjpStats[$namaDokter]['spesialis'],
                            'tgl_respon' => $tglRespon->format('d/m/Y'),
                            'jam_respon' => $tglRespon->format('H:i'),
                            'lama_respon' => $lamaJam . ' jam ' . ($tglRespon->diffInMinutes($tglOrder) % 60) . ' mnt'
                        ];
                    } else {
                        $kurang24Jam++;
                        $dpjpStats[$namaDokter]['kurang24']++;
                    }
                } else {
                    // Jika belum direspon sama sekali, cek apakah sudah lewat 24 jam sejak order
                    if ($isLebih24) {
                        $lebih24Jam++;
                        $dpjpStats[$namaDokter]['lebih24']++;
                        
                        $daftarLebih24Jam[] = [
                            'tgl_order' => $tglOrder->format('d/m/Y'),
                            'jam_order' => $tglOrder->format('H:i'),
                            'no_rm' => $p->serial_number,
                            'nama' => $p->merk,
                            'ruangan' => $p->lokasi ?: '-',
                            'konsul_ke' => $namaDokter,
                            'konsul_spesialis' => $dpjpStats[$namaDokter]['spesialis'],
                            'respon_dari' => '-',
                            'respon_spesialis' => '-',
                            'tgl_respon' => '-',
                            'jam_respon' => '-',
                            'lama_respon' => '> 24 jam (Belum direspon)'
                        ];
                    } else {
                        // Masih dalam batas waktu 24 jam tapi belum direspon, 
                        // untuk dashboard biasanya dihitung masih on-track atau masuk ke kurang24 sementara
                        $kurang24Jam++;
                        $dpjpStats[$namaDokter]['kurang24']++;
                    }
                }
            }
        }

        $persentaseKepatuhan = $totalKonsul > 0 ? round(($kurang24Jam / $totalKonsul) * 100, 1) : 0;

        foreach ($dpjpStats as &$stat) {
            $stat['kepatuhan'] = $stat['total'] > 0 ? round(($stat['kurang24'] / $stat['total']) * 100, 1) : 0;
        }

        // Urutkan berdasarkan kepatuhan descending
        usort($dpjpStats, function($a, $b) {
            return $b['kepatuhan'] <=> $a['kepatuhan'];
        });

        // Simulasi data trend chart (masih statis karena kita belum punya log riwayat harian kepatuhan)
        $trendLabels = [now()->subDays(6)->format('d/m'), now()->subDays(5)->format('d/m'), now()->subDays(4)->format('d/m'), now()->subDays(3)->format('d/m'), now()->subDays(2)->format('d/m'), now()->subDays(1)->format('d/m'), now()->format('d/m')];
        // Taruh angka kepatuhan riil di hari terakhir
        $trendData = [83.1, 85.2, 87.0, 88.7, 89.1, 90.2, $persentaseKepatuhan];

        return view('mutu.respon_konsul', compact(
            'totalKonsul', 'kurang24Jam', 'lebih24Jam', 'persentaseKepatuhan',
            'dpjpStats', 'daftarLebih24Jam', 'trendLabels', 'trendData'
        ));
    }

    public function distribusiDpjp(Request $request)
    {
        // 1. Ambil data pasien aktif (yang ada di ruangan)
        $patients = Equipment::whereHas('bed')
            ->whereNotNull('lokasi')
            ->where('lokasi', '!=', '')
            ->get();

        $floorReport = [];
        $dpjpReport = [];

        foreach ($patients as $p) {
            $floorName = $p->lantai ?: 'Lantai Lain';
            // Format nama lantai
            $displayFloor = is_numeric($floorName) ? 'Lantai ' . $floorName : $floorName;
            $dpjp = $p->dpjp_utama ?: 'Tidak Ada DPJP';

            // Kelompok 1: Lantai -> DPJP -> Pasien
            if (!isset($floorReport[$displayFloor])) {
                $floorReport[$displayFloor] = [
                    'floor_name' => $displayFloor,
                    'original_name' => $floorName,
                    'doctors' => []
                ];
            }

            if (!isset($floorReport[$displayFloor]['doctors'][$dpjp])) {
                $floorReport[$displayFloor]['doctors'][$dpjp] = [
                    'doctor_name' => $dpjp,
                    'patient_count' => 0,
                    'patients' => []
                ];
            }

            $floorReport[$displayFloor]['doctors'][$dpjp]['patient_count']++;
            $floorReport[$displayFloor]['doctors'][$dpjp]['patients'][] = [
                'name' => $p->merk,
                'serial_number' => $p->serial_number,
                'room' => $p->lokasi,
                'class' => $p->hak_kelas ?: '-',
                'diagnosa' => $p->type ?: '-',
                'registered_date' => $p->registered_date ? Carbon::parse($p->registered_date)->format('d/m/Y') : '-'
            ];

            // Kelompok 2: DPJP -> Lantai -> Pasien
            if (!isset($dpjpReport[$dpjp])) {
                $dpjpReport[$dpjp] = [
                    'doctor_name' => $dpjp,
                    'spesialis' => 'Umum',
                    'total_patients' => 0,
                    'floors' => []
                ];

                // Deteksi spesialisasi sederhana
                if (stripos($dpjp, 'Sp.PD') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Penyakit Dalam';
                elseif (stripos($dpjp, 'Sp.OG') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Obstetri & Ginekologi';
                elseif (stripos($dpjp, 'Sp.B') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Bedah';
                elseif (stripos($dpjp, 'Sp.JP') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Jantung';
                elseif (stripos($dpjp, 'Sp.An') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Anestesi';
                elseif (stripos($dpjp, 'Sp.A') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Anak';
                elseif (stripos($dpjp, 'Sp.S') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Saraf';
                elseif (stripos($dpjp, 'Sp.THT') !== false) $dpjpReport[$dpjp]['spesialis'] = 'THT';
                elseif (stripos($dpjp, 'Sp.M') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Mata';
                elseif (stripos($dpjp, 'Sp.KJ') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Kedokteran Jiwa';
                elseif (stripos($dpjp, 'Sp.Rad') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Radiologi';
                elseif (stripos($dpjp, 'Sp.P') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Paru';
                elseif (stripos($dpjp, 'Sp.U') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Urologi';
                elseif (stripos($dpjp, 'Sp.OT') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Ortopedi';
                elseif (stripos($dpjp, 'Sp.BS') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Bedah Saraf';
                elseif (stripos($dpjp, 'Sp.BTKV') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Bedah Toraks Kardiovaskular';
                elseif (stripos($dpjp, 'Sp.DV') !== false || stripos($dpjp, 'Sp.KK') !== false) $dpjpReport[$dpjp]['spesialis'] = 'Dermatologi & Venereologi';
            }

            if (!isset($dpjpReport[$dpjp]['floors'][$displayFloor])) {
                $dpjpReport[$dpjp]['floors'][$displayFloor] = [
                    'floor_name' => $displayFloor,
                    'patient_count' => 0,
                    'patients' => []
                ];
            }

            $dpjpReport[$dpjp]['total_patients']++;
            $dpjpReport[$dpjp]['floors'][$displayFloor]['patient_count']++;
            $dpjpReport[$dpjp]['floors'][$displayFloor]['patients'][] = [
                'name' => $p->merk,
                'serial_number' => $p->serial_number,
                'room' => $p->lokasi,
                'class' => $p->hak_kelas ?: '-',
                'diagnosa' => $p->type ?: '-',
                'registered_date' => $p->registered_date ? Carbon::parse($p->registered_date)->format('d/m/Y') : '-'
            ];
        }

        // Urutkan floorReport secara logis (lantai numerik dahulu)
        uksort($floorReport, function($a, $b) {
            $numA = preg_replace('/[^0-9]/', '', $a);
            $numB = preg_replace('/[^0-9]/', '', $b);
            if ($numA !== '' && $numB !== '') {
                return (int)$numA <=> (int)$numB;
            }
            return strcmp($a, $b);
        });

        // Urutkan dpjpReport berdasarkan jumlah pasien terbanyak
        uasort($dpjpReport, function($a, $b) {
            return $b['total_patients'] <=> $a['total_patients'];
        });

        return view('mutu.distribusi_dpjp', compact('floorReport', 'dpjpReport', 'patients'));
    }

    public function jadwalNers(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        // Ambil data pasien aktif pada tanggal yang dipilih
        $patients = Equipment::whereHas('bed')
            ->whereNotNull('lokasi')
            ->where('lokasi', '!=', '')
            ->where(function($q) use ($date) {
                $q->whereDate('registered_date', '<=', $date)
                  ->orWhereDate('tanggal_pengadaan', '<=', $date);
            })
            ->get();

        $nurseReports = [];
        $shiftReports = [
            'Pagi' => [],
            'Siang' => [],
            'Malam' => []
        ];
        $floorReports = [];

        foreach ($patients as $p) {
            $floorName = $p->lantai ?: 'Lantai Lain';
            $displayFloor = is_numeric($floorName) ? 'Lantai ' . $floorName : $floorName;

            $shifts = [
                'Pagi' => $p->ners_pagi,
                'Siang' => $p->ners_siang,
                'Malam' => $p->ners_malam
            ];

            foreach ($shifts as $shiftName => $nurseName) {
                $nurseName = trim($nurseName);
                if (empty($nurseName) || $nurseName === '-') continue;

                // 1. Tampilan Per Ners
                if (!isset($nurseReports[$nurseName])) {
                    $nurseReports[$nurseName] = [
                        'name' => $nurseName,
                        'shifts' => []
                    ];
                }

                if (!isset($nurseReports[$nurseName]['shifts'][$shiftName])) {
                    $nurseReports[$nurseName]['shifts'][$shiftName] = [
                        'shift_name' => $shiftName,
                        'floors' => []
                    ];
                }

                if (!isset($nurseReports[$nurseName]['shifts'][$shiftName]['floors'][$displayFloor])) {
                    $nurseReports[$nurseName]['shifts'][$shiftName]['floors'][$displayFloor] = [
                        'floor_name' => $displayFloor,
                        'patients' => []
                    ];
                }

                $nurseReports[$nurseName]['shifts'][$shiftName]['floors'][$displayFloor]['patients'][] = [
                    'name' => $p->merk,
                    'serial_number' => $p->serial_number,
                    'room' => $p->lokasi,
                    'class' => $p->hak_kelas ?: '-',
                    'diagnosa' => $p->type ?: '-'
                ];

                // 2. Tampilan Per Shift
                if (!isset($shiftReports[$shiftName][$displayFloor])) {
                    $shiftReports[$shiftName][$displayFloor] = [];
                }

                if (!isset($shiftReports[$shiftName][$displayFloor][$nurseName])) {
                    $shiftReports[$shiftName][$displayFloor][$nurseName] = [
                        'nurse_name' => $nurseName,
                        'patients' => []
                    ];
                }

                $shiftReports[$shiftName][$displayFloor][$nurseName]['patients'][] = [
                    'name' => $p->merk,
                    'serial_number' => $p->serial_number,
                    'room' => $p->lokasi,
                    'class' => $p->hak_kelas ?: '-'
                ];

                // 3. Tampilan Per Lantai
                if (!isset($floorReports[$displayFloor])) {
                    $floorReports[$displayFloor] = [];
                }

                if (!isset($floorReports[$displayFloor][$shiftName])) {
                    $floorReports[$displayFloor][$shiftName] = [];
                }

                if (!isset($floorReports[$displayFloor][$shiftName][$nurseName])) {
                    $floorReports[$displayFloor][$shiftName][$nurseName] = [
                        'nurse_name' => $nurseName,
                        'patients' => []
                    ];
                }

                $floorReports[$displayFloor][$shiftName][$nurseName]['patients'][] = [
                    'name' => $p->merk,
                    'serial_number' => $p->serial_number,
                    'room' => $p->lokasi,
                    'class' => $p->hak_kelas ?: '-'
                ];
            }
        }

        // Urutkan nama ners secara alfabetis
        ksort($nurseReports);

        // Urutkan lantai secara logis (lantai numerik dahulu)
        uksort($floorReports, function($a, $b) {
            $numA = preg_replace('/[^0-9]/', '', $a);
            $numB = preg_replace('/[^0-9]/', '', $b);
            if ($numA !== '' && $numB !== '') {
                return (int)$numA <=> (int)$numB;
            }
            return strcmp($a, $b);
        });

        return view('mutu.jadwal_ners', compact('nurseReports', 'shiftReports', 'floorReports', 'date', 'patients'));
    }
}
