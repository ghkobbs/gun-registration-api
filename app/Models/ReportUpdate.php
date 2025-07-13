<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'crime_report_id',
        'updated_by',
        'update_type',
        'message',
        'visibility',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function crimeReport(): BelongsTo
    {
        return $this->belongsTo(CrimeReport::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getIsPublicAttribute(): bool
    {
        return $this->visibility === 'public';
    }

    public function getIsInternalAttribute(): bool
    {
        return $this->visibility === 'internal';
    }

    public function getIsReporterOnlyAttribute(): bool
    {
        return $this->visibility === 'reporter_only';
    }

    public function getFormattedMessageAttribute(): string
    {
        return ucfirst($this->message);
    }

    // Methods
    public function canBeViewedBy(User $user): bool
    {
        // Internal updates can only be viewed by staff
        if ($this->is_internal && !$user->isAdmin() && !$user->hasRole('staff')) {
            return false;
        }

        // Reporter-only updates can be viewed by the reporter and staff
        if ($this->is_reporter_only) {
            return $user->id === $this->crimeReport->user_id || 
                   $user->isAdmin() || 
                   $user->hasRole('staff');
        }

        // Public updates can be viewed by anyone with access to the report
        return true;
    }

    public function markAsRead(User $user): void
    {
        // Logic to mark update as read by specific user
        // This might involve a separate table for tracking read status
    }
}