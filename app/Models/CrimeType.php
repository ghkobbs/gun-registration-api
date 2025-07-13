<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrimeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'crime_category_id',
        'name',
        'description',
        'severity_level',
        'requires_immediate_response',
        'is_active',
    ];

    protected $casts = [
        'severity_level' => 'integer',
        'requires_immediate_response' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CrimeCategory::class, 'crime_category_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CrimeReport::class);
    }
}