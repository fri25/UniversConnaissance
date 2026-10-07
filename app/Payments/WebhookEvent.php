<?php

namespace App\Payments;

final class WebhookEvent
{
    /**
     * @param  array{email?: ?string, name?: ?string, first_name?: ?string, last_name?: ?string, phone?: ?string, country?: ?string}  $customer
     */
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $paymentReference,
        public readonly ?string $orderReference = null,
        public readonly ?int $amount = null,
        public readonly ?string $eventName = null,
        // Identifiant du produit chez le prestataire (si celui-ci fixe le prix).
        public readonly ?string $productReference = null,
        // Acheteur tel que saisi sur la page de paiement du prestataire.
        public readonly array $customer = [],
    ) {}

    /**
     * Événement reçu mais sans effet pour nous (ex. license.issued).
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

    public function customerEmail(): ?string
    {
        $email = mb_strtolower(trim((string) ($this->customer['email'] ?? '')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function customerName(): string
    {
        $name = trim((string) ($this->customer['name'] ?? ''))
            ?: trim(($this->customer['first_name'] ?? '').' '.($this->customer['last_name'] ?? ''));

        return $name !== '' ? $name : (string) strstr((string) $this->customerEmail(), '@', true);
    }
}
