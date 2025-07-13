<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrimeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'priority_level',
        'is_active',
    ];

    protected $casts = [
        'priority_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(CrimeType::class);
    }
}