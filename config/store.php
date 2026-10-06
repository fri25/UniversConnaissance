<?php

return [
    'name' => 'Univers Connaissance',
    'tagline' => 'Le savoir à portée de clic.',

    'support' => [
        'email' => env('STORE_SUPPORT_EMAIL', 'support@universconnaissance.com'),
        'phone' => env('STORE_SUPPORT_PHONE', '+229 01 00 00 00 00'),
        'hours' => 'Du lundi au samedi, 8h – 19h (GMT+1)',
    ],

    'socials' => [
        'facebook' => env('STORE_FACEBOOK_URL', 'https://facebook.com'),
        'instagram' => env('STORE_INSTAGRAM_URL', 'https://instagram.com'),
        'whatsapp' => env('STORE_WHATSAPP_URL', 'https://wa.me/22900000000'),
        'tiktok' => env('STORE_TIKTOK_URL', 'https://tiktok.com'),
    ],
];
