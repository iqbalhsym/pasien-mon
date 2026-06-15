<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        $query = Equipment::query();

        if ($request->filled('lantai')) {
            $query->where('lantai', $request->input('lantai'));
        }

        if ($request->filled('wing')) {
            $wingVal = $request->input('wing');
            $query->where(function($q) use ($wingVal) {
                $q->whereHas('bed.room.wing', function($wq) use ($wingVal) {
                    $wq->where('name', $wingVal);
                })->orWhere('lokasi', 'like', $wingVal . ' - %');
            });
        }

        if ($request->filled('room')) {
            $roomVal = $request->input('room');
            $query->where(function($q) use ($roomVal) {
                $q->whereHas('bed.room', function($rq) use ($roomVal) {
                    $rq->where('name', $roomVal);
                })->orWhere('lokasi', 'like', '% - ' . $roomVal . ' (%');
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('merk', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        $equipments = $query->with('media')->latest()->paginate($perPage);
        return view('equipments.index', compact('equipments', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'merk' => 'required',
            'type' => 'required',
            'serial_number' => 'required|unique:equipments,serial_number',
            'tanggal_lahir' => 'required|date',
            'lokasi' => 'required',
            'lantai' => 'required|string',
            'kondisi' => 'required',
            'tanggal_pengadaan' => 'required|date',
            'jam' => 'nullable|string',
            'spesifikasi' => 'nullable|string',
            'status_kepemilikan' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $data = $request->except('gambar');
        $equipment = Equipment::create($data);

        if ($request->hasFile('gambar')) {
            $equipment->addMediaFromRequest('gambar')->toMediaCollection('equipments');
        }

        return redirect()->route('equipments.index')->with('success', 'Data pasien berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $request->validate([
            'merk' => 'required',
            'type' => 'required',
            'serial_number' => 'required|unique:equipments,serial_number,' . $equipment->id,
            'tanggal_lahir' => 'required|date',
            'lokasi' => 'required',
            'lantai' => 'required|string',
            'kondisi' => 'required',
            'tanggal_pengadaan' => 'required|date',
            'jam' => 'nullable|string',
            'spesifikasi' => 'nullable|string',
            'status_kepemilikan' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $oldLokasi = $equipment->lokasi;
        $newLokasi = $request->input('lokasi');

        $data = $request->except('gambar');
        $equipment->update($data);

        if ($oldLokasi !== $newLokasi) {
            \App\Models\Maintenance::create([
                'equipment_id' => $equipment->id,
                'jenis_pemeliharaan' => 'Pemindahan Poli',
                'tanggal_pelaksanaan' => now()->format('Y-m-d'),
                'tanggal_jadwal_berikutnya' => now()->format('Y-m-d'),
                'tindakan_hasil' => "Rujukan Internal: Pasien dipindahkan dari poliklinik lama [{$oldLokasi}] menuju poliklinik baru [{$newLokasi}].",
                'petugas' => auth()->check() ? (auth()->user()->name ?? auth()->user()->username) : 'Administrasi RSUI',
                'diagnosa_gejala' => $equipment->type,
                'lokasi_rawat' => $newLokasi,
                'kondisi_klinis' => $equipment->kondisi,
                'metode_pembayaran' => $equipment->status_kepemilikan,
            ]);
        }

        if ($request->hasFile('gambar')) {
            $equipment->clearMediaCollection('equipments');
            $equipment->addMediaFromRequest('gambar')->toMediaCollection('equipments');
        }

        return redirect()->route('equipments.index')->with('success', 'Data pasien berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->clearMediaCollection('equipments');
        $equipment->delete();

        return redirect()->route('equipments.index')->with('success', 'Data pasien berhasil dihapus!');
    }

    public function exportCsv()
    {
        $fileName = 'inventaris_alkes_export.csv';
        $equipments = Equipment::all();

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        // Header kolom CSV
        $columns = array('Merk', 'Type', 'Serial Number', 'Lokasi', 'Kondisi', 'Spesifikasi', 'Tanggal Pengadaan', 'Jam', 'Status Kepemilikan', 'Lantai');

        $callback = function () use ($equipments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($equipments as $item) {
                fputcsv($file, array(
                    $item->merk,
                    $item->type,
                    $item->serial_number, // Menggunakan serial_number sesuai DB
                    $item->lokasi,
                    $item->kondisi,
                    $item->spesifikasi,
                    $item->tanggal_pengadaan,
                    $item->jam,
                    $item->status_kepemilikan,
                    $item->lantai
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file_csv' => 'required|mimes:csv,txt'
        ]);

        $file = fopen($request->file('file_csv')->getRealPath(), 'r');

        // 1. Deteksi otomatis apakah pemisah kolom adalah koma (,) atau titik koma (;)
        $firstLine = fgets($file);
        $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';

        rewind($file);
        fgetcsv($file, 0, $separator); // Skip header baris pertama

        while (($row = fgetcsv($file, 0, $separator)) !== FALSE) {
            // 2. Validasi: Lewati jika baris kosong atau kolom Serial Number (index 2) tidak ada
            if (!isset($row[2]) || empty(array_filter($row))) {
                continue;
            }

            // 3. Konversi format tanggal dari DD/MM/YYYY (Excel) ke YYYY-MM-DD (Database)
            $tanggalRaw = trim($row[6]);
            $tanggalFix = now()->format('Y-m-d'); // Default jika kosong

            if (!empty($tanggalRaw)) {
                try {
                    // Menangani format 01/04/2026
                    if (strpos($tanggalRaw, '/') !== false) {
                        $tanggalFix = \Carbon\Carbon::createFromFormat('d/m/Y', $tanggalRaw)->format('Y-m-d');
                    }
                    // Menangani format jika sudah YYYY-MM-DD
                    else {
                        $tanggalFix = \Carbon\Carbon::parse($tanggalRaw)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // Jika error parse, gunakan tanggal default hari ini
                    $tanggalFix = now()->format('Y-m-d');
                }
            }

            $hasJam = (count($row) >= 9);
            $jamFix = $hasJam ? trim($row[7] ?? '') : '';
            $statusFix = $hasJam ? (!empty(trim($row[8] ?? '')) ? trim($row[8]) : 'Milik RS') : (!empty(trim($row[7] ?? '')) ? trim($row[7]) : 'Milik RS');
            $lantaiFix = (count($row) >= 10) ? trim($row[9] ?? '') : null;

            // 4. Update atau Buat data baru berdasarkan Serial Number
            Equipment::updateOrCreate(
                ['serial_number' => trim($row[2])],
                [
                    'merk' => !empty(trim($row[0] ?? '')) ? trim($row[0]) : '-',
                    'type' => !empty(trim($row[1] ?? '')) ? trim($row[1]) : '-',
                    'lokasi' => !empty(trim($row[3] ?? '')) ? trim($row[3]) : '-',
                    'kondisi' => !empty(trim($row[4] ?? '')) ? trim($row[4]) : 'Baik',
                    'spesifikasi' => !empty(trim($row[5] ?? '')) ? trim($row[5]) : '-',
                    'tanggal_pengadaan' => $tanggalFix,
                    'jam' => $jamFix,
                    'status_kepemilikan' => $statusFix,
                    'lantai' => $lantaiFix,
                ]
            );
        }

        fclose($file);
        return back()->with('success', 'Data pasien berhasil diimpor!');
    }
}