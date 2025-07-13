<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CrimeReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_number',
        'reference_code',
        'user_id',
        'crime_type_id',
        'reporter_name',
        'reporter_phone',
        'reporter_email',
        'is_anonymous',
        'reporting_method',
        'incident_description',
        'incident_date',
        'incident_location',
        'latitude',
        'longitude',
        'region_id',
        'district_id',
        'community_id',
        'suspects_count',
        'victims_count',
        'witnesses_count',
        'urgency_level',
        'status',
        'additional_notes',
        'assigned_to',
        'assigned_at',
        'closed_by',
        'closed_at',
        'closure_reason',
        'preferred_language',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'incident_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'suspects_count' => 'integer',
        'victims_count' => 'integer',
        'witnesses_count' => 'integer',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(CrimeEvidence::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ReportUpdate::class);
    }

    // Accessors
    public function getIsOpenAttribute(): bool
    {
        return !in_array($this->status, ['resolved', 'closed', 'cancelled']);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function getIsAssignedAttribute(): bool
    {
        return !is_null($this->assigned_to);
    }

    public function getHasLocationAttribute(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getReporterDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }
        
        return $this->reporter_name ?: $this->user?->full_name ?: 'Unknown';
    }

    // Methods
    public function assign(User $officer): void
    {
        $this->update([
            'assigned_to' => $officer->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
        
        $this->addUpdate($officer, 'Case assigned to ' . $officer->full_name, 'assignment');
    }

    public function close(User $officer, string $reason): void
    {
        $this->update([
            'status' => 'closed',
            'closed_by' => $officer->id,
            'closed_at' => now(),
            'closure_reason' => $reason,
        ]);
        
        $this->addUpdate($officer, 'Case closed: ' . $reason, 'status_change');
    }

    public function resolve(User $officer, string $details): void
    {
        $this->update([
            'status' => 'resolved',
            'closed_by' => $officer->id,
            'closed_at' => now(),
            'closure_reason' => $details,
        ]);
        
        $this->addUpdate($officer, 'Case resolved: ' . $details, 'status_change');
    }

    public function addUpdate(User $user, string $message, string $type = 'comment', string $visibility = 'public'): ReportUpdate
    {
        return $this->updates()->create([
            'updated_by' => $user->id,
            'update_type' => $type,
            'message' => $message,
            'visibility' => $visibility,
        ]);
    }

    public static function generateReportNumber(): string
    {
        return 'CR-' . now()->format('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    public static function generateReferenceCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->report_number)) {
                $model->report_number = static::generateReportNumber();
            }
            
            if (empty($model->reference_code)) {
                $model->reference_code = static::generateReferenceCode();
            }
        });
    }
}