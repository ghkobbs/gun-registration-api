<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
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
        return $this->name . ', ' . $this->region->name;
    }

    // Methods
    public function getActiveCommunities()
    {
        return $this->communities()->where('is_active', true)->get();
    }

    public function getCrimeStatsForPeriod($startDate, $endDate)
    {
        return $this->crimeReports()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                crime_type_id,
                COUNT(*) as total_reports,
                SUM(CASE WHEN status IN ("resolved", "closed") THEN 1 ELSE 0 END) as resolved_reports
            ')
            ->groupBy('crime_type_id')
            ->with('crimeType')
            ->get();
    }
}