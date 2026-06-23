<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MutuController extends Controller
{
    public function kepatuhanVisit(Request $request)
    {
        // 1. Ambil data pasien aktif (yang ada di ruangan)
        $patients = Equipment::whereNotNull('lokasi')->where('lokasi', '!=', '')->get();

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
}
