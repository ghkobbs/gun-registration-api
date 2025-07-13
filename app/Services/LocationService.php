<?php
namespace App\Services;

use App\Models\Region;
use App\Models\District;
use App\Models\Community;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationService
{
    protected $geocodingApiKey;
    protected $geocodingBaseUrl;

    public function __construct()
    {
        $this->geocodingApiKey = config('services.geocoding.api_key');
        $this->geocodingBaseUrl = config('services.geocoding.base_url', 'https://api.opencagedata.com/geocode/v1');
    }

    /**
     * Get location details from coordinates or IDs
     */
    public function getLocationDetails($latitude = null, $longitude = null, $regionId = null, $districtId = null, $communityId = null)
    {
        // If coordinates are provided, try to get administrative divisions
        if ($latitude && $longitude) {
            return $this->getLocationFromCoordinates($latitude, $longitude);
        }

        // If IDs are provided, validate and return them
        if ($regionId || $districtId || $communityId) {
            return $this->validateLocationIds($regionId, $districtId, $communityId);
        }

        return [
            'region_id' => null,
            'district_id' => null,
            'community_id' => null,
        ];
    }

    /**
     * Get location from coordinates using reverse geocoding
     */
    protected function getLocationFromCoordinates($latitude, $longitude)
    {
        $cacheKey = "location_coords_{$latitude}_{$longitude}";
        
        return Cache::remember($cacheKey, 3600, function () use ($latitude, $longitude) {
            try {
                $response = Http::get($this->geocodingBaseUrl . '/json', [
                    'key' => $this->geocodingApiKey,
                    'q' => "{$latitude},{$longitude}",
                    'countrycode' => 'gh',
                    'language' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['results']) && count($data['results']) > 0) {
                        $result = $data['results'][0];
                        return $this->extractLocationFromGeocoding($result);
                    }
                }

                Log::warning("Geocoding failed for coordinates: {$latitude}, {$longitude}");
                return $this->getDefaultLocation();

            } catch (\Exception $e) {
                Log::error("Geocoding error: " . $e->getMessage());
                return $this->getDefaultLocation();
            }
        });
    }

    /**
     * Extract location information from geocoding result
     */
    protected function extractLocationFromGeocoding($result)
    {
        $components = $result['components'] ?? [];
        
        $regionName = $components['state'] ?? $components['region'] ?? null;
        $districtName = $components['county'] ?? $components['district'] ?? null;
        $communityName = $components['town'] ?? $components['village'] ?? $components['suburb'] ?? null;

        $region = null;
        $district = null;
        $community = null;

        // Find region
        if ($regionName) {
            $region = Region::where('name', 'like', "%{$regionName}%")
                           ->orWhere('code', 'like', "%{$regionName}%")
                           ->first();
        }

        // Find district
        if ($districtName && $region) {
            $district = District::where('region_id', $region->id)
                              ->where('name', 'like', "%{$districtName}%")
                              ->first();
        }

        // Find community
        if ($communityName && $district) {
            $community = Community::where('district_id', $district->id)
                                 ->where('name', 'like', "%{$communityName}%")
                                 ->first();
        }

        return [
            'region_id' => $region?->id,
            'district_id' => $district?->id,
            'community_id' => $community?->id,
            'region_name' => $region?->name,
            'district_name' => $district?->name,
            'community_name' => $community?->name,
        ];
    }

    /**
     * Validate location IDs
     */
    protected function validateLocationIds($regionId, $districtId, $communityId)
    {
        $region = $regionId ? Region::find($regionId) : null;
        $district = $districtId ? District::find($districtId) : null;
        $community = $communityId ? Community::find($communityId) : null;

        // Validate hierarchy
        if ($district && $region && $district->region_id !== $region->id) {
            $district = null;
        }

        if ($community && $district && $community->district_id !== $district->id) {
            $community = null;
        }

        return [
            'region_id' => $region?->id,
            'district_id' => $district?->id,
            'community_id' => $community?->id,
            'region_name' => $region?->name,
            'district_name' => $district?->name,
            'community_name' => $community?->name,
        ];
    }

    /**
     * Get default location (fallback)
     */
    protected function getDefaultLocation()
    {
        return [
            'region_id' => null,
            'district_id' => null,
            'community_id' => null,
            'region_name' => null,
            'district_name' => null,
            'community_name' => null,
        ];
    }

    /**
     * Get coordinates from address
     */
    public function getCoordinatesFromAddress($address)
    {
        $cacheKey = "coords_address_" . md5($address);
        
        return Cache::remember($cacheKey, 3600, function () use ($address) {
            try {
                $response = Http::get($this->geocodingBaseUrl . '/json', [
                    'key' => $this->geocodingApiKey,
                    'q' => $address . ', Ghana',
                    'countrycode' => 'gh',
                    'language' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['results']) && count($data['results']) > 0) {
                        $result = $data['results'][0];
                        return [
                            'latitude' => $result['geometry']['lat'],
                            'longitude' => $result['geometry']['lng'],
                        ];
                    }
                }

                return null;

            } catch (\Exception $e) {
                Log::error("Address geocoding error: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Calculate distance between two points
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Find nearby locations
     */
    public function findNearbyLocations($latitude, $longitude, $radius = 10)
    {
        return Community::selectRaw('*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance', [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Get all regions
     */
    public function getAllRegions()
    {
        return Cache::remember('all_regions', 3600, function () {
            return Region::where('is_active', true)->orderBy('name')->get();
        });
    }

    /**
     * Get districts by region
     */
    public function getDistrictsByRegion($regionId)
    {
        return Cache::remember("districts_region_{$regionId}", 3600, function () use ($regionId) {
            return District::where('region_id', $regionId)
                          ->where('is_active', true)
                          ->orderBy('name')
                          ->get();
        });
    }

    /**
     * Get communities by district
     */
    public function getCommunitiesByDistrict($districtId)
    {
        return Cache::remember("communities_district_{$districtId}", 3600, function () use ($districtId) {
            return Community::where('district_id', $districtId)
                           ->where('is_active', true)
                           ->orderBy('name')
                           ->get();
        });
    }

    /**
     * Get location hierarchy
     */
    public function getLocationHierarchy($communityId = null, $districtId = null, $regionId = null)
    {
        $hierarchy = [];

        if ($communityId) {
            $community = Community::with('district.region')->find($communityId);
            if ($community) {
                $hierarchy = [
                    'community' => $community,
                    'district' => $community->district,
                    'region' => $community->district->region,
                ];
            }
        } elseif ($districtId) {
            $district = District::with('region')->find($districtId);
            if ($district) {
                $hierarchy = [
                    'district' => $district,
                    'region' => $district->region,
                ];
            }
        } elseif ($regionId) {
            $region = Region::find($regionId);
            if ($region) {
                $hierarchy = [
                    'region' => $region,
                ];
            }
        }

        return $hierarchy;
    }
}