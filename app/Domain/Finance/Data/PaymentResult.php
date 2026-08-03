<?php

namespace App\Domain\Finance\Data;

readonly class PaymentResult
{
    public function __construct(
        public string $externalReference,
        public string $status,
        public ?string $paymentUrl = null,
    ) {}
}
