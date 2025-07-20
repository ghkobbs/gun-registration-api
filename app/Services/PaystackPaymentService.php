<?php

namespace App\Services\Payments;

use App\Contracts\PaymentServiceInterface;
use Illuminate\Support\Facades\Http;

class PaystackPaymentService implements PaymentServiceInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initialize(array $data): array
    {
        $response = Http::withToken($this->config['secret_key'])
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'],
                'metadata' => $data['metadata'] ?? [],
            ]);

        return $response->json();
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken($this->config['secret_key'])
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        return $response->json();
    }

		public function refund(string $reference, float $amount): array
		{
				$data = ['transaction' => $reference];
				if ($amount) {
						$data['amount'] = $amount * 100; // Convert to kobo
				}

				$response = Http::withToken($this->config['secret_key'])
						->post('https://api.paystack.co/refund', $data);

				return $response->json();
		}

		public function getTransactionStatus(string $reference): string
		{
				$response = $this->verify($reference);
				return $response['data']['status'] ?? 'unknown';
		}
}