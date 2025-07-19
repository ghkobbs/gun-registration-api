<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SMSServiceAlt
{
    protected $apiKey;
    protected $apiUrl;
    protected $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.sms.api_key');
        $this->apiUrl = config('services.sms.api_url');
        $this->senderId = config('services.sms.sender_id', 'CRIMEAPP');
    }

    /**
     * Send SMS
     */
    public function sendSMS($phoneNumber, $message)
    {
        try {
            // Validate phone number
            if (!$this->validatePhoneNumber($phoneNumber)) {
                throw new \Exception('Invalid phone number format');
            }

            // Format phone number
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);

            // Check rate limiting
            if (!$this->checkRateLimit($formattedNumber)) {
                throw new \Exception('SMS rate limit exceeded');
            }

            // Send SMS via API
            $response = Http::timeout(30)->post($this->apiUrl . '/send', [
                'api_key' => $this->apiKey,
                'sender_id' => $this->senderId,
                'message' => $message,
                'recipient' => $formattedNumber,
                'type' => 'text',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['status']) && $data['status'] === 'success') {
                    Log::info('SMS sent successfully', [
                        'phone' => $formattedNumber,
                        'message_id' => $data['message_id'] ?? null,
                    ]);

                    // Update rate limit counter
                    $this->updateRateLimit($formattedNumber);

                    return [
                        'success' => true,
                        'message_id' => $data['message_id'] ?? null,
                        'cost' => $data['cost'] ?? null,
                    ];
                } else {
                    throw new \Exception($data['message'] ?? 'SMS sending failed');
                }
            } else {
                throw new \Exception('SMS API request failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSMS($recipients, $message)
    {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendSMS($recipient, $message);
            
            $results[] = [
                'recipient' => $recipient,
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'error' => $result['error'] ?? null,
            ];

            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }

            // Add delay between messages to respect rate limits
            usleep(100000); // 100ms delay
        }

        return [
            'total' => count($recipients),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Check SMS delivery status
     */
    public function checkDeliveryStatus($messageId)
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl . '/status', [
                'api_key' => $this->apiKey,
                'message_id' => $messageId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'delivered_at' => $data['delivered_at'] ?? null,
                ];
            } else {
                throw new \Exception('Status check failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('SMS status check failed', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate phone number
     */
    protected function validatePhoneNumber($phoneNumber)
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check if it's a valid Ghana phone number
        if (strlen($cleaned) === 10 && preg_match('/^0[2-5][0-9]{8}$/', $cleaned)) {
            return true;
        }
        
        // Check if it's in international format
        if (strlen($cleaned) === 12 && preg_match('/^233[2-5][0-9]{8}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Convert to international format
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
            return '233' . substr($cleaned, 1);
        }
        
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '233') {
            return $cleaned;
        }

        throw new \Exception('Invalid phone number format');
    }

    /**
     * Check rate limiting
     */
    protected function checkRateLimit($phoneNumber)
    {
        $key = 'sms_rate_limit_' . $phoneNumber;
        $current = Cache::get($key, 0);
        
        // Allow maximum 10 SMS per hour per number
        return $current < 10;
    }

    /**
     * Update rate limit counter
     */
    protected function updateRateLimit($phoneNumber)
    {
        $key = 'sms_rate_limit_' . $phoneNumber;
        $current = Cache::get($key, 0);
        
        Cache::put($key, $current + 1, 3600); // 1 hour TTL
    }

    /**
     * Get SMS statistics
     */
    public function getSMSStatistics($dateFrom = null, $dateTo = null)
    {
        // This would integrate with your SMS provider's analytics
        // For now, returning basic structure
        return [
            'total_sent' => 0,
            'successful' => 0,
            'failed' => 0,
            'delivery_rate' => 0,
            'total_cost' => 0,
        ];
    }

    /**
     * Get account balance
     */
    public function getAccountBalance()
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl . '/balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? 0,
                    'currency' => $data['currency'] ?? 'GHS',
                ];
            } else {
                throw new \Exception('Balance check failed: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('SMS balance check failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}