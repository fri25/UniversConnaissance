<?php

namespace App\Payments\Gateways;

use App\Models\Book;
use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentSession;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use App\Support\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Intégration Chariow par API : le site crée le paiement (POST /v1/checkout)
 * avec les coordonnées saisies par le client, puis le redirige vers la page de
 * paiement Chariow. La vente est confirmée par un Pulse (webhook) signé.
 *
 * Le prix est celui du produit Chariow : chaque e-book doit y être lié
 * (`books.chariow_product_id`).
 *
 * @see https://chariow.dev
 */
class ChariowGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'x-chariow-signature';

    /**
     * @param  array{base_url: string, api_key: ?string, pulse_secret: ?string, payment_currency: ?string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'chariow';
    }

    public function canSell(Book $book): bool
    {
        return (bool) $book->chariow_product_id;
    }

    public function createPayment(Order $order, array $customer): PaymentSession
    {
        $order->loadMissing('book');

        if (! $this->canSell($order->book)) {
            throw new PaymentException("L'e-book « {$order->book->title} » n'est lié à aucun produit Chariow.");
        }
        if (empty($customer['phone']) || empty($customer['country'])) {
            throw new PaymentException('Numéro de téléphone requis pour le paiement Chariow.');
        }

        [$firstname, $lastname] = $this->splitName($customer['name']);
        $returnUrl = $order->returnUrl();

        $data = $this->request('post', '/checkout', array_filter([
            'product_id' => $order->book->chariow_product_id,
            'email' => $customer['email'],
            'first_name' => Str::limit($firstname, 50, ''),
            'last_name' => Str::limit($lastname, 50, ''),
            'phone' => [
                'number' => Phone::digits($customer['phone'], $customer['country']),
                'country_code' => $customer['country'],
            ],
            'payment_currency' => $this->config['payment_currency'] ?? null,
            'redirect_url' => $returnUrl,
            'custom_metadata' => ['order_reference' => $order->reference],
            'customer_ip' => request()?->ip(),
        ]))['data'] ?? [];

        $saleId = $data['purchase']['id'] ?? null;

        return match ($data['step'] ?? null) {
            'payment' => isset($saleId, $data['payment']['checkout_url'])
                ? new PaymentSession($saleId, $data['payment']['checkout_url'])
                : throw new PaymentException('Réponse Chariow incomplète (checkout_url manquant).'),
            // Produit gratuit : la vente est déjà conclue, la page de retour la confirmera.
            'completed' => $saleId
                ? new PaymentSession($saleId, $returnUrl)
                : throw new PaymentException('Réponse Chariow incomplète (vente manquante).'),
            'already_purchased' => throw new PaymentException('Chariow indique que ce client a déjà acheté ce produit.'),
            default => throw new PaymentException('Réponse Chariow inattendue : '.Str::limit((string) json_encode($data), 200)),
        };
    }

    public function fetchStatus(string $paymentReference): PaymentStatus
    {
        $sale = $this->request('get', '/sales/'.rawurlencode($paymentReference))['data'] ?? [];

        return match ($sale['status'] ?? null) {
            'completed', 'settled' => PaymentStatus::Paid,
            'failed', 'abandoned' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
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

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $data = []): array
    {
        if (empty($this->config['api_key'])) {
            throw new PaymentException('CHARIOW_API_KEY non configurée.');
        }

        try {
            $response = $this->client()->{$method}($uri, $data);
        } catch (ConnectionException $e) {
            throw new PaymentException('Impossible de joindre Chariow : '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new PaymentException('Erreur Chariow ('.$response->status().') : '.Str::limit($response->body(), 300));
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->config['base_url'], '/'))
            ->withToken($this->config['api_key'])
            ->acceptJson()
            ->asJson()
            ->timeout($this->config['timeout'] ?? 15);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        return [$parts[0], $parts[1] ?? $parts[0]];
    }
}
