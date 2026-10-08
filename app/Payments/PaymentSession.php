<?php

namespace App\Payments;

final class PaymentSession
{
    public function __construct(
        public readonly string $paymentReference,
        public readonly string $redirectUrl,
    ) {}
}
