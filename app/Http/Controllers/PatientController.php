<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AfyaService;
use App\Models\Admission;

class PatientController extends Controller
{
    protected $afyaService;

    public function __construct(AfyaService $afyaService)
    {
        $this->afyaService = $afyaService;
    }

    /**
     * Check patient by MRN (MRN/noRekamMedis)
     * Calls Afya HIE, then falls back to local database.
     */
    public function checkPatientByMrn(Request $request)
    {
        $mrn = $request->query('mrn');

        if (empty($mrn)) {
            return response()->json([
                'exists' => false,
                'message' => 'MRN parameter is required'
            ], 400);
        }

        // 1. Try fetching from Afya HIE API
        $afyaResult = $this->afyaService->searchPatientByMrn($mrn);
        $patientData = null;

        if ($afyaResult) {
            if (isset($afyaResult['NoRekamMedis']) || isset($afyaResult['noRekamMedis'])) {
                $patientData = $afyaResult;
            } elseif (is_array($afyaResult) && isset($afyaResult[0])) {
                $patientData = $afyaResult[0];
            } else {
                $patientData = $afyaResult;
            }
        }

        if ($patientData) {
            // Determine penjamin from NoBPJS
            $penjamin = 'UMUM';
            if (!empty($patientData['NoBPJS'])) {
                $penjamin = 'BPJS Kesehatan';
            }

            // Convert gender
            $gender = $patientData['Gender'] ?? $patientData['gender'] ?? null;
            if ($gender === 'M') {
                $gender = 'Laki-laki';
            } elseif ($gender === 'F' || $gender === 'W') {
                $gender = 'Perempuan';
            }

            return response()->json([
                'exists' => true,
                'no_rm' => $patientData['NoRekamMedis'] ?? $patientData['noRekamMedis'] ?? $mrn,
                'name' => $patientData['Nama'] ?? $patientData['name'] ?? $patientData['patientName'] ?? '',
                'dob' => $patientData['DateofBirth'] ?? $patientData['birthDate'] ?? $patientData['dob'] ?? null,
                'gender' => $gender,
                'pasien_id' => $patientData['PersonKey'] ?? $patientData['patientId'] ?? $patientData['id'] ?? null,
                'penjamin' => $patientData['insuranceName'] ?? $patientData['penjamin'] ?? $penjamin,
            ]);
        }

        // 2. Fallback to local database (admissions table)
        $localPatient = Admission::where('rm', $mrn)->first();

        if ($localPatient) {
            return response()->json([
                'exists' => true,
                'no_rm' => $localPatient->rm,
                'name' => $localPatient->name,
                'dob' => null,
                'gender' => null,
                'pasien_id' => $localPatient->id,
                'penjamin' => $localPatient->insurance
            ]);
        }

        return response()->json([
            'exists' => false
        ]);
    }

    /**
     * Fetch floors and rooms from the external bed-mon API.
     */
    public function getFloorsRooms()
    {
        $url = rtrim(config('services.bed_mon.url'), '/') . '/api/external/floors-rooms';
        $apiKey = config('services.bed_mon.api_key');

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey
            ])->timeout(5)->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari bed-mon API: ' . $response->body()
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kesalahan koneksi ke bed-mon API: ' . $e->getMessage()
            ], 500);
        }
    }
}
