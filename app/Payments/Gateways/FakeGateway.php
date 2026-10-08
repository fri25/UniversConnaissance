<?php

namespace App\Payments\Gateways;

use App\Models\Book;
use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentSession;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Prestataire simulé pour le développement local, la démo et les tests.
 * Interdit en production (voir PaymentManager).
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

    public function canSell(Book $book): bool
    {
        return true;
    }

    public function createPayment(Order $order, array $customer): PaymentSession
    {
        // Lien signé : sans compte, seul celui qui vient de remplir le formulaire peut « payer ».
        return new PaymentSession(
            'fake_'.Str::lower(Str::random(16)),
            URL::temporarySignedRoute('payment.fake.show', now()->addHour(), ['order' => $order->reference]),
        );
    }

    public function fetchStatus(string $paymentReference): PaymentStatus
    {
        // La simulation confirme la commande directement : pas d'état distant.
        return PaymentStatus::Pending;
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
