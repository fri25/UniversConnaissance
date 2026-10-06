<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Order;
use Illuminate\Support\Facades\URL;

class DownloadService
{
    /**
     * Crée (ou réutilise) le droit de téléchargement d'une commande payée.
     */
    public function issue(Order $order): Download
    {
        return Download::firstOrCreate(
            ['order_id' => $order->id],
            [
                'token' => Download::newToken(),
                'expires_at' => now()->addHours(config('ebooks.link_ttl_hours')),
                'max_downloads' => config('ebooks.max_downloads'),
            ],
        );
    }

    /**
     * Garantit un lien valide dans le temps (sans toucher au quota).
     */
    public function ensureFresh(Download $download): Download
    {
        if ($download->isExpired()) {
            $download->renew();
        }

        return $download;
    }

    /**
     * URL signée et temporaire vers le fichier. La route vérifie en plus
     * l'authentification et la propriété de la commande.
     */
    public function signedUrl(Download $download, string $format): string
    {
        return URL::temporarySignedRoute(
            'downloads.file',
            $download->expires_at,
            ['download' => $download->token, 'format' => $format],
        );
    }

    /**
     * Incrémente le compteur de façon atomique ; false si le quota est atteint.
     */
    public function consume(Download $download): bool
    {
        $updated = Download::whereKey($download->getKey())
            ->whereColumn('download_count', '<', 'max_downloads')
            ->update([
                'download_count' => $download->download_count + 1,
                'last_downloaded_at' => now(),
            ]);

        return $updated > 0;
    }
}
