<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'documentable_type',
        'documentable_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'is_required',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_required' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessors
    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('documents.download', $this->id);
    }

    // Methods
    public function verify(User $verifier, string $notes = null): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    public function reject(User $verifier, string $reason): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $reason,
        ]);
    }

    public function delete(): bool
    {
        // Delete the physical file
        if (Storage::disk('documents')->exists($this->file_path)) {
            Storage::disk('documents')->delete($this->file_path);
        }
        
        return parent::delete();
    }
}