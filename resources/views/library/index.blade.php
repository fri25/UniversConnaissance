<x-app-layout title="Mes achats">
    <x-slot name="header">
        <h1 class="text-3xl font-bold text-ink dark:text-white">Mes achats</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-300">Votre bibliothèque personnelle : vos e-books sont téléchargeables à tout moment.</p>
    </x-slot>

    <div class="container-page py-8">
        @if ($purchases->isEmpty())
            <div class="card flex flex-col items-center p-12 text-center">
                <svg class="h-14 w-14 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                <p class="mt-4 font-serif text-xl font-bold text-ink dark:text-white">Votre bibliothèque est vide</p>
                <p class="mt-2 text-slate-600 dark:text-slate-400">Vos e-books apparaîtront ici dès votre premier achat.</p>
                <a href="{{ route('books.index') }}" class="btn-primary mt-6">Découvrir le catalogue</a>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($purchases as $order)
                    @php($download = $order->download)
                    <article class="card flex gap-4 p-4" data-reveal>
                        <a href="{{ route('books.show', $order->book) }}" class="shrink-0">
                            <img src="{{ $order->book->coverUrl() }}" alt="Couverture de {{ $order->book->title }}" class="aspect-cover w-24 rounded-lg object-cover shadow-cover">
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <h2 class="font-serif text-base font-bold leading-snug text-ink dark:text-white">
                                <a href="{{ route('books.show', $order->book) }}" class="hover:text-brand-700">{{ $order->book->title }}</a>
                            </h2>
                            <p class="truncate text-sm text-slate-600 dark:text-slate-400">{{ $order->book->authorNames() }}</p>
                            <p class="mt-1 text-xs text-slate-500">Acheté le {{ $order->paid_at?->translatedFormat('d M Y') }} · {{ fcfa($order->amount) }}</p>
                            @if ($download)
                                <p class="mt-1 text-xs text-slate-500">Téléchargements restants : <strong>{{ $download->remaining() }}</strong>/{{ $download->max_downloads }}</p>
                            @endif
                            <div class="mt-auto flex flex-wrap gap-2 pt-3">
                                @if ($download?->isExhausted())
                                    <a href="{{ route('pages.help') }}" class="btn-outline !px-3 !py-1.5 text-xs">Quota atteint — contacter le support</a>
                                @else
                                    @foreach ($order->book->availableFormats() as $fmt)
                                        <a href="{{ route('library.download', [$order, $fmt]) }}" class="btn-solid !px-3 !py-1.5 text-xs">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            {{ strtoupper($fmt) }}
                                        </a>
                                    @endforeach
                                @endif
                                <a href="{{ route('books.show', $order->book) }}#avis" class="btn-ghost !px-3 !py-1.5 text-xs">Donner mon avis</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($otherOrders->isNotEmpty())
            <section class="mt-14" aria-labelledby="titre-historique">
                <h2 id="titre-historique" class="text-xl font-bold text-ink dark:text-white">Autres commandes</h2>
                <div class="card mt-4 overflow-x-auto">
                    <table class="admin-table">
                        <thead><tr><th>Référence</th><th>E-book</th><th>Montant</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                            @foreach ($otherOrders as $order)
                                <tr>
                                    <td class="font-mono text-xs">{{ $order->reference }}</td>
                                    <td>{{ $order->book->title }}</td>
                                    <td>{{ fcfa($order->amount) }}</td>
                                    <td><x-order-status :order="$order" /></td>
                                    <td>{{ $order->created_at->translatedFormat('d M Y') }}</td>
                                    <td>
                                        @if (! in_array($order->book_id, auth()->user()->ownedBookIds(), true) && in_array($order->status, ['pending', 'failed']))
                                            <a href="{{ route('checkout.show', $order->book) }}" class="link text-xs">Payer</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
