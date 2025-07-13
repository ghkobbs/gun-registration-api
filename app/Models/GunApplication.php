<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GunApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number',
        'user_id',
        'application_type',
        'status',
        'purpose_of_ownership',
        'has_previous_conviction',
        'conviction_details',
        'has_mental_health_issues',
        'mental_health_details',
        'emergency_contacts',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'submitted_at',
        'expires_at',
        'is_escalated',
        'escalated_at',
        'escalated_by',
        'escalation_reason',
        'priority_level',
    ];

    protected $casts = [
        'emergency_contacts' => 'array',
        'has_previous_conviction' => 'boolean',
        'has_mental_health_issues' => 'boolean',
        'is_escalated' => 'boolean',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function gunRegistration(): HasOne
    {
        return $this->hasOne(GunRegistration::class, 'application_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function escalationLogs(): MorphMany
    {
        return $this->morphMany(EscalationLog::class, 'escalatable');
    }

    // Accessors
    public function getIsSubmittedAttribute(): bool
    {
        return !is_null($this->submitted_at);
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        return $this->expires_at ? now()->diffInDays($this->expires_at, false) : null;
    }

    // Methods
    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function escalate(User $escalator, string $reason): void
    {
        $this->update([
            'is_escalated' => true,
            'escalated_by' => $escalator->id,
            'escalated_at' => now(),
            'escalation_reason' => $reason,
            'status' => 'escalated',
            'priority_level' => min($this->priority_level + 1, 4),
        ]);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->hasRequiredDocuments();
    }

    public function hasRequiredDocuments(): bool
    {
        $requiredDocuments = ['id_card', 'proof_of_ownership', 'license'];
        $uploadedDocuments = $this->documents->pluck('document_type')->toArray();
        
        return empty(array_diff($requiredDocuments, $uploadedDocuments));
    }

    public static function generateApplicationNumber(): string
    {
        return 'GA-' . now()->format('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->application_number)) {
                $model->application_number = static::generateApplicationNumber();
            }
        });
    }
}