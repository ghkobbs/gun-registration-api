<?php

namespace App\Services\Payments;

use App\Contracts\PaymentServiceInterface;
use InvalidArgumentException;

class PaymentManager
{
    private array $drivers = [];
    private array $customDrivers = [];

    public function driver(string $driver): PaymentServiceInterface
    {
        $driver = $driver ?: config('payments.default');

        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        return $this->drivers[$driver] = $this->resolve($driver);
    }

    protected function resolve(string $driver): PaymentServiceInterface
    {
        if (isset($this->customDrivers[$driver])) {
            return $this->customDrivers[$driver]();
        }

        $method = 'create' . ucfirst($driver) . 'Driver';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Payment driver [$driver] not supported.");
    }

    public function extend(string $driver, \Closure $callback): self
    {
        $this->customDrivers[$driver] = $callback;
        return $this;
    }

    protected function createStripeDriver(): PaymentServiceInterface
    {
        return new StripePaymentService(config('payments.stripe'));
    }

    protected function createPaystackDriver(): PaymentServiceInterface
    {
        return new PaystackPaymentService(config('payments.paystack'));
    }

    protected function createMobileMoneyDriver(): PaymentServiceInterface
    {
        return new MobileMoneyPaymentService(config('payments.mobile_money'));
    }
}