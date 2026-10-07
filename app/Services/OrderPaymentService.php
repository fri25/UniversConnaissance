<?php

namespace App\Services;

use App\Jobs\SendMetaPurchase;
use App\Mail\EbookDelivered;
use App\Models\Order;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderPaymentService
{
    public function __construct(
        private readonly DownloadService $downloads,
        private readonly MetaPixel $meta,
    ) {}

    /**
     * Applique un événement de paiement. Idempotent : rejouer le même
     * événement ne change rien et n'envoie pas de second email.
     *
     * @return bool true si une commande a été créée ou a changé d'état
     */
    public function handle(WebhookEvent $event, PaymentGateway $gateway): bool
    {
        $order = $this->findOrder($event);

        if ($order !== null) {
            return match ($event->status) {
                PaymentStatus::Paid => $this->markAsPaid($order, $event->paymentReference),
                PaymentStatus::Failed => $this->markAsFailed($order),
                PaymentStatus::Refunded => $this->markAsRefunded($order),
                PaymentStatus::Pending => false,
            };
        }

        // Échec ou abandon d'une vente jamais enregistrée chez nous : rien à faire.
        if ($event->status !== PaymentStatus::Paid) {
            return false;
        }

        return $this->recordSale($event, $gateway) !== null;
    }

    /**
     * Enregistre une vente conclue sur la page du prestataire par un client
     * qui ne s'est pas connecté : on retrouve (ou crée) son compte à partir de
     * l'email saisi au paiement, puis on lui livre l'e-book.
     */
    public function recordSale(WebhookEvent $event, PaymentGateway $gateway): ?Order
    {
        $book = $event->productReference !== null ? $gateway->bookFor($event->productReference) : null;
        $email = $event->customerEmail();

        if ($book === null || $email === null || $event->paymentReference === null) {
            Log::error('Paiement : vente impossible à rattacher (livre, email ou référence manquant).', [
                'payment_reference' => $event->paymentReference,
                'product' => $event->productReference,
                'email' => $email,
            ]);

            return null;
        }

        if ($event->amount !== null && $event->amount !== $book->price) {
            Log::warning('Paiement : montant encaissé différent du prix affiché sur le site (vérifiez le prix chez le prestataire).', [
                'book' => $book->slug, 'site' => $book->price, 'paid' => $event->amount, 'sale' => $event->paymentReference,
            ]);
        }

        $user = $this->customerFor($event, $email);

        try {
            $order = DB::transaction(function () use ($event, $gateway, $book, $user) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'amount' => $event->amount ?? $book->price,
                    'currency' => config('payment.currency'),
                    'status' => Order::STATUS_PAID,
                    'gateway' => $gateway->name(),
                    'payment_reference' => $event->paymentReference,
                    'paid_at' => now(),
                ]);

                $this->downloads->issue($order);

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // Même vente reçue deux fois en parallèle : déjà enregistrée.
            return null;
        }

        $this->sendDeliveryEmail($order);
        $this->trackPurchase($order);

        return $order;
    }

    public function markAsPaid(Order $order, ?string $paymentReference = null, ?int $amount = null): bool
    {
        $changed = DB::transaction(function () use ($order, $paymentReference, $amount) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isPaid() || $locked->status === Order::STATUS_REFUNDED) {
                return false;
            }

            if ($amount !== null && $amount !== $locked->amount) {
                Log::error('Paiement : montant reçu différent du montant de la commande.', [
                    'order' => $locked->reference,
                    'expected' => $locked->amount,
                    'received' => $amount,
                ]);

                return false;
            }

            $locked->forceFill([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'payment_reference' => $paymentReference ?? $locked->payment_reference,
            ])->save();

            $this->downloads->issue($locked);

            $order->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($changed) {
            $this->sendDeliveryEmail($order);
            $this->trackPurchase($order);
        }

        return $changed;
    }

    public function markAsFailed(Order $order): bool
    {
        return Order::whereKey($order->getKey())
            ->where('status', Order::STATUS_PENDING)
            ->update(['status' => Order::STATUS_FAILED, 'updated_at' => now()]) > 0;
    }

    public function markAsRefunded(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $updated = Order::whereKey($order->getKey())
                ->where('status', Order::STATUS_PAID)
                ->update(['status' => Order::STATUS_REFUNDED, 'updated_at' => now()]) > 0;

            if ($updated) {
                // Un remboursement coupe l'accès au fichier.
                $order->download()->delete();
            }

            return $updated;
        });
    }

    public function sendDeliveryEmail(Order $order): void
    {
        $order->loadMissing('user', 'book', 'download');

        Mail::to($order->user)->queue(new EbookDelivered($order));
    }

    /**
     * Statistiques publicitaires (pixel Meta, API Conversions) si configurées.
     */
    private function trackPurchase(Order $order): void
    {
        if ($this->meta->serverSideEnabled()) {
            SendMetaPurchase::dispatch($order);
        }
    }

    /**
     * Compte existant (même email) ou nouveau compte « invité » : le client
     * choisira son mot de passe via le lien reçu dans l'email de livraison.
     */
    private function customerFor(WebhookEvent $event, string $email): User
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing;
        }

        $country = strtoupper((string) ($event->customer['country'] ?? ''));

        try {
            $user = User::create([
                'name' => Str::limit($event->customerName(), 255, ''),
                'email' => $email,
                'phone' => $event->customer['phone'] ?? null,
                'phone_country' => strlen($country) === 2 ? $country : null,
                'password' => Hash::make(Str::random(40)),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Créé entre-temps par un webhook concurrent.
            return User::where('email', $email)->firstOrFail();
        }

        $user->forceFill(['is_guest' => true])->save();

        return $user;
    }

    private function findOrder(WebhookEvent $event): ?Order
    {
        if ($event->paymentReference !== null) {
            $order = Order::where('payment_reference', $event->paymentReference)->first();
            if ($order) {
                return $order;
            }
        }

        return $event->orderReference !== null
            ? Order::where('reference', $event->orderReference)->first()
            : null;
    }
}
