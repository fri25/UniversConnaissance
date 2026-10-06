<?php

namespace App\Payments;

final class WebhookEvent
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $paymentReference,
        public readonly ?string $orderReference = null,
        public readonly ?int $amount = null,
        public readonly ?string $eventName = null,
        // Identifiant du produit chez le prestataire (si celui-ci fixe le prix).
        public readonly ?string $productReference = null,
    ) {}

    /**
     * Événement reçu mais sans effet pour nous (ex. transaction.created).
     */
    public static function ignored(?string $eventName = null): self
    {
        return new self(PaymentStatus::Pending, null, null, null, $eventName);
    }

    public function isActionable(): bool
    {
        return $this->status !== PaymentStatus::Pending
            && ($this->paymentReference !== null || $this->orderReference !== null);
    }
}
