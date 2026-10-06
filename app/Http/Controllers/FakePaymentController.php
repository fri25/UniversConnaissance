<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Page de paiement simulée (développement local uniquement).
 */
class FakePaymentController extends Controller
{
    public function show(Order $order): View
    {
        $this->guard($order);

        return view('checkout.fake', ['order' => $order->load('book')]);
    }

    public function complete(Request $request, Order $order, OrderPaymentService $service): RedirectResponse
    {
        $this->guard($order);

        $request->validate(['outcome' => ['required', 'in:success,failure']]);

        $request->input('outcome') === 'success'
            ? $service->markAsPaid($order, $order->payment_reference, $order->amount)
            : $service->markAsFailed($order);

        return redirect()->route('checkout.return', $order);
    }

    private function guard(Order $order): void
    {
        abort_if(app()->isProduction() || $order->gateway !== 'fake', 404);
        Gate::authorize('view', $order);
        abort_unless($order->user_id === auth()->id(), 403);
    }
}
