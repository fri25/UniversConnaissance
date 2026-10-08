<x-app-layout title="Votre commande {{ $order->reference }}">
    @if ($order->isPaid())
        @php
            $pixelPurchase = ['value' => $order->amount, 'currency' => 'XOF', 'content_ids' => [(string) $order->book_id], 'content_type' => 'product'];
            $pixelEventId = \App\Services\MetaPixel::purchaseEventId($order);
        @endphp
        @push('pixel-events')
            ucTrack('Purchase', @json($pixelPurchase), { eventID: @json($pixelEventId) });
        @endpush
    @endif

    <div class="container-page max-w-xl py-14 text-center">
        @if ($order->isPaid())
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
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
        @elseif ($order->isPending())
            <div class="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600" aria-hidden="true"></div>
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Paiement en cours de confirmation</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Validez l'opération sur votre téléphone si ce n'est pas déjà fait. Cette page se met à jour automatiquement,
                et votre lien de téléchargement sera aussi envoyé à <strong>{{ $maskedEmail }}</strong>.
            </p>
            <meta http-equiv="refresh" content="8">
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-700">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Le paiement n'a pas abouti</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">Aucun montant n'a été débité. Vous pouvez réessayer.</p>
            <a href="{{ route('checkout.show', $order->book) }}" class="btn-primary mt-8">Réessayer le paiement</a>
        @endif

        @if (! $order->isFailed())
            <div class="card mt-10 p-5 text-left text-sm">
                <p class="font-semibold text-ink dark:text-white">Retrouver vos e-books à tout moment</p>
                <p class="mt-1 text-slate-600 dark:text-slate-400">
                    Vos achats sont rattachés à votre email. Nouveau client ? Utilisez le lien « Créer mon mot de passe » de l'email,
                    ou <a href="{{ route('password.request') }}" class="link">Mot de passe oublié</a>, puis accédez à « Mes achats ».
                </p>
            </div>
        @endif

        <p class="mt-8 text-sm text-slate-500">
            Commande {{ $order->reference }} · Un souci ? Écrivez-nous à <a href="mailto:{{ config('store.support.email') }}" class="link">{{ config('store.support.email') }}</a>.
        </p>
        <a href="{{ route('books.index') }}" class="btn-ghost mt-4">Continuer mes découvertes</a>
    </div>
</x-app-layout>
