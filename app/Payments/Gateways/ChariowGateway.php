<?php

namespace App\Payments\Gateways;

use App\Models\Book;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Http\Request;

/**
 * Intégration Chariow sans connexion préalable : le bouton « Acheter » envoie
 * le client sur la page de paiement du produit Chariow, où il saisit ses
 * coordonnées. La vente nous est notifiée par un Pulse (webhook) signé.
 *
 * @see https://chariow.dev/en/guides/pulses
 */
class ChariowGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'x-chariow-signature';

    /**
     * @param  array{pulse_secret: ?string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'chariow';
    }

    public function checkoutUrl(Book $book): ?string
    {
        // Les deux sont nécessaires : l'URL pour payer, l'identifiant pour rattacher la vente au livre.
        if (! $book->chariow_product_id || ! $book->chariow_product_url) {
            return null;
        }

        return str_starts_with($book->chariow_product_url, 'https://') ? $book->chariow_product_url : null;
    }

    public function bookFor(string $productReference): ?Book
    {
        return Book::where('chariow_product_id', $productReference)->first();
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $this->verifySignature($request->getContent(), (string) $request->header(self::SIGNATURE_HEADER));

        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            throw new InvalidWebhookSignature('Corps du Pulse illisible.');
        }

        $event = $payload['event'] ?? null;
        $sale = $payload['sale'] ?? [];

        $status = match ($event) {
            'successful.sale' => PaymentStatus::Paid,
            'failed.sale', 'abandoned.sale' => PaymentStatus::Failed,
            default => null,
        };

        if ($status === null) {
            return WebhookEvent::ignored($event);
        }

        $amount = $sale['amount'] ?? null;
        $customer = $payload['customer'] ?? [];

        return new WebhookEvent(
            status: $status,
            paymentReference: $sale['id'] ?? null,
            orderReference: $sale['custom_metadata']['order_reference'] ?? null,
            // Montant exploitable uniquement s'il est exprimé en francs CFA.
            amount: isset($amount['value']) && ($amount['currency'] ?? null) === 'XOF' ? (int) round($amount['value']) : null,
            eventName: $event,
            productReference: $payload['product']['id'] ?? null,
            customer: [
                'email' => $customer['email'] ?? null,
                'name' => $customer['name'] ?? null,
                'first_name' => $customer['first_name'] ?? null,
                'last_name' => $customer['last_name'] ?? null,
                'phone' => $customer['phone'] ?? null,
                'country' => $customer['country'] ?? null,
            ],
        );
    }

    /**
     * En-tête « sha256=<hex> » : HMAC-SHA256 du corps brut avec le secret du Pulse.
     */
    public function verifySignature(string $payload, string $header): void
    {
        $secret = $this->config['pulse_secret'] ?? null;
        if (! $secret) {
            throw new InvalidWebhookSignature('CHARIOW_PULSE_SECRET non configuré.');
        }

        if (! hash_equals(self::sign($payload, $secret), trim($header))) {
            throw new InvalidWebhookSignature('Signature du Pulse invalide.');
        }
    }

    public static function sign(string $payload, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }
}
