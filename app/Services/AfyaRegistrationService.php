<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AfyaRegistrationService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl  = env('AFYA_API_URL', 'http://152.118.52.27:8081');
        $this->username = env('AFYA_API_USER', 'apm01');
        $this->password = env('AFYA_API_PASS', '123456789');
    }

    /**
     * Get login token (cached for 30 minutes)
     */
    protected function getToken(): ?string
    {
        return Cache::remember('afya_token', 1800, function () {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'insomnia/12.4.0',
                ])
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->baseUrl . '/api/v2/auth/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

                if ($response->json('metadata.code') == 200 || $response->json('metadata.code') == 202) {
                    return $response->json('results.0.tokenKey');
                }
            } catch (\Exception $e) {
                Log::error('Afya login token error: ' . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Get patient registration details by MRN (No. RM)
     */
    public function getRegistrationDetails(string $noRm): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // We will try querying the last 30 days first.
        // If not found, we will query the prior 30 days (30-60 days ago).
        $ranges = [
            [now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d')],
            [now()->subDays(60)->format('Y-m-d'), now()->subDays(30)->format('Y-m-d')]
        ];

        foreach ($ranges as $range) {
            try {
                $response = Http::withHeaders([
                    'token'        => $token,
                    'Content-Type' => 'application/json',
                    'User-Agent'   => 'insomnia/12.4.0',
                ])
                ->withoutVerifying()
                ->timeout(15)
                ->post($this->baseUrl . '/api/v8/transaction/registration/list', [
                    'PageNumber'          => 1,
                    'PageSize'            => 1, // Only need the latest active registration
                    'OrderBy'             => 'NoBilling DESC',
                    'PartnerKeyInsurance' => null,
                    'NoBilling'           => '',
                    'MedicalRecord'       => $noRm,
                    'Nama'                => '',
                    'RegDateFrom'         => $range[0],
                    'RegDateTo'           => $range[1],
                    'Gender'              => null,
                    'IdLokasi'            => null,
                    'DokterKey'           => null,
                    'TipePerawatanKey'    => null,
                    'PoliKey'             => null,
                    'IsDischarge'         => 0    // Only active (Open) registrations
                ]);

                if ($response->successful()) {
                    $results = $response->json('results');
                    if (!empty($results)) {
                        $reg = $results[0];

                        $birthDate = null;
                        if (!empty($reg['DateofBirth'])) {
                            $birthDate = date('Y-m-d', strtotime($reg['DateofBirth']));
                        } elseif (!empty($reg['TglLahir'])) {
                            try {
                                $birthDate = \Carbon\Carbon::createFromFormat('d/m/Y', $reg['TglLahir'])->format('Y-m-d');
                            } catch (\Exception $ex) {
                                $birthDate = date('Y-m-d', strtotime($reg['TglLahir']));
                            }
                        }

                        return [
                            'registered_date' => isset($reg['RegDate']) ? date('Y-m-d', strtotime($reg['RegDate'])) : null,
                            'dpjp_utama'      => $reg['NamaDokter'] ?? $reg['DoctorName'] ?? null,
                            'tanggal_lahir'   => $birthDate,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch registration details for RM: $noRm in range {$range[0]} - {$range[1]}. Error: " . $e->getMessage());
            }
        }

        return null;
    }
}
