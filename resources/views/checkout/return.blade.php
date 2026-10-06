<x-app-layout title="Commande {{ $order->reference }}">
    <div class="container-page max-w-xl py-14 text-center">
        @if ($order->isPaid())
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Paiement confirmé !</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                « {{ $order->book->title }} » a été ajouté à votre bibliothèque. Un email avec votre lien de téléchargement vous a été envoyé.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @foreach ($order->book->availableFormats() as $fmt)
                    <a href="{{ route('library.download', [$order, $fmt]) }}" class="btn-solid">Télécharger ({{ strtoupper($fmt) }})</a>
                @endforeach
                <a href="{{ route('dashboard') }}" class="btn-outline">Mes achats</a>
            </div>
        @elseif ($order->isPending())
            <div class="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-brand-200 border-t-brand-600" aria-hidden="true"></div>
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Paiement en cours de confirmation</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                Validez l'opération sur votre téléphone si ce n'est pas déjà fait. Cette page se met à jour automatiquement.
            </p>
            <meta http-equiv="refresh" content="8">
            <a href="{{ route('dashboard') }}" class="btn-outline mt-8">Aller à « Mes achats »</a>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-700">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="mt-5 text-3xl font-bold text-ink dark:text-white">Le paiement n'a pas abouti</h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">Aucun montant n'a été débité. Vous pouvez réessayer.</p>
            <a href="{{ route('checkout.show', $order->book) }}" class="btn-primary mt-8">Réessayer le paiement</a>
        @endif
        <p class="mt-10 text-xs text-slate-500">Référence de commande : {{ $order->reference }}</p>
    </div>
</x-app-layout>
