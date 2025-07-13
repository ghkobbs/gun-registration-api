<?php
namespace App\Models;

use App\Notifications\EscalationReminderNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EscalationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'escalatable_type',
        'escalatable_id',
        'escalation_rule_id',
        'escalated_by',
        'escalated_to',
        'escalation_reason',
        'status',
        'escalated_at',
        'acknowledged_at',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function escalatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function escalationRule(): BelongsTo
    {
        return $this->belongsTo(EscalationRule::class);
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    // Accessors
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsAcknowledgedAttribute(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->status === 'resolved';
    }

    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->acknowledged_at) {
            return null;
        }

        return $this->escalated_at->diffInHours($this->acknowledged_at);
    }

    public function getResolutionTimeAttribute(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->escalated_at->diffInHours($this->resolved_at);
    }

    // Methods
    public function acknowledge(User $user): void
    {
        $this->update([
            'status' => 'acknowledged',
            'escalated_to' => $user->id,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(User $user, string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes ?? null,	
        ]);
    }

    public function sendReminder(): void
    {
        if ($this->escalated_to) {
            $this->escalatedTo->notify(new EscalationReminderNotification($this));
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->escalated_at)) {
                $model->escalated_at = now();
            }
        });
    }
}