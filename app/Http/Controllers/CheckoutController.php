<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Book;
use App\Models\Order;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use App\Payments\PaymentStatus;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function show(Request $request, Book $book): View|RedirectResponse
    {
        abort_unless($book->is_active, 404);

        if ($request->user()->hasPurchased($book)) {
            return redirect()->route('books.show', $book)
                ->with('status', 'Vous possédez déjà cet e-book : retrouvez-le dans « Mes achats ».');
        }

        $book->load('authors');

        return view('checkout.show', [
            'book' => $book,
            'gateway' => $this->payments->gateway()->name(),
        ]);
    }

    public function store(CheckoutRequest $request, Book $book): Response
    {
        $user = $request->user();
        $user->update([
            'phone' => trim($request->input('phone_number')),
            'phone_country' => $request->input('phone_country'),
        ]);
        $gateway = $this->payments->gateway();

        // Réutilise une commande en attente pour éviter les doublons.
        $order = Order::firstOrNew([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Order::STATUS_PENDING,
        ]);
        $order->fill([
            'amount' => $book->price,
            'currency' => config('payment.currency'),
            'gateway' => $gateway->name(),
        ])->save();

        try {
            $session = $gateway->createPayment($order);
        } catch (PaymentException $e) {
            report($e);

            return back()->with('error', 'Le service de paiement est momentanément indisponible. Merci de réessayer dans quelques instants.');
        }

        $order->update(['payment_reference' => $session->paymentReference]);

        return redirect()->away($session->redirectUrl);
    }

    /**
     * Retour du client depuis la page de paiement. Le statut est revérifié
     * auprès du prestataire : on ne fait jamais confiance aux paramètres d'URL.
     */
    public function return(Order $order, OrderPaymentService $service): View
    {
        Gate::authorize('view', $order);

        if ($order->isPending() && $order->payment_reference && $order->gateway !== 'fake') {
            try {
                $status = $this->payments->gateway($order->gateway)->fetchStatus($order->payment_reference);

                match ($status) {
                    PaymentStatus::Paid => $service->markAsPaid($order, $order->payment_reference),
                    PaymentStatus::Failed => $service->markAsFailed($order),
                    default => null,
                };
                $order->refresh();
            } catch (PaymentException $e) {
                report($e);
            }
        }

        return view('checkout.return', ['order' => $order->load('book')]);
    }
}
