<?php
// app/Services/NationalIdService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NationalIdService
{
    protected $apiKey;
    protected $apiUrl;
    protected $timeout = 30;

    public function __construct()
    {
        $this->apiKey = config('services.national_id.api_key');
        $this->apiUrl = config('services.national_id.api_url');
    }

    /**
     * Validate National ID
     */
    public function validateNationalId($nationalId, $userData = [])
    {
        try {
            // Check cache first
            $cacheKey = 'national_id_validation_' . $nationalId;
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            // Make API request to NIA
            $response = Http::timeout($this->timeout)->post($this->apiUrl . '/validate', [
                'api_key' => $this->apiKey,
                'national_id' => $nationalId,
                'first_name' => $userData['first_name'] ?? null,
                'last_name' => $userData['last_name'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $result = [
                    'valid' => $data['valid'] ?? false,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'middle_name' => $data['middle_name'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'place_of_birth' => $data['place_of_birth'] ?? null,
                    'verified_at' => now()->toISOString(),
                ];

                // Cache result for 24 hours
                Cache::put($cacheKey, $result, 86400);

                Log::info('National ID validation successful', [
                    'national_id' => $nationalId,
                    'valid' => $result['valid'],
                ]);

                return $result;

            } else {
                throw new \Exception('NIA API request failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('National ID validation failed', [
                'national_id' => $nationalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'verified_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get detailed National ID information
     */
    public function getNationalIdDetails($nationalId)
    {
        try {
            $cacheKey = 'national_id_details_' . $nationalId;
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            $response = Http::timeout($this->timeout)->get($this->apiUrl . '/details', [
                'api_key' => $this->apiKey,
                'national_id' => $nationalId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $result = [
                    'success' => true,
                    'data' => [
                        'national_id' => $data['national_id'] ?? null,
                        'first_name' => $data['first_name'] ?? null,
                        'last_name' => $data['last_name'] ?? null,
                        'middle_name' => $data['middle_name'] ?? null,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'gender' => $data['gender'] ?? null,
                        'place_of_birth' => $data['place_of_birth'] ?? null,
                        'region' => $data['region'] ?? null,
                        'district' => $data['district'] ?? null,
                        'issued_date' => $data['issued_date'] ?? null,
                        'expiry_date' => $data['expiry_date'] ?? null,
                        'status' => $data['status'] ?? null,
                    ],
                ];

                // Cache for 6 hours
                Cache::put($cacheKey, $result, 21600);

                return $result;

            } else {
                throw new \Exception('NIA API request failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('National ID details fetch failed', [
                'national_id' => $nationalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if National ID is blacklisted
     */
    public function isBlacklisted($nationalId)
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->apiUrl . '/blacklist', [
                'api_key' => $this->apiKey,
                'national_id' => $nationalId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'blacklisted' => $data['blacklisted'] ?? false,
                    'reason' => $data['reason'] ?? null,
                    'blacklisted_at' => $data['blacklisted_at'] ?? null,
                ];

            } else {
                throw new \Exception('Blacklist check failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('National ID blacklist check failed', [
                'national_id' => $nationalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate National ID format
     */
    public function validateFormat($nationalId)
    {
        // Ghana Card format: GHA-XXXXXXXXX-X
        $pattern = '/^GHA-[0-9]{9}-[0-9]$/';
        
        if (!preg_match($pattern, $nationalId)) {
            return [
                'valid' => false,
                'error' => 'Invalid National ID format. Expected format: GHA-XXXXXXXXX-X',
            ];
        }

        // Check checksum (basic validation)
        $digits = str_replace(['GHA-', '-'], '', $nationalId);
        $checksum = $this->calculateChecksum(substr($digits, 0, 9));
        
        if ($checksum !== intval(substr($digits, 9))) {
            return [
                'valid' => false,
                'error' => 'Invalid National ID checksum',
            ];
        }

        return [
            'valid' => true,
            'formatted' => $nationalId,
        ];
    }

    /**
     * Calculate checksum for National ID
     */
    protected function calculateChecksum($digits)
    {
        $sum = 0;
        for ($i = 0; $i < strlen($digits); $i++) {
            $sum += intval($digits[$i]) * (($i % 2) + 1);
        }
        
        return $sum % 10;
    }

    /**
     * Get service status
     */
    public function getServiceStatus()
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl . '/status', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'available' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'last_updated' => $data['last_updated'] ?? null,
                ];

            } else {
                return [
                    'available' => false,
                    'error' => 'Service unavailable',
                ];
            }

        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}