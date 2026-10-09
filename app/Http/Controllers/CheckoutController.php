<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Book;
use App\Models\Order;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Achat sans connexion : le client saisit nom, email et téléphone, le site
 * crée le paiement via l'API du prestataire (Chariow) puis le redirige vers sa
 * page de paiement. Un compte est créé (ou retrouvé) à partir de l'email.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function show(Request $request, Book $book): View|RedirectResponse
    {
        abort_unless($book->is_active, 404);

        if ($request->user()?->hasPurchased($book)) {
            return redirect()->route('dashboard')
                ->with('status', 'Vous possédez déjà « '.$book->title.' » : il est disponible ci-dessous.');
        }

        if (! $this->payments->gateway()->canSell($book)) {
            return redirect()->route('books.show', $book)
                ->with('error', 'Cet e-book n\'est pas encore disponible à l\'achat. Revenez très bientôt !');
        }

        return view('checkout.show', [
            'book' => $book,
            'gateway' => $this->payments->gateway()->name(),
            'user' => $request->user(),
        ]);
    }

    public function store(CheckoutRequest $request, Book $book, OrderPaymentService $service): RedirectResponse
    {
        abort_unless($book->is_active, 404);

        $gateway = $this->payments->gateway();
        $data = $request->validated();

        $customer = $service->customer($data['email'], $data['name'], trim($data['phone_number']), $data['phone_country']);

        if ($customer->hasPurchased($book)) {
            return back()->withInput()->with('error', $request->user()
                ? 'Vous possédez déjà cet e-book : retrouvez-le dans « Mes achats ».'
                : 'Cet email possède déjà cet e-book. Retrouvez le lien de téléchargement dans vos emails, ou connectez-vous (« Mot de passe oublié » si besoin) pour accéder à « Mes achats ».');
        }

        // Réutilise une commande en attente pour éviter les doublons.
        $order = Order::firstOrNew([
            'user_id' => $customer->id,
            'book_id' => $book->id,
            'status' => Order::STATUS_PENDING,
        ]);
        $order->fill([
            'amount' => $book->price,
            'currency' => config('payment.currency'),
            'gateway' => $gateway->name(),
        ])->save();

        try {
            $session = $gateway->createPayment($order, [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => trim($data['phone_number']),
                'country' => $data['phone_country'],
            ]);
        } catch (PaymentException $e) {
            report($e);

            $message = 'Le service de paiement est momentanément indisponible. Merci de réessayer dans quelques instants.';
            // Un administrateur voit la cause exacte (clé API, produit, téléphone…) pour corriger la configuration.
            if ($request->user()?->is_admin) {
                $message .= ' [Admin] Détail : '.$e->getMessage();
            }

            return back()->withInput()->with('error', $message);
        }

        $order->update(['payment_reference' => $session->paymentReference]);

        return redirect()->away($session->redirectUrl);
    }
}
