<?php

namespace App\Payments\Contracts;

use App\Models\Book;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\WebhookEvent;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Identifiant court du prestataire (stocké sur la commande).
     */
    public function name(): string;

    /**
     * Page de paiement hébergée vers laquelle rediriger le client (sans
     * connexion préalable : l'acheteur y saisit ses coordonnées), ou null si
     * l'e-book n'est pas encore vendable chez ce prestataire.
     */
    public function checkoutUrl(Book $book): ?string;

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
