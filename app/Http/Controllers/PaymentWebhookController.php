<?php

namespace App\Http\Controllers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, OrderPaymentService $service): JsonResponse
    {
        try {
            $event = $gateway->parseWebhook($request);
        } catch (InvalidWebhookSignature $e) {
            Log::warning('Webhook de paiement rejeté : '.$e->getMessage(), ['ip' => $request->ip()]);

            return response()->json(['message' => 'Signature invalide.'], 400);
        }

        if (! $event->isActionable()) {
            return response()->json(['message' => 'Événement ignoré.']);
        }

        $changed = $service->handle($event);

        // Toujours 200 une fois la signature validée : le prestataire ne doit
        // pas rejouer indéfiniment un événement déjà traité.
        return response()->json([
            'message' => $changed ? 'Commande mise à jour.' : 'Déjà traité.',
        ]);
    }
}
