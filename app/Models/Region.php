<?php
// app/Models/Region.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active', 'region_id'];
    
    protected $casts = ['is_active' => 'boolean'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function crimeReports(): HasMany
    {
        return $this->hasMany(CrimeReport::class);
    }
}