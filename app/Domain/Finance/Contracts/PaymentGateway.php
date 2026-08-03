<?php

namespace App\Domain\Finance\Contracts;

use App\Domain\Finance\Data\PaymentRequest;
use App\Domain\Finance\Data\PaymentResult;

interface PaymentGateway
{
    public function createCharge(PaymentRequest $request): PaymentResult;

    public function status(string $externalReference): PaymentResult;

    public function cancel(string $externalReference, string $idempotencyKey): PaymentResult;

    /** @param array<string, mixed> $payload */
    public function processWebhook(array $payload, string $signature): PaymentResult;
}
