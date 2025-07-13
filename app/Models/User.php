<?php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone_number',
        'national_id',
        'national_id_verified_at',
        'national_id_status',
        'password',
        'user_type',
        'status',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'national_id_verified_at' => 'datetime',
        'password' => 'hashed',
        'preferences' => 'array',
    ];

    // Relationships
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_primary', true);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps()
            ->withPivot('assigned_at', 'assigned_by');
    }

    public function gunApplications(): HasMany
    {
        return $this->hasMany(GunApplication::class);
    }

    public function gunRegistrations(): HasMany
    {
        return $this->hasMany(GunRegistration::class);
    }

    public function crimeReports(): HasMany
    {
        return $this->hasMany(CrimeReport::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at) && 
               !is_null($this->phone_verified_at) && 
               $this->national_id_status === 'verified';
    }

    // Methods
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereJsonContains('permissions', $permission)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin' || $this->hasRole('admin');
    }

    public function isClient(): bool
    {
        return $this->user_type === 'client';
    }

    public function markNationalIdAsVerified(): void
    {
        $this->update([
            'national_id_verified_at' => now(),
            'national_id_status' => 'verified',
        ]);
    }
}