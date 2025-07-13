<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GunRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'application_id',
        'user_id',
        'firearm_type',
        'make',
        'model',
        'caliber',
        'serial_number',
        'manufacture_year',
        'barrel_length',
        'overall_length',
        'weight',
        'additional_features',
        'acquisition_method',
        'previous_owner_name',
        'previous_owner_id',
        'acquisition_date',
        'purchase_price',
        'dealer_name',
        'dealer_license_number',
        'status',
        'registration_date',
        'expiry_date',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'acquisition_date' => 'date',
        'registration_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(GunApplication::class, 'application_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null;
    }

    public function getFirearmDescriptionAttribute(): string
    {
        return implode(' ', array_filter([
            $this->make,
            $this->model,
            $this->caliber,
            $this->firearm_type,
        ]));
    }

    // Methods
    public function suspend(string $reason): void
    {
        $this->update(['status' => 'suspended']);
        
        // Log the suspension
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'gun_registration_suspended',
            'auditable_type' => static::class,
            'auditable_id' => $this->id,
            'new_values' => ['reason' => $reason],
        ]);
    }

    public function revoke(string $reason): void
    {
        $this->update(['status' => 'revoked']);
        
        // Log the revocation
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'gun_registration_revoked',
            'auditable_type' => static::class,
            'auditable_id' => $this->id,
            'new_values' => ['reason' => $reason],
        ]);
    }

    public function renew(int $years = 1): void
    {
        $this->update([
            'expiry_date' => $this->expiry_date->addYears($years),
            'status' => 'active',
        ]);
    }

    public static function generateRegistrationNumber(): string
    {
        return 'GR-' . now()->format('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->registration_number)) {
                $model->registration_number = static::generateRegistrationNumber();
            }
        });
    }
}