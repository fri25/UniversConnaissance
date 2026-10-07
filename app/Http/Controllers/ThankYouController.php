<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page de retour après paiement. Avec ?sale=<référence de vente> et si la
 * vente vient d'être confirmée, on propose directement le téléchargement ;
 * sinon on invite le client à surveiller sa boîte mail.
 */
class ThankYouController extends Controller
{
    /**
     * Durée pendant laquelle la page de remerciement donne accès au fichier.
     */
    private const FRESH_MINUTES = 120;

    public function __invoke(Request $request, DownloadService $downloads): View
    {
        $order = null;
        $links = [];

        $sale = $request->query('sale');
        if (is_string($sale) && $sale !== '' && strlen($sale) <= 100) {
            $order = Order::with('book', 'user', 'download')
                ->where('payment_reference', $sale)
                ->where('status', Order::STATUS_PAID)
                ->where('paid_at', '>=', now()->subMinutes(self::FRESH_MINUTES))
                ->first();
        }

        if ($order?->download && ! $order->download->isExhausted()) {
            $download = $downloads->ensureFresh($order->download);
            foreach ($order->book->availableFormats() as $format) {
                $links[strtoupper($format)] = $downloads->signedUrl($download, $format);
            }
        }

        return view('checkout.thanks', [
            'order' => $order,
            'links' => $links,
            'maskedEmail' => $order ? $this->mask($order->user->email) : null,
        ]);
    }

    private function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 2).str_repeat('•', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }
}
