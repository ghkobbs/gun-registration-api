<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrimeStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'crime_type_id',
        'region_id',
        'district_id',
        'community_id',
        'total_reports',
        'resolved_reports',
        'pending_reports',
        'resolution_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'total_reports' => 'integer',
        'resolved_reports' => 'integer',
        'pending_reports' => 'integer',
        'resolution_rate' => 'decimal:2',
    ];

    // Relationships
    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    // Accessors
    public function getLocationNameAttribute(): string
    {
        if ($this->community) {
            return $this->community->full_name;
        }
        
        if ($this->district) {
            return $this->district->full_name;
        }
        
        if ($this->region) {
            return $this->region->name;
        }
        
        return 'National';
    }

    // Methods
    public static function generateForDate($date): void
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        // Generate statistics for each crime type and location combination
        $crimeTypes = CrimeType::where('is_active', true)->get();
        $locations = static::getLocationHierarchy();
        
        foreach ($crimeTypes as $crimeType) {
            foreach ($locations as $location) {
                static::generateStatisticRecord($date, $crimeType, $location);
            }
        }
    }

    private static function getLocationHierarchy(): array
    {
        $locations = [];
        
        // National level
        $locations[] = ['region_id' => null, 'district_id' => null, 'community_id' => null];
        
        // Regional level
        foreach (Region::where('is_active', true)->get() as $region) {
            $locations[] = ['region_id' => $region->id, 'district_id' => null, 'community_id' => null];
            
            // District level
            foreach ($region->districts()->where('is_active', true)->get() as $district) {
                $locations[] = ['region_id' => $region->id, 'district_id' => $district->id, 'community_id' => null];
                
                // Community level
                foreach ($district->communities()->where('is_active', true)->get() as $community) {
                    $locations[] = ['region_id' => $region->id, 'district_id' => $district->id, 'community_id' => $community->id];
                }
            }
        }
        
        return $locations;
    }

    private static function generateStatisticRecord($date, $crimeType, $location): void
    {
        $query = CrimeReport::where('crime_type_id', $crimeType->id)
                            ->whereDate('created_at', $date);
        
        // Apply location filters
        if ($location['community_id']) {
            $query->where('community_id', $location['community_id']);
        } elseif ($location['district_id']) {
            $query->where('district_id', $location['district_id']);
        } elseif ($location['region_id']) {
            $query->where('region_id', $location['region_id']);
        }
        
        $totalReports = $query->count();
        $resolvedReports = $query->whereIn('status', ['resolved', 'closed'])->count();
        $pendingReports = $totalReports - $resolvedReports;
        $resolutionRate = $totalReports > 0 ? ($resolvedReports / $totalReports) * 100 : 0;
        
        static::updateOrCreate([
            'date' => $date,
            'crime_type_id' => $crimeType->id,
            'region_id' => $location['region_id'],
            'district_id' => $location['district_id'],
            'community_id' => $location['community_id'],
        ], [
            'total_reports' => $totalReports,
            'resolved_reports' => $resolvedReports,
            'pending_reports' => $pendingReports,
            'resolution_rate' => $resolutionRate,
        ]);
    }

    public static function getTrendData($crimeTypeId = null, $regionId = null, $districtId = null, $communityId = null, $days = 30): array
    {
        $query = static::query()
                      ->where('date', '>=', now()->subDays($days))
                      ->orderBy('date');
        
        if ($crimeTypeId) {
            $query->where('crime_type_id', $crimeTypeId);
        }
        
        if ($communityId) {
            $query->where('community_id', $communityId);
        } elseif ($districtId) {
            $query->where('district_id', $districtId);
        } elseif ($regionId) {
            $query->where('region_id', $regionId);
        }
        
        return $query->get()
                    ->groupBy('date')
                    ->map(function ($group) {
                        return [
                            'date' => $group->first()->date,
                            'total_reports' => $group->sum('total_reports'),
                            'resolved_reports' => $group->sum('resolved_reports'),
                            'pending_reports' => $group->sum('pending_reports'),
                            'resolution_rate' => $group->avg('resolution_rate'),
                        ];
                    })
                    ->values()
                    ->toArray();
    }
}