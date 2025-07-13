<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // Accessors
    public function getChangesAttribute(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }
        
        return $changes;
    }

    public function getHasChangesAttribute(): bool
    {
        return !empty($this->changes);
    }

    public function getUserDisplayNameAttribute(): string
    {
        return $this->user?->full_name ?? 'System';
    }

    // Methods
    public static function logEvent(string $event, $auditable, array|null $oldValues, array|null $newValues, array $metadata = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues ?? [],
						'new_values' => $newValues ?? [],
            'url' => request()->url(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => implode(',', $metadata['tags'] ?? []),
        ]);
    }

    public static function logLogin(User $user): void
    {
        static::logEvent('user_login', $user, null, null, ['tags' => ['authentication']]);
    }

    public static function logLogout(User $user): void
    {
        static::logEvent('user_logout', $user, null, null, ['tags' => ['authentication']]);
    }

    public static function logApplicationSubmission(GunApplication $application): void
    {
        static::logEvent('application_submitted', $application, null, null, ['tags' => ['gun_registration']]);
    }

    public static function logCrimeReport(CrimeReport $report): void
    {
        static::logEvent('crime_report_created', $report, null, null, ['tags' => ['crime_reporting']]);
    }

    public static function logPayment(Payment $payment): void
    {
        static::logEvent('payment_processed', $payment, null, null, ['tags' => ['payment']]);
    }

    // Scopes
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->where('tags', 'like', "%{$tag}%");
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}