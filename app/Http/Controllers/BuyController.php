<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bouton « Acheter » : redirection directe vers la page de paiement du
 * prestataire, sans connexion. Le client y saisit nom, email et téléphone.
 */
class BuyController extends Controller
{
    public function __invoke(Request $request, Book $book, PaymentManager $payments): RedirectResponse
    {
        abort_unless($book->is_active, 404);

        if ($request->user()?->hasPurchased($book)) {
            return redirect()->route('dashboard')
                ->with('status', 'Vous possédez déjà « '.$book->title.' » : il est disponible ci-dessous.');
        }

        $url = $payments->gateway()->checkoutUrl($book);

        if ($url === null) {
            return redirect()->route('books.show', $book)
                ->with('error', 'Cet e-book n\'est pas encore disponible à l\'achat. Revenez très bientôt !');
        }

        return redirect()->away($url);
    }
}
