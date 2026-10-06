<?php

namespace App\Services;

use App\Mail\EbookDelivered;
use App\Models\Order;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderPaymentService
{
    public function __construct(private readonly DownloadService $downloads) {}

    /**
     * Applique un événement de paiement. Idempotent : rejouer le même
     * événement ne change rien et n'envoie pas de second email.
     *
     * @return bool true si la commande a changé d'état
     */
    public function handle(WebhookEvent $event): bool
    {
        $order = $this->findOrder($event);

        if ($order === null) {
            Log::warning('Paiement : commande introuvable pour le webhook.', [
                'payment_reference' => $event->paymentReference,
                'order_reference' => $event->orderReference,
                'event' => $event->eventName,
            ]);

            return false;
        }

        return match ($event->status) {
            PaymentStatus::Paid => $this->productMatches($order, $event)
                ? $this->markAsPaid($order, $event->paymentReference, $event->productReference ? null : $event->amount)
                : false,
            PaymentStatus::Failed => $this->markAsFailed($order),
            PaymentStatus::Refunded => $this->markAsRefunded($order),
            PaymentStatus::Pending => false,
        };
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
     * Quand le prestataire fixe le prix par produit (Chariow), on vérifie que la
     * vente porte bien sur le produit lié au livre commandé.
     */
    private function productMatches(Order $order, WebhookEvent $event): bool
    {
        if ($event->productReference === null) {
            return true;
        }

        $expected = $order->book()->value('chariow_product_id');
        if ($expected === $event->productReference) {
            if ($event->amount !== null && $event->amount !== $order->amount) {
                Log::warning('Paiement : montant Chariow différent du prix du site (vérifiez le prix du produit Chariow).', [
                    'order' => $order->reference, 'site' => $order->amount, 'chariow' => $event->amount,
                ]);
            }

            return true;
        }

        Log::error('Paiement : produit de la vente différent du produit commandé.', [
            'order' => $order->reference, 'expected' => $expected, 'received' => $event->productReference,
        ]);

        return false;
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
