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
            // Secret de signature du Pulse (whsec_…) : Automatisations > Pulses
            'pulse_secret' => env('CHARIOW_PULSE_SECRET'),
        ],

        'fake' => [
            // Secret HMAC utilisé pour signer les webhooks simulés.
            'webhook_secret' => env('FAKE_PAYMENT_WEBHOOK_SECRET', 'fake-secret'),
        ],
    ],
];
