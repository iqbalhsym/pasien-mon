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

    // Static flags to instantly fail-fast within the same request execution thread
    protected static $authFailed = false;
    protected static $apiFailed = false;

    public function __construct()
    {
        $this->baseUrl  = env('AFYA_API_URL', 'http://152.118.52.27:8081');
        $this->username = env('AFYA_API_USER', 'apm01');
        $this->password = env('AFYA_API_PASS', '123456789');
    }

    /**
     * Get login token (cached for 30 minutes, or fail-fast for 1 minute on error)
     */
    protected function getToken(): ?string
    {
        // Fail fast if auth has already failed in this thread or in cache (last 60s)
        if (self::$authFailed || Cache::has('afya_token_failed')) {
            return null;
        }

        // Return token from cache if available
        if (Cache::has('afya_token')) {
            $token = Cache::get('afya_token');
            if ($token) {
                return $token;
            }
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent'   => 'insomnia/12.4.0',
            ])
            ->withoutVerifying()
            ->timeout(5) // Reduced timeout to fail faster
            ->post($this->baseUrl . '/api/v2/auth/login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && ($response->json('metadata.code') == 200 || $response->json('metadata.code') == 202)) {
                $token = $response->json('results.0.tokenKey');
                if ($token) {
                    Cache::put('afya_token', $token, 1800); // Cache for 30 minutes
                    return $token;
                }
            }
        } catch (\Exception $e) {
            Log::error('Afya login token error: ' . $e->getMessage());
        }

        // Mark as failed to avoid multiple repeated connection attempts
        self::$authFailed = true;
        Cache::put('afya_token_failed', true, 60); // Cache failure for 1 minute
        return null;
    }

    /**
     * Get patient registration details by MRN (No. RM)
     */
    public function getRegistrationDetails(string $noRm): ?array
    {
        // Fail fast if API calls have failed in this thread or in cache (last 60s)
        if (self::$apiFailed || Cache::has('afya_api_failed')) {
            return null;
        }

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
                ->timeout(8) // Reduced timeout to prevent long blocking states
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
                
                // If a connection/timeout exception happens, mark as api failed to stop subsequent calls
                self::$apiFailed = true;
                Cache::put('afya_api_failed', true, 60);
                return null;
            }
        }

        return null;
    }
}
