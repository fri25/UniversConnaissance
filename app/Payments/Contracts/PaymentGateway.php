<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentSession;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Identifiant court du prestataire (stocké sur la commande).
     */
    public function name(): string;

    /**
     * Initialise un paiement chez le prestataire et renvoie l'URL de redirection.
     *
     * @throws PaymentException
     */
    public function createPayment(Order $order): PaymentSession;

    /**
     * Vérifie la signature d'un webhook et le traduit en événement neutre.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(Request $request): WebhookEvent;

    /**
     * Interroge le prestataire pour connaître le statut réel d'une transaction.
     *
     * @throws PaymentException
     */
    public function fetchStatus(string $paymentReference): PaymentStatus;
}
