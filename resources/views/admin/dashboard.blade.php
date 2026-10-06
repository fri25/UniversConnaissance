<x-admin-layout title="Tableau de bord">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Tableau de bord</h1>
        <a href="{{ route('admin.books.create') }}" class="btn-solid">+ Nouvel e-book</a>
    </x-slot>

    {{-- Indicateurs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Chiffre d\'affaires total', fcfa($stats['revenue']), null],
            ['CA sur 30 jours', fcfa($stats['revenue_30']), null],
            ['Ventes', number_format($stats['sales'], 0, ',', ' '), $stats['pending'].' commande(s) en attente'],
            ['Clients', number_format($stats['customers'], 0, ',', ' '), $stats['books'].' e-books actifs'],
        ] as [$label, $value, $sub])
            <div class="card p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <p class="mt-1 font-serif text-2xl font-bold text-ink dark:text-white">{{ $value }}</p>
                @if ($sub)
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $sub }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- CA quotidien, 30 derniers jours (série unique : pas de légende) --}}
    @php($max = max(1, $chart->max('amount')))
    <section class="card mt-6 p-5" aria-labelledby="titre-ca">
        <div class="flex items-baseline justify-between gap-4">
            <h2 id="titre-ca" class="font-sans text-base font-semibold text-ink dark:text-white">Chiffre d'affaires quotidien — 30 derniers jours</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">max {{ fcfa($max) }}</span>
        </div>
        <div class="relative mt-5 h-48" x-data="{ tip: null }">
            {{-- Grille discrète --}}
            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                <div class="border-t border-dashed border-slate-200 dark:border-ink-600"></div>
                <div class="border-t border-dashed border-slate-200 dark:border-ink-600"></div>
                <div class="border-t border-slate-300 dark:border-ink-600"></div>
            </div>
            <div class="relative flex h-full items-end gap-[2px]" role="img" aria-label="Histogramme du chiffre d'affaires quotidien ; le détail est disponible dans le tableau ci-dessous.">
                @foreach ($chart as $point)
                    @php($label = $point['date']->translatedFormat('D d M').' : '.fcfa($point['amount']))
                    <div class="group flex h-full flex-1 cursor-default items-end"
                         @mouseenter="tip = @js($label)" @mouseleave="tip = null">
                        <div class="w-full rounded-t-[4px] bg-brand-600 transition group-hover:bg-brand-800 dark:bg-brand-400 dark:group-hover:bg-brand-200"
                             style="height: {{ $point['amount'] ? max(2, round($point['amount'] * 100 / $max, 1)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div x-show="tip" x-cloak x-text="tip"
                 class="pointer-events-none absolute -top-3 left-1/2 -translate-x-1/2 -translate-y-full rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white shadow-lg dark:bg-white dark:text-ink"></div>
        </div>
        <div class="mt-2 flex justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>{{ $chart->first()['date']->translatedFormat('d M') }}</span>
            <span>{{ $chart->last()['date']->translatedFormat('d M') }}</span>
        </div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer text-slate-600 dark:text-slate-400">Voir les données en tableau</summary>
            <table class="admin-table mt-2">
                <thead><tr><th>Date</th><th class="!text-right">Chiffre d'affaires</th></tr></thead>
                <tbody>
                    @foreach ($chart->reverse() as $point)
                        <tr><td>{{ $point['date']->translatedFormat('d/m/Y') }}</td><td class="text-right tabular-nums">{{ fcfa($point['amount']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="font-sans text-base font-semibold text-ink dark:text-white">Top e-books</h2>
            <ol class="mt-4 space-y-3">
                @forelse ($topBooks as $book)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-5 text-slate-400">{{ $loop->iteration }}</span>
                        <a href="{{ route('admin.books.edit', $book) }}" class="flex-1 truncate font-medium text-ink hover:text-brand-700 dark:text-white">{{ $book->title }}</a>
                        <span class="text-slate-500 dark:text-slate-400">{{ $book->sales_count }} vente(s)</span>
                        <span class="w-28 text-right font-semibold tabular-nums">{{ fcfa((int) $book->revenue) }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucune vente pour l'instant.</li>
                @endforelse
            </ol>
        </section>
        <section class="card p-5">
            <h2 class="font-sans text-base font-semibold text-ink dark:text-white">Top catégories</h2>
            <ol class="mt-4 space-y-3">
                @forelse ($topCategories as $cat)
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-5 text-slate-400">{{ $loop->iteration }}</span>
                        <span class="flex-1 truncate font-medium text-ink dark:text-white">{{ $cat->name }}</span>
                        <span class="text-slate-500 dark:text-slate-400">{{ $cat->sales_count }} vente(s)</span>
                        <span class="w-28 text-right font-semibold tabular-nums">{{ fcfa((int) $cat->revenue) }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucune vente pour l'instant.</li>
                @endforelse
            </ol>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between p-5">
            <h2 class="font-sans text-base font-semibold text-ink dark:text-white">Dernières commandes</h2>
            <a href="{{ route('admin.orders.index') }}" class="link text-sm">Toutes les commandes →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>Référence</th><th>Client</th><th>E-book</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                    @foreach ($latestOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="link font-mono text-xs">{{ $order->reference }}</a></td>
                            <td>{{ $order->user->name }}</td>
                            <td class="max-w-[14rem] truncate">{{ $order->book->title }}</td>
                            <td class="tabular-nums">{{ fcfa($order->amount) }}</td>
                            <td><x-order-status :order="$order" /></td>
                            <td class="whitespace-nowrap">{{ $order->created_at->translatedFormat('d M H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($stats['hidden_reviews'])
        <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ $stats['hidden_reviews'] }} avis masqué(s) — <a href="{{ route('admin.reviews.index', ['status' => 'hidden']) }}" class="link">modération</a>.</p>
    @endif
</x-admin-layout>
