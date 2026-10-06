<?php

return [
    /*
    | Prestataire actif : "chariow" (production) ou "fake" (simulation locale,
    | refusée en production).
    */
    'default' => env('PAYMENT_GATEWAY', 'fake'),

    'currency' => 'XOF',

    'gateways' => [
        'chariow' => [
            'base_url' => env('CHARIOW_BASE_URL', 'https://api.chariow.com/v1'),
            // Clé API : tableau de bord Chariow > Paramètres > API (sk_live_…)
            'api_key' => env('CHARIOW_API_KEY'),
            // Secret de signature du Pulse (whsec_…) : Automatisations > Pulses
            'pulse_secret' => env('CHARIOW_PULSE_SECRET'),
            // Devise de paiement (ISO 4217) ; vide = devise par défaut de la boutique Chariow.
            'payment_currency' => env('CHARIOW_PAYMENT_CURRENCY', 'XOF'),
            'timeout' => 15,
        ],

        'fake' => [
            // Secret HMAC utilisé pour signer les webhooks simulés.
            'webhook_secret' => env('FAKE_PAYMENT_WEBHOOK_SECRET', 'fake-secret'),
        ],
    ],
];
