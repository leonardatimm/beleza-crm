<?php

namespace App\Domain\Finance\Data;

readonly class PaymentRequest
{
    public function __construct(
        public int $tenantId,
        public int $amountCents,
        public string $description,
        public string $idempotencyKey,
    ) {}
}
