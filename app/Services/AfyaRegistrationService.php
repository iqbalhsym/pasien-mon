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

    // Circuit breaker for getRegistrationDetails(): a single slow/timed-out patient (e.g. one with
    // many historical registrations) must NOT poison lookups for every other patient in the same
    // batch run. We only trip the breaker after several *consecutive* failures, which is a much
    // stronger signal that the Afya API itself is down rather than one particular query being slow.
    // The trip itself is expressed purely through the 60s 'afya_api_failed' cache entry (not a
    // permanent static flag) so that a long-running bulk sync (--force, hundreds of patients) can
    // self-heal and resume trying once the cooldown passes, instead of staying blind for the rest
    // of a process that may run for tens of minutes.
    protected static $consecutiveApiFailures = 0;
    const API_FAILURE_THRESHOLD = 3;

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
        // Fail fast if the circuit breaker tripped recently (cache-backed, self-expires after 60s —
        // see the constructor-level comment above for why this is not also a permanent static flag).
        if (Cache::has('afya_api_failed')) {
            return null;
        }

        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        // We will query with MRN filter and no date range for maximum speed and accuracy.
        try {
            $response = Http::withHeaders([
                'token'        => $token,
                'Content-Type' => 'application/json',
                'User-Agent'   => 'insomnia/12.4.0',
            ])
            ->withoutVerifying()
            ->timeout(45) // Raised from 15s: patients with many historical registrations (e.g. 12+ rows)
                          // can take 30s+ for Afya to query/sort, and a premature timeout here was
                          // falling back to stale cached data instead of the current registration.
            ->post($this->baseUrl . '/api/v8/transaction/registration/list', [
                'PageNumber'          => 1,
                'PageSize'            => 1, // Only need the latest active registration
                'OrderBy'             => 'NoBilling DESC',
                'PartnerKeyInsurance' => null,
                'NoBilling'           => '',
                'MRN'                 => $noRm, // Fixed MRN filter key!
                'Nama'                => '',
                'RegDateFrom'         => null,  // No date limit needed since MRN filter works!
                'RegDateTo'           => null,
                'Gender'              => null,
                'IdLokasi'            => null,
                'DokterKey'           => null,
                'TipePerawatanKey'    => null,
                'PoliKey'             => null,
                'IsDischarge'         => 0      // Only active (Open) registrations — this is only called
                                                 // for patients currently occupying a bed, so we must not
                                                 // let an old, already-discharged visit win over the
                                                 // current active one.
            ]);

            if ($response->successful()) {
                $results = $response->json('results');
                if (!empty($results)) {
                    // Double check matching MRN to be 100% safe
                    $reg = null;
                    foreach ($results as $item) {
                        if (isset($item['MedicalRecordNumber']) && trim($item['MedicalRecordNumber']) === $noRm) {
                            $reg = $item;
                            break;
                        }
                    }

                    if ($reg) {
                        // A successful response proves the API is up, so clear any accumulated
                        // failure count from earlier (slow/unlucky) patients in this same run.
                        self::$consecutiveApiFailures = 0;

                        // Helper function to remove UTC timezone indicator (Z or +00:00)
                        // so that PHP strtotime treats it as local WIB time and avoids +7 timezone shifts.
                        $stripTz = function($dateStr) {
                            return preg_replace('/(Z|\.000Z|\+00:00)$/i', '', trim($dateStr));
                        };

                        $birthDate = null;
                        if (!empty($reg['DateofBirth'])) {
                            $birthDate = date('Y-m-d', strtotime($stripTz($reg['DateofBirth'])));
                        } elseif (!empty($reg['TglLahir'])) {
                            try {
                                $birthDate = \Carbon\Carbon::createFromFormat('d/m/Y', $reg['TglLahir'])->format('Y-m-d');
                            } catch (\Exception $ex) {
                                $birthDate = date('Y-m-d', strtotime($stripTz($reg['TglLahir'])));
                            }
                        }

                        $regDate = null;
                        if (!empty($reg['TanggalMasuk'])) {
                            $regDate = date('Y-m-d', strtotime($stripTz($reg['TanggalMasuk'])));
                        } elseif (!empty($reg['TglMasuk'])) {
                            try {
                                $regDate = \Carbon\Carbon::createFromFormat('d/m/Y', $reg['TglMasuk'])->format('Y-m-d');
                            } catch (\Exception $ex) {
                                $regDate = date('Y-m-d', strtotime($stripTz($reg['TglMasuk'])));
                            }
                        } elseif (!empty($reg['RegDate'])) {
                            $regDate = date('Y-m-d', strtotime($stripTz($reg['RegDate'])));
                        }

                        $dpjp = $reg['Dokter'] ?? $reg['NamaDokter'] ?? $reg['DoctorName'] ?? null;

                        return [
                            'registered_date' => $regDate,
                            'dpjp_utama'      => $dpjp,
                            'tanggal_lahir'   => $birthDate,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch registration details for RM: $noRm. Error: " . $e->getMessage());

            // Only trip the circuit breaker after several consecutive failures — a lone timeout is
            // most likely just one patient's query being slow (e.g. many historical registrations),
            // not the whole Afya API being down. Tripping on the very first failure previously caused
            // every other patient queued after an unlucky one to silently get skipped for 60s.
            self::$consecutiveApiFailures++;
            if (self::$consecutiveApiFailures >= self::API_FAILURE_THRESHOLD) {
                Cache::put('afya_api_failed', true, 60);
                self::$consecutiveApiFailures = 0; // give it a fresh count once the cooldown passes
            }
            return null;
        }

        return null;
    }
}
