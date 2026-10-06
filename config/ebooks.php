<?php

return [
    // Durée de validité (heures) d'un lien de téléchargement signé.
    'link_ttl_hours' => (int) env('EBOOK_LINK_TTL_HOURS', 72),

    // Nombre maximal de téléchargements par achat.
    'max_downloads' => (int) env('EBOOK_MAX_DOWNLOADS', 5),

    // Filigrane nom/email de l'acheteur sur les PDF.
    'watermark' => (bool) env('EBOOK_WATERMARK', true),

    // Disque privé où sont stockés les fichiers complets et les extraits.
    'disk' => env('EBOOK_DISK', 'local'),
];
