<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Services\DownloadService;
use App\Services\PdfWatermarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert le fichier complet depuis le stockage privé. Contrôles : URL signée et
 * temporaire (middleware), jeton secret non expiré, commande payée, quota non
 * atteint. Le client n'a pas besoin d'être connecté (lien reçu par email après
 * un achat sans compte) ; s'il est connecté avec un autre compte, l'accès est refusé.
 * Le filigrane nom/email décourage le partage du lien.
 */
class DownloadController extends Controller
{
    public function __invoke(Request $request, Download $download, string $format, DownloadService $downloads, PdfWatermarker $watermarker): Response
    {
        $order = $download->order()->with(['book', 'user'])->firstOrFail();

        abort_unless($order->isPaid(), 403);
        abort_if($request->user() && $request->user()->id !== $order->user_id, 403, 'Ce lien appartient à un autre compte.');

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
