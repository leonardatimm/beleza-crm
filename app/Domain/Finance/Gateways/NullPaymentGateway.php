<?php

namespace App\Domain\Finance\Gateways;

use App\Domain\Finance\Contracts\PaymentGateway;
use App\Domain\Finance\Data\PaymentRequest;
use App\Domain\Finance\Data\PaymentResult;
use LogicException;

class NullPaymentGateway implements PaymentGateway
{
    public function createCharge(PaymentRequest $request): PaymentResult
    {
        return $this->unavailable();
    }

    public function status(string $externalReference): PaymentResult
    {
        return $this->unavailable();
    }

    public function cancel(string $externalReference, string $idempotencyKey): PaymentResult
    {
        return $this->unavailable();
    }

    /** @param array<string, mixed> $payload */
    public function processWebhook(array $payload, string $signature): PaymentResult
    {
        return $this->unavailable();
    }

    private function unavailable(): never
    {
        throw new LogicException('Nenhum gateway de pagamento está configurado para este ambiente.');
    }
}
