<?php

namespace App\Contracts;

interface PaymentServiceInterface
{
    public function initialize(array $data): array;
    public function verify(string $reference): array;
    public function refund(string $reference, float $amount): array;
    public function getTransactionStatus(string $reference): string;
}