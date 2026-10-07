<x-app-layout title="Merci pour votre achat">
    @if ($order)
        @php
            $pixelPurchase = ['value' => $order->amount, 'currency' => 'XOF', 'content_ids' => [(string) $order->book_id], 'content_type' => 'product'];
            $pixelEventId = \App\Services\MetaPixel::purchaseEventId($order);
        @endphp
        @push('pixel-events')
            ucTrack('Purchase', @json($pixelPurchase), { eventID: @json($pixelEventId) });
        @endpush
    @endif
    <div class="container-page max-w-xl py-14 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </div>

        @if ($order)
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Paiement confirmé !</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Merci ! « {{ $order->book->title }} » est à vous. Un email avec votre lien de téléchargement
                a été envoyé à <strong>{{ $maskedEmail }}</strong>.
            </p>
            @if ($links)
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    @foreach ($links as $format => $url)
                        <a href="{{ $url }}" class="btn-primary">Télécharger ({{ $format }})</a>
                    @endforeach
                </div>
            @endif
        @else
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Merci pour votre achat !</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Dès que votre paiement est confirmé (en général en moins d'une minute), vous recevez par email
                votre <strong>lien de téléchargement</strong>, à l'adresse saisie lors du paiement.
            </p>
            <p class="mt-3 text-sm text-slate-500">Pensez à vérifier vos courriers indésirables.</p>
        @endif

        <div class="card mt-10 p-5 text-left text-sm">
            <p class="font-semibold text-ink dark:text-white">Retrouver vos e-books à tout moment</p>
            <p class="mt-1 text-slate-600 dark:text-slate-400">
                Vos achats sont rattachés à votre email. Nouveau client ? Utilisez le lien « Créer mon mot de passe » de l'email,
                ou <a href="{{ route('password.request') }}" class="link">Mot de passe oublié</a>, puis accédez à « Mes achats ».
            </p>
        </div>

        <p class="mt-8 text-sm text-slate-500">
            Un souci ? Écrivez-nous à <a href="mailto:{{ config('store.support.email') }}" class="link">{{ config('store.support.email') }}</a>.
        </p>
        <a href="{{ route('books.index') }}" class="btn-ghost mt-4">Continuer mes découvertes</a>
    </div>
</x-app-layout>
