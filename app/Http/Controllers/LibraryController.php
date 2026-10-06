<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with(['book' => fn ($q) => $q->with('authors:id,name'), 'download'])
            ->latest()
            ->get();

        return view('library.index', [
            'purchases' => $orders->where('status', Order::STATUS_PAID)->values(),
            'otherOrders' => $orders->where('status', '!=', Order::STATUS_PAID)->values(),
        ]);
    }

    /**
     * Depuis la bibliothèque : régénère au besoin un lien valide puis redirige
     * vers l'URL signée.
     */
    public function download(Order $order, string $format, DownloadService $downloads): RedirectResponse
    {
        Gate::authorize('download', $order);

        abort_unless(in_array($format, $order->book->availableFormats(), true), 404);

        $download = $downloads->ensureFresh($downloads->issue($order));

        if ($download->isExhausted()) {
            return back()->with('error', 'Vous avez atteint le nombre maximal de téléchargements pour cet e-book. Contactez le support si besoin.');
        }

        return redirect()->to($downloads->signedUrl($download, $format));
    }
}
