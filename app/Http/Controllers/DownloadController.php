<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Services\DownloadService;
use App\Services\PdfWatermarker;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert le fichier complet depuis le stockage privé. Triple contrôle :
 * URL signée (middleware), utilisateur connecté propriétaire de la commande
 * payée (policy), jeton non expiré et quota non atteint.
 */
class DownloadController extends Controller
{
    public function __invoke(Download $download, string $format, DownloadService $downloads, PdfWatermarker $watermarker): Response
    {
        $order = $download->order()->with(['book', 'user'])->firstOrFail();

        Gate::authorize('download', $order);

        abort_if($download->isExpired(), 410, 'Ce lien a expiré. Relancez le téléchargement depuis « Mes achats ».');
        abort_if($download->isExhausted(), 429, 'Nombre maximal de téléchargements atteint.');

        $book = $order->book;
        $path = $book->filePathFor($format);
        $disk = Storage::disk(config('ebooks.disk'));

        abort_unless($path && $disk->exists($path), 404, 'Fichier indisponible. Notre équipe a été prévenue.');
        abort_unless($downloads->consume($download), 429, 'Nombre maximal de téléchargements atteint.');

        $filename = Str::slug($book->title).'.'.$format;

        if ($format === 'pdf' && config('ebooks.watermark')) {
            $stamped = $watermarker->stamp(
                $disk->path($path),
                sprintf('Licence personnelle de %s (%s) - commande %s - %s', $order->user->name, $order->user->email, $order->reference, config('store.name')),
            );

            if ($stamped !== null) {
                return response($stamped, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    'Cache-Control' => 'private, no-store',
                ]);
            }
        }

        return $disk->download($path, $filename, ['Cache-Control' => 'private, no-store']);
    }
}
