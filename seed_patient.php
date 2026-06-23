<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Equipment;

$eq = new Equipment();
$eq->merk = 'Budi Raharjo, Tn';
$eq->serial_number = '00891234';
$eq->type = 'Umum';
$eq->lokasi = 'Melati - Kelas 1';
$eq->lantai = '2';
$eq->kondisi = 'Baik';
$eq->tanggal_pengadaan = now()->subDays(3)->format('Y-m-d');
$eq->registered_date = now()->subDays(3)->format('Y-m-d');
$eq->status_kepemilikan = 'RSUI';
$eq->dpjp_utama = 'dr. Andi Pratama, Sp.PD';
$eq->dokter_konsul = '[v] dr. Citra Lestari, Sp.JP, [ ] dr. Budi Santoso, Sp.An';
$eq->visit_dpjp = 'Sudah';
$eq->gender = 'Laki-laki';
$eq->hak_kelas = 'Kelas 1';
$eq->guarantor = 'BPJS Kesehatan';
$eq->kategori_pasien = 'IGD Medis';
$eq->target_los = '5';
$eq->notes_num = 'Pasien stabil';
$eq->notes_case_manager = 'ACC Pulang besok jika lab baik';
$eq->save();

echo "Patient seeded successfully.\n";
