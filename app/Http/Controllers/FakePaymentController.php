<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page de paiement simulée (développement local / démo), atteinte par un lien
 * signé après le formulaire d'achat. Interdite en production.
 */
class FakePaymentController extends Controller
{
    public function show(Order $order): View
    {
        $this->guard($order);

        return view('checkout.fake', ['order' => $order->load('book', 'user')]);
    }

    public function complete(Request $request, Order $order, OrderPaymentService $service): RedirectResponse
    {
        $this->guard($order);

        $request->validate(['outcome' => ['required', 'in:success,failure']]);

        if ($request->input('outcome') === 'failure') {
            $service->markAsFailed($order);
        } else {
            $service->markAsPaid($order, $order->payment_reference, $order->amount);
        }

        return redirect()->to($order->returnUrl());
    }

    private function guard(Order $order): void
    {
        abort_if(app()->isProduction() || $order->gateway !== 'fake', 404);
    }
}
