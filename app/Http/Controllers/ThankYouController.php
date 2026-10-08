<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Payments\PaymentStatus;
use App\Services\DownloadService;
use App\Services\OrderPaymentService;
use Illuminate\View\View;

/**
 * Retour du client après paiement. Le lien est signé (le client qui a payé
 * est le seul à le recevoir) : on peut donc proposer directement le
 * téléchargement, sans connexion. Le statut est revérifié auprès du
 * prestataire si le webhook n'est pas encore arrivé.
 */
class ThankYouController extends Controller
{
    public function __invoke(Order $order, PaymentManager $payments, OrderPaymentService $service, DownloadService $downloads): View
    {
        if ($order->isPending() && $order->payment_reference && $order->gateway !== 'fake') {
            try {
                match ($payments->gateway($order->gateway)->fetchStatus($order->payment_reference)) {
                    PaymentStatus::Paid => $service->markAsPaid($order, $order->payment_reference),
                    PaymentStatus::Failed => $service->markAsFailed($order),
                    default => null,
                };
                $order->refresh();
            } catch (PaymentException $e) {
                report($e);
            }
        }

        $order->load('book', 'user', 'download');
        $links = [];

        if ($order->isPaid() && $order->download && ! $order->download->isExhausted()) {
            $download = $downloads->ensureFresh($order->download);
            foreach ($order->book->availableFormats() as $format) {
                $links[strtoupper($format)] = $downloads->signedUrl($download, $format);
            }
        }

        return view('checkout.thanks', [
            'order' => $order,
            'links' => $links,
            'maskedEmail' => $this->mask($order->user->email),
        ]);
    }

    private function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 2).str_repeat('•', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }
}
