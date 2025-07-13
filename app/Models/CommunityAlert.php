<?php
namespace App\Models;

use App\Notifications\CommunityAlertNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'alert_type',
        'severity',
        'latitude',
        'longitude',
        'radius',
        'region_id',
        'district_id',
        'community_id',
        'created_by',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveNowAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    public function getHasLocationAttribute(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => '#28a745',
            'medium' => '#ffc107',
            'high' => '#fd7e14',
            'critical' => '#dc3545',
            default => '#6c757d',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->alert_type) {
            'crime_warning' => 'exclamation-triangle',
            'safety_tip' => 'shield-alt',
            'emergency' => 'siren',
            'general' => 'info-circle',
            default => 'bell',
        };
    }

    // Methods
    public function isWithinRadius($latitude, $longitude): bool
    {
        if (!$this->has_location || !$this->radius) {
            return false;
        }

        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $this->radius;
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

    public function getAffectedUsers(): \Illuminate\Support\Collection
    {
        $query = User::query();

        if ($this->has_location && $this->radius) {
            // Get users within radius
            $query->whereHas('addresses', function ($addressQuery) {
                $addressQuery->selectRaw('
                    *, ( 6371 * acos( cos( radians(?) ) *
                    cos( radians( latitude ) ) *
                    cos( radians( longitude ) - radians(?) ) +
                    sin( radians(?) ) *
                    sin( radians( latitude ) ) ) ) AS distance
                ', [$this->latitude, $this->longitude, $this->latitude])
                ->having('distance', '<=', $this->radius);
            });
        } else {
            // Get users by geographic boundaries
            if ($this->community_id) {
                $query->whereHas('addresses', function ($addressQuery) {
                    $addressQuery->where('city', $this->community->name);
                });
            } elseif ($this->district_id) {
                $query->whereHas('addresses', function ($addressQuery) {
                    $addressQuery->where('city', 'like', '%' . $this->district->name . '%');
                });
            } elseif ($this->region_id) {
                $query->whereHas('addresses', function ($addressQuery) {
                    $addressQuery->where('region', $this->region->name);
                });
            }
        }

        return $query->get();
    }

    public function broadcast(): void
    {
        $users = $this->getAffectedUsers();

        foreach ($users as $user) {
            $user->notify(new CommunityAlertNotification($this));
        }
    }

    public function extend(int $hours): void
    {
        $this->update([
            'expires_at' => ($this->expires_at ?: now())->addHours($hours),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeWithinRadius($query, $latitude, $longitude, $radius)
    {
        return $query->selectRaw('
            *, ( 6371 * acos( cos( radians(?) ) *
            cos( radians( latitude ) ) *
            cos( radians( longitude ) - radians(?) ) +
            sin( radians(?) ) *
            sin( radians( latitude ) ) ) ) AS distance
        ', [$latitude, $longitude, $latitude])
        ->having('distance', '<=', $radius)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude');
    }
}