<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pixel Meta (navigateur) et API Conversions (serveur).
 *
 * L'achat a lieu sur la page de paiement Chariow, hors du site : le pixel du
 * navigateur ne le voit pas toujours. L'événement Purchase est donc aussi
 * envoyé par le serveur dès que la vente est confirmée, avec le même
 * event_id que côté navigateur pour que Meta ne le compte qu'une fois.
 */
class MetaPixel
{
    public function pixelId(): ?string
    {
        $id = Setting::get('meta_pixel_id', config('services.meta.pixel_id'));

        return is_string($id) && preg_match('/^\d{8,20}$/', $id) ? $id : null;
    }

    public function enabled(): bool
    {
        return $this->pixelId() !== null;
    }

    public function capiToken(): ?string
    {
        return Setting::get('meta_capi_token', config('services.meta.capi_token')) ?: null;
    }

    public function serverSideEnabled(): bool
    {
        return $this->enabled() && $this->capiToken() !== null;
    }

    /**
     * Identifiant d'événement partagé navigateur / serveur (déduplication).
     */
    public static function purchaseEventId(Order $order): string
    {
        return 'purchase-'.$order->reference;
    }

    /**
     * @return array<string, mixed>
     */
    public static function bookData(\App\Models\Book $book): array
    {
        return [
            'content_ids' => [(string) $book->id],
            'content_name' => $book->title,
            'content_type' => 'product',
            'value' => $book->price,
            'currency' => 'XOF',
        ];
    }

    /**
     * Envoie l'achat via l'API Conversions. Silencieux si non configurée.
     */
    public function sendPurchase(Order $order): bool
    {
        if (! $this->serverSideEnabled()) {
            return false;
        }

        $order->loadMissing('user', 'book');
        $user = $order->user;

        $userData = array_filter([
            'em' => [hash('sha256', mb_strtolower(trim($user->email)))],
            'ph' => $user->phone ? [hash('sha256', preg_replace('/\D+/', '', $user->phone))] : null,
            'fn' => [hash('sha256', mb_strtolower(Str::before(trim($user->name), ' ')))],
            'external_id' => [hash('sha256', (string) $user->id)],
            'country' => $user->phone_country ? [hash('sha256', mb_strtolower($user->phone_country))] : null,
        ]);

        $payload = array_filter([
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => ($order->paid_at ?? now())->getTimestamp(),
                'event_id' => self::purchaseEventId($order),
                'action_source' => 'website',
                'event_source_url' => route('books.show', $order->book),
                'user_data' => $userData,
                'custom_data' => [
                    'currency' => 'XOF',
                    'value' => $order->amount,
                    'content_ids' => [(string) $order->book_id],
                    'content_name' => $order->book->title,
                    'content_type' => 'product',
                    'order_id' => $order->reference,
                ],
            ]],
            'test_event_code' => Setting::get('meta_test_event_code', config('services.meta.test_event_code')),
        ]);

        $url = sprintf('https://graph.facebook.com/%s/%s/events', config('services.meta.graph_version'), $this->pixelId());

        try {
            $response = Http::timeout(10)->asJson()->post($url.'?access_token='.urlencode($this->capiToken()), $payload);
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI : envoi impossible ('.$e->getMessage().').', ['order' => $order->reference]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('Meta CAPI : erreur '.$response->status().' : '.Str::limit($response->body(), 300), ['order' => $order->reference]);

            return false;
        }

        return true;
    }
}
