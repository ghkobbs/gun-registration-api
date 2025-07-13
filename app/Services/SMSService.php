<?php
// app/Services/SMSService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SMSService
{
    protected $provider;
    protected $apiKey;
    protected $apiUrl;
    protected $senderId;

    public function __construct()
    {
        $this->provider = config('sms.provider', 'arkesel');
        $this->apiKey = config('sms.api_key');
        $this->apiUrl = config('sms.api_url');
        $this->senderId = config('sms.sender_id', 'GunCrime');
    }

    /**
     * Send SMS using configured provider
     */
    public function sendSMS($phoneNumber, $message)
    {
        try {
            // Normalize phone number for Ghana
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            switch ($this->provider) {
                case 'arkesel':
                    return $this->sendArkeselSMS($phoneNumber, $message);
                case 'hubtel':
                    return $this->sendHubtelSMS($phoneNumber, $message);
                case 'mnotify':
                    return $this->sendMNotifySMS($phoneNumber, $message);
                default:
                    Log::error('Unknown SMS provider: ' . $this->provider);
                    return false;
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via Arkesel
     */
    private function sendArkeselSMS($phoneNumber, $message)
    {
        $response = Http::post($this->apiUrl, [
            'api_key' => $this->apiKey,
            'to' => $phoneNumber,
            'from' => $this->senderId,
            'sms' => $message,
            'type' => 'plain',
        ]);

        if ($response->successful()) {
            Log::info('SMS sent successfully via Arkesel to: ' . $phoneNumber);
            return true;
        }

        Log::error('Failed to send SMS via Arkesel: ' . $response->body());
        return false;
    }

    /**
     * Send SMS via Hubtel
     */
    private function sendHubtelSMS($phoneNumber, $message)
    {
        $response = Http::withBasicAuth($this->apiKey, config('sms.api_secret'))
            ->post($this->apiUrl, [
                'From' => $this->senderId,
                'To' => $phoneNumber,
                'Content' => $message,
            ]);

        if ($response->successful()) {
            Log::info('SMS sent successfully via Hubtel to: ' . $phoneNumber);
            return true;
        }

        Log::error('Failed to send SMS via Hubtel: ' . $response->body());
        return false;
    }

    /**
     * Send SMS via MNotify
     */
    private function sendMNotifySMS($phoneNumber, $message)
    {
        $response = Http::post($this->apiUrl, [
            'key' => $this->apiKey,
            'to' => $phoneNumber,
            'sender' => $this->senderId,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info('SMS sent successfully via MNotify to: ' . $phoneNumber);
            return true;
        }

        Log::error('Failed to send SMS via MNotify: ' . $response->body());
        return false;
    }

    /**
     * Normalize phone number for Ghana
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Add Ghana country code if not present
        if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '233' . substr($phoneNumber, 1);
        } elseif (strlen($phoneNumber) === 9) {
            $phoneNumber = '233' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Get SMS provider balance
     */
    public function getBalance()
    {
        try {
            switch ($this->provider) {
                case 'arkesel':
                    return $this->getArkeselBalance();
                case 'hubtel':
                    return $this->getHubtelBalance();
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get SMS balance: ' . $e->getMessage());
            return null;
        }
    }

    private function getArkeselBalance()
    {
        $response = Http::get($this->apiUrl . '/balance', [
            'api_key' => $this->apiKey,
        ]);

        return $response->successful() ? $response->json() : null;
    }

    private function getHubtelBalance()
    {
        $response = Http::withBasicAuth($this->apiKey, config('sms.api_secret'))
            ->get($this->apiUrl . '/account/balance');

        return $response->successful() ? $response->json() : null;
    }
}