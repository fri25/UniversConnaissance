<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Payments\PaymentManager;
use App\Payments\PaymentStatus;
use App\Payments\WebhookEvent;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Page de paiement simulée (développement local / démo) : elle imite la page
 * Chariow, où le client saisit ses coordonnées sans être connecté.
 */
class FakePaymentController extends Controller
{
    public function show(Request $request, Book $book): View
    {
        $this->guard($book);

        return view('checkout.fake', ['book' => $book, 'user' => $request->user()]);
    }

    public function complete(Request $request, Book $book, PaymentManager $payments, OrderPaymentService $service): RedirectResponse
    {
        $this->guard($book);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'outcome' => ['required', 'in:success,failure'],
        ], [], ['name' => 'nom', 'phone' => 'téléphone']);

        if ($data['outcome'] === 'failure') {
            return back()->withInput()->with('error', 'Paiement refusé (simulation). Aucun montant n\'a été débité.');
        }

        $sale = 'fake_'.Str::lower(Str::random(16));

        // Même chemin que le webhook d'un vrai prestataire.
        $service->recordSale(new WebhookEvent(
            status: PaymentStatus::Paid,
            paymentReference: $sale,
            amount: $book->price,
            eventName: 'fake.paid',
            productReference: $book->slug,
            customer: ['email' => $data['email'], 'name' => $data['name'], 'phone' => $data['phone'] ?? null, 'country' => 'BJ'],
        ), $payments->gateway('fake'));

        return redirect()->route('checkout.thanks', ['sale' => $sale]);
    }

    private function guard(Book $book): void
    {
        abort_if(app()->isProduction() || config('payment.default') !== 'fake', 404);
        abort_unless($book->is_active, 404);
    }
}
