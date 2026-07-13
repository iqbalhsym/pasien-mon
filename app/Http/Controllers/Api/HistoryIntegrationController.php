<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoryIntegrationController extends Controller
{
    /**
     * Update medicine history (riw_obat) for patient.
     */
    public function updateObatHistory(Request $request)
    {
        $request->validate([
            'no_rm'         => 'required|string',
            'detail_obat'   => 'required|string',
            'tanggal_ambil' => 'nullable|string', // Datetime string, e.g. Y-m-d H:i:s
            'tanggal_lahir' => 'nullable|date',
        ]);

        try {
            $noRm = trim($request->no_rm);
            $detailObat = trim($request->detail_obat);
            $tanggalAmbil = $request->tanggal_ambil;

            // Find patient by serial_number (RM)
            $patient = Equipment::where('serial_number', $noRm)->first();

            if (!$patient) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Pasien dengan No. RM {$noRm} tidak ditemukan."
                ], 404);
            }

            // Format tanggal dan jam
            if ($tanggalAmbil) {
                try {
                    $formattedDateTime = date('d-m-Y H:i:s', strtotime($tanggalAmbil));
                } catch (\Exception $e) {
                    $formattedDateTime = date('d-m-Y H:i:s');
                }
            } else {
                $formattedDateTime = date('d-m-Y H:i:s');
            }

            // Bersihkan tag HTML (jika ada input dari editor)
            $cleanObat = strip_tags($detailObat);

            // Format entri riwayat baru
            $newEntry = "- " . $cleanObat . " | Tanggal Ambil: " . $formattedDateTime;

            // Dapatkan riwayat lama
            $currentHistory = $patient->riw_obat;

            if (empty($currentHistory) || trim($currentHistory) === '-') {
                $updatedHistory = $newEntry;
            } else {
                $updatedHistory = rtrim($currentHistory) . "\n" . $newEntry;
            }

            // Update riw_obat and optional tanggal_lahir
            $updateData = [
                'riw_obat' => $updatedHistory
            ];
            if ($request->filled('tanggal_lahir')) {
                $updateData['tanggal_lahir'] = $request->input('tanggal_lahir');
            }

            $patient->update($updateData);

            return response()->json([
                'status'  => 'success',
                'message' => "Riwayat obat berhasil diperbarui untuk pasien {$patient->merk}.",
                'data'    => [
                    'no_rm'    => $noRm,
                    'riw_obat' => $updatedHistory
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('HistoryIntegrationController Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat memperbarui riwayat obat.'
            ], 500);
        }
    }
}
