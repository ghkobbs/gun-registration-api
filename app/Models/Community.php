<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'name',
        'code',
        'latitude',
        'longitude',
        'boundary',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function crimeReports(): HasMany
    {
        return $this->hasMany(CrimeReport::class);
    }

    public function communityAlerts(): HasMany
    {
        return $this->hasMany(CommunityAlert::class);
    }

    public function crimeStatistics(): HasMany
    {
        return $this->hasMany(CrimeStatistics::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->name . ', ' . $this->district->name . ', ' . $this->district->region->name;
    }

    public function getHasCoordinatesAttribute(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    // Methods
    public function getRecentCrimeReports($days = 30)
    {
        return $this->crimeReports()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getCrimeHotspots($radius = 5)
    {
        if (!$this->has_coordinates) {
            return collect();
        }

        return CrimeReport::selectRaw('
            latitude, longitude, COUNT(*) as crime_count,
            ( 6371 * acos( cos( radians(?) ) *
              cos( radians( latitude ) ) *
              cos( radians( longitude ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( latitude ) ) ) ) AS distance
        ', [$this->latitude, $this->longitude, $this->latitude])
            ->having('distance', '<', $radius)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->groupBy('latitude', 'longitude')
            ->orderBy('crime_count', 'desc')
            ->limit(10)
            ->get();
    }

    public function isWithinRadius($latitude, $longitude, $radius = 5): bool
    {
        if (!$this->has_coordinates) {
            return false;
        }

        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $radius;
    }

    private function calculateDistance($lat2, $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1Rad = deg2rad($this->latitude);
        $lon1Rad = deg2rad($this->longitude);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}