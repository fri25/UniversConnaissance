<x-admin-layout title="E-books">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">E-books</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.books.import') }}" class="btn-outline">Import CSV</a>
            <a href="{{ route('admin.books.create') }}" class="btn-solid">+ Nouvel e-book</a>
        </div>
    </x-slot>

    <form class="mb-4 flex flex-wrap gap-2">
        <label for="q" class="sr-only">Rechercher</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un titre…" class="input max-w-xs">
        <label for="status" class="sr-only">Statut</label>
        <select id="status" name="status" class="input !w-auto">
            <option value="">Tous</option>
            <option value="active" @selected(request('status') === 'active')>Actifs</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option>
        </select>
        <button class="btn-outline">Filtrer</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="admin-table">
            <thead class="bg-slate-50 dark:bg-ink-700"><tr><th>E-book</th><th>Format</th><th>Prix</th><th>Ventes</th><th>Statut</th><th class="!text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-ink-700">
                @forelse ($books as $book)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ $book->coverUrl() }}" alt="" class="h-12 w-12 rounded object-cover">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-ink dark:text-white">{{ $book->title }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $book->authorNames() }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $book->formatLabel() }}</td>
                        <td class="whitespace-nowrap tabular-nums">
                            {{ fcfa($book->price) }}
                            @if ($book->isOnPromo())<span class="block text-xs text-slate-500 line-through">{{ fcfa($book->old_price) }}</span>@endif
                        </td>
                        <td class="tabular-nums">{{ $book->sales_count }}</td>
                        <td>
                            @if ($book->is_active)
                                <span class="badge bg-emerald-100 text-emerald-800">Actif</span>
                            @else
                                <span class="badge bg-slate-200 text-slate-700">Inactif</span>
                            @endif
                            @if ($book->is_featured)<span class="badge-brand">À la une</span>@endif
                            @if (config('payment.default') === 'chariow' && ! $book->chariow_product_id)
                                <span class="badge bg-amber-100 text-amber-900" title="Achat indisponible tant que l'ID du produit Chariow n'est pas renseigné">Non lié à Chariow</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('books.show', $book) }}" class="link text-xs">Voir</a>
                            <a href="{{ route('admin.books.edit', $book) }}" class="link ml-3 text-xs">Modifier</a>
                            <form method="POST" action="{{ route('admin.books.destroy', $book) }}" class="ml-3 inline" onsubmit="return confirm('Supprimer cet e-book ?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-medium text-red-700 hover:underline dark:text-red-300">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">Aucun e-book.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $books->links() }}</div>
</x-admin-layout>
