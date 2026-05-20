<?php

namespace App\Http\Controllers;

use App\Models\Calibration;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CalibrationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Calibration::with('equipment', 'media');

        if ($search) {
            $query->whereHas('equipment', function($q) use ($search) {
                $q->where('merk', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        $calibrations = $query->latest('tanggal_kalibrasi')->paginate(10);
        $equipments = Equipment::all(); // For the dropdown in the 'Add Calibration' modal
        
        return view('calibrations.index', compact('calibrations', 'equipments', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'tanggal_kalibrasi' => 'required|date',
            'tanggal_kalibrasi_berikutnya' => 'required|date|after:tanggal_kalibrasi',
            'penyedia_jasa' => 'required',
            'sertifikat' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $data = $request->except('sertifikat');

        $calibration = Calibration::create($data);

        if ($request->hasFile('sertifikat')) {
            $calibration->addMediaFromRequest('sertifikat')->toMediaCollection('calibrations');
        }

        return redirect()->route('calibrations.index')->with('success', 'Data kalibrasi berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $calibration = Calibration::findOrFail($id);

        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'tanggal_kalibrasi' => 'required|date',
            'tanggal_kalibrasi_berikutnya' => 'required|date|after:tanggal_kalibrasi',
            'penyedia_jasa' => 'required',
            'sertifikat' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $data = $request->except('sertifikat');

        $calibration->update($data);

        if ($request->hasFile('sertifikat')) {
            $calibration->clearMediaCollection('calibrations');
            $calibration->addMediaFromRequest('sertifikat')->toMediaCollection('calibrations');
        }

        return redirect()->route('calibrations.index')->with('success', 'Data kalibrasi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $calibration = Calibration::findOrFail($id);
        
        $calibration->clearMediaCollection('calibrations');
        $calibration->delete();

        return redirect()->route('calibrations.index')->with('success', 'Data kalibrasi berhasil dihapus!');
    }

    public function exportCsv()
    {
        $calibrations = Calibration::with('equipment')->latest('tanggal_kalibrasi')->get();
        $filename = "arsip_kalibrasi_alkes_" . date('Y-m-d') . ".csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'No', 
            'Instrumen Terkait', 
            'No Seri',
            'Tanggal Pelaksanaan', 
            'Instansi Penguji', 
            'Jadwal Kalibrasi Mendatang'
        ];

        $callback = function() use($calibrations, $columns) {
            $file = fopen('php://output', 'w');
            
            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns, ';');

            foreach ($calibrations as $key => $cal) {
                fputcsv($file, [
                    $key + 1,
                    $cal->equipment->merk . ' ' . $cal->equipment->type,
                    $cal->equipment->serial_number,
                    $cal->tanggal_kalibrasi,
                    $cal->penyedia_jasa,
                    $cal->tanggal_kalibrasi_berikutnya
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file_csv' => 'required|mimes:csv,txt|max:2048'
        ]);

        $file = fopen($request->file('file_csv')->getRealPath(), 'r');

        // Deteksi pemisah (koma atau titik koma)
        $firstLine = fgets($file);
        $separator = (str_contains($firstLine, ';')) ? ';' : ',';
        rewind($file);

        // Lewati baris header
        fgetcsv($file, 0, $separator);

        $imported = 0;
        $skipped  = 0;

        while (($row = fgetcsv($file, 0, $separator)) !== false) {
            // Lewati baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            // Kolom: No | Instrumen Terkait | No Seri | Tanggal Pelaksanaan | Instansi Penguji | Jadwal Kalibrasi Mendatang
            $serialNumber       = isset($row[2]) ? trim($row[2]) : null;
            $tanggalPelaksanaan = isset($row[3]) ? trim($row[3]) : null;
            $instansiPenguji    = isset($row[4]) ? trim($row[4]) : null;
            $jadwalBerikutnya   = isset($row[5]) ? trim($row[5]) : null;

            if (!$serialNumber || !$tanggalPelaksanaan || !$instansiPenguji || !$jadwalBerikutnya) {
                $skipped++;
                continue;
            }

            // Cocokkan alat berdasarkan serial number
            $equipment = Equipment::where('serial_number', $serialNumber)->first();
            if (!$equipment) {
                $skipped++;
                continue;
            }

            // Parse tanggal — mendukung DD/MM/YYYY dan YYYY-MM-DD
            $parseTanggal = function ($raw) {
                try {
                    if (str_contains($raw, '/')) {
                        return \Carbon\Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
                    }
                    return \Carbon\Carbon::parse($raw)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $tglKalibrasi           = $parseTanggal($tanggalPelaksanaan);
            $tglKalibrasiBerikutnya = $parseTanggal($jadwalBerikutnya);

            if (!$tglKalibrasi || !$tglKalibrasiBerikutnya) {
                $skipped++;
                continue;
            }

            Calibration::create([
                'equipment_id'                 => $equipment->id,
                'tanggal_kalibrasi'            => $tglKalibrasi,
                'penyedia_jasa'                => $instansiPenguji,
                'tanggal_kalibrasi_berikutnya' => $tglKalibrasiBerikutnya,
            ]);

            $imported++;
        }

        fclose($file);

        $msg = "Import selesai: {$imported} data berhasil diimpor";
        if ($skipped > 0) {
            $msg .= ", {$skipped} baris dilewati (No Seri tidak ditemukan / data tidak lengkap).";
        }

        return redirect()->route('calibrations.index')->with('success', $msg);
    }
}
