<?php

namespace App\Payments\Gateways;

use App\Models\Book;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Http\Request;

/**
 * Prestataire simulé pour le développement local, la démo et les tests.
 * Sa page imite celle de Chariow (coordonnées + paiement). Interdit en production.
 */
class FakeGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'X-FAKE-SIGNATURE';

    /**
     * @param  array{webhook_secret: string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'fake';
    }

    public function checkoutUrl(Book $book): ?string
    {
        return route('payment.fake.show', $book);
    }

    public function bookFor(string $productReference): ?Book
    {
        return Book::where('slug', $productReference)->first();
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $expected = hash_hmac('sha256', $request->getContent(), $this->config['webhook_secret']);

        if (! hash_equals($expected, (string) $request->header(self::SIGNATURE_HEADER))) {
            throw new InvalidWebhookSignature('Signature du webhook invalide.');
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        return new WebhookEvent(
            status: PaymentStatus::tryFrom($payload['status'] ?? '') ?? PaymentStatus::Pending,
            paymentReference: $payload['payment_reference'] ?? null,
            orderReference: $payload['order_reference'] ?? null,
            amount: isset($payload['amount']) ? (int) $payload['amount'] : null,
            eventName: 'fake.'.($payload['status'] ?? 'unknown'),
            productReference: $payload['book'] ?? null,
            customer: (array) ($payload['customer'] ?? []),
        );
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->config['webhook_secret']);
    }
}
