<?php

namespace App\Payments\Contracts;

use App\Models\Book;
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
     * L'e-book peut-il être vendu par ce prestataire (ex. produit Chariow lié) ?
     */
    public function canSell(Book $book): bool;

    /**
     * Crée le paiement chez le prestataire pour une commande en attente et
     * renvoie l'URL de la page de paiement vers laquelle rediriger le client.
     *
     * @param  array{name: string, email: string, phone: string, country: string}  $customer  coordonnées saisies à l'achat
     *
     * @throws PaymentException
     */
    public function createPayment(Order $order, array $customer): PaymentSession;

    /**
     * Interroge le prestataire sur l'état réel d'une vente.
     *
     * @throws PaymentException
     */
    public function fetchStatus(string $paymentReference): PaymentStatus;

    /**
     * Retrouve l'e-book correspondant à la référence produit du prestataire.
     */
    public function bookFor(string $productReference): ?Book;

    /**
     * Vérifie la signature d'un webhook et le traduit en événement neutre.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(Request $request): WebhookEvent;
}
