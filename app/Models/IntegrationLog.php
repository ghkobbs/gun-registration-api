<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'operation_type',
        'request_data',
        'response_data',
        'status',
        'error_message',
        'response_time',
    ];

    protected $casts = [
        'response_time' => 'integer',
    ];

    // Methods
    public static function logRequest(string $serviceName, string $operationType, array $requestData): self
    {
        return static::create([
            'service_name' => $serviceName,
            'operation_type' => $operationType,
            'request_data' => json_encode($requestData),
            'status' => 'pending',
        ]);
    }

    public function logResponse(array $responseData, string $status = 'success', string $errorMessage, int $responseTime): void
    {
        $this->update([
            'response_data' => json_encode($responseData),
            'status' => $status,
            'error_message' => $errorMessage ?? null,
            'response_time' => $responseTime,
        ]);
    }
}