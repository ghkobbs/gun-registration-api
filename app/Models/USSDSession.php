<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class USSDSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'phone_number',
        'current_menu',
        'session_data',
        'language',
        'status',
        'last_activity',
    ];

    protected $casts = [
        'session_data' => 'array',
        'last_activity' => 'datetime',
    ];

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && 
               $this->last_activity->diffInMinutes(now()) < 5; // 5 minute timeout
    }

    // Methods
    public function updateData(array $data): void
    {
        $sessionData = $this->session_data ?? [];
        $sessionData = array_merge($sessionData, $data);
        
        $this->update([
            'session_data' => $sessionData,
            'last_activity' => now(),
        ]);
    }

    public function getData(string $key = null)
    {
        if ($key) {
            return $this->session_data[$key] ?? null;
        }
        
        return $this->session_data ?? [];
    }

    public function terminate(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function timeout(): void
    {
        $this->update(['status' => 'timeout']);
    }
}