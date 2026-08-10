<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AfyaService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.afya.url'), '/');
        $this->username = config('services.afya.username');
        $this->password = config('services.afya.password');
    }

    /**
     * Get token from Afya API (from cache or new login)
     */
    public function getToken()
    {
        $cacheKey = 'afya_token_' . $this->username;
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        // Common login endpoints in Afya Better Dynamic
        $loginEndpoints = [
            '/api/v1/auth/login',
            '/api/v1/login',
            '/api/v1/user/login',
        ];

        foreach ($loginEndpoints as $endpoint) {
            try {
                // Try sending username & password
                $response = Http::withoutVerifying()->timeout(5)->post($this->baseUrl . $endpoint, [
                    'username' => $this->username,
                    'password' => $this->password
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $token = $data['results'][0]['tokenKey'] ?? $data['token'] ?? $data['data']['token'] ?? $data['accessToken'] ?? null;
                    if ($token) {
                        Cache::put($cacheKey, $token, now()->addHours(11));
                        return $token;
                    }
                }

                // If fails, try sending email & password
                $response = Http::withoutVerifying()->timeout(5)->post($this->baseUrl . $endpoint, [
                    'email' => $this->username,
                    'password' => $this->password
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $token = $data['results'][0]['tokenKey'] ?? $data['token'] ?? $data['data']['token'] ?? $data['accessToken'] ?? null;
                    if ($token) {
                        Cache::put($cacheKey, $token, now()->addHours(11));
                        return $token;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Afya Login failed for endpoint {$endpoint}: " . $e->getMessage());
            }
        }

        Log::error("Afya Service could not authenticate with username: " . $this->username);
        return null;
    }

    /**
     * Cari Pasien Berdasarkan No Rekam Medis (MRN) - Fast & accurate
     */
    public function searchPatientByMrn(string $mrn): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        try {
            $response = Http::withHeaders(['token' => $token, 'User-Agent' => 'insomnia/12.4.0'])
                ->withoutVerifying()
                ->timeout(15)
                ->post($this->baseUrl . '/api/v1/transaction/patientmgmt/Appointment/searchpatient', [
                    'noRekamMedis' => $mrn,
                    'name'         => '',
                    'birthDate'    => '',
                    'top'          => 1
                ]);

            if ($response->successful()) {
                $results = $response->json('results') ?? [];
                return !empty($results) ? $results[0] : null;
            }
            
            Log::error("Afya searchpatient API error response: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Afya searchpatient API request failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Cari Pasien Berdasarkan NIK
     */
    public function getPatientByIdentity($nik)
    {
        $token = $this->getToken();
        if (!$token) return null;

        try {
            $response = Http::withHeaders(['token' => $token, 'User-Agent' => 'insomnia/12.4.0'])
                ->withoutVerifying()
                ->get($this->baseUrl . '/api/v1/references/patient/get/by-identity', [
                    'nik' => $nik
                ]);

            if ($response->json('metadata.code') == 200) {
                return $response->json('results') ?? null;
            }
        } catch (\Exception $e) {
            Log::error("Afya getPatientByIdentity API request failed: " . $e->getMessage());
        }

        return null;
    }
}
