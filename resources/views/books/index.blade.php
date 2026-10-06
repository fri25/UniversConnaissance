@php
    $pageTitle = $currentCategory?->name ?? (! empty($filters['q']) ? 'Résultats pour « '.$filters['q'].' »' : 'Tous les e-books');
    $sorts = ['recent' => 'Plus récents', 'popular' => 'Meilleures ventes', 'rating' => 'Mieux notés', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'title' => 'Titre (A-Z)'];
    $action = $currentCategory ? route('categories.show', $currentCategory) : route('books.index');
@endphp
<x-app-layout :title="$pageTitle" :description="$currentCategory?->description">
    <x-slot name="header">
        <nav class="text-sm text-slate-500 dark:text-slate-400" aria-label="Fil d'Ariane">
            <a href="{{ route('home') }}" class="hover:text-brand-700">Accueil</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('books.index') }}" class="hover:text-brand-700">Boutique</a>
            @if ($currentCategory)
                <span aria-hidden="true">/</span> <span class="text-slate-700 dark:text-slate-200">{{ $currentCategory->name }}</span>
            @endif
        </nav>
        <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl dark:text-white">{{ $pageTitle }}</h1>
        @if ($currentCategory?->description)
            <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-300">{{ $currentCategory->description }}</p>
        @endif
    </x-slot>

    <div class="container-page py-8" x-data="{ filtersOpen: false }">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ $books->total() }} e-book{{ $books->total() > 1 ? 's' : '' }}
            </p>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-outline !py-2 lg:hidden" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()" aria-controls="filtres">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4"/></svg>
                    Filtres
                </button>
                <form action="{{ $action }}" method="GET" class="flex items-center gap-2">
                    @foreach (collect($filters)->except(['sort', 'category']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="sort" class="hidden text-sm text-slate-600 sm:block dark:text-slate-400">Trier par</label>
                    <select id="sort" name="sort" class="input !w-auto rounded-full py-2 text-sm" onchange="this.form.submit()">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'recent') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn-solid !py-2">OK</button></noscript>
                </form>
            </div>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[16rem_1fr]">
            {{-- Filtres --}}
            <aside id="filtres" class="lg:block" :class="filtersOpen ? 'block' : 'hidden'" aria-label="Filtres">
                <form action="{{ $action }}" method="GET" class="card space-y-5 p-5 lg:sticky lg:top-24">
                    @isset($filters['sort'])
                        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                    @endisset

                    <div>
                        <label for="f-q" class="label">Recherche</label>
                        <input id="f-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input text-sm" placeholder="Titre, auteur…">
                    </div>

                    @unless ($currentCategory)
                        <div>
                            <label for="f-category" class="label">Catégorie</label>
                            <select id="f-category" name="category" class="input text-sm">
                                <option value="">Toutes</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->slug }}" @selected(($filters['category'] ?? null) === $category->slug)>
                                        {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <div>
                        <label for="f-author" class="label">Auteur</label>
                        <select id="f-author" name="author" class="input text-sm">
                            <option value="">Tous</option>
                            @foreach ($authors as $author)
                                <option value="{{ $author->slug }}" @selected(($filters['author'] ?? null) === $author->slug)>{{ $author->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="f-language" class="label">Langue</label>
                        <select id="f-language" name="language" class="input text-sm">
                            <option value="">Toutes</option>
                            @foreach (\App\Models\Book::LANGUAGES as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['language'] ?? null) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset>
                        <legend class="label">Format</legend>
                        <div class="flex gap-2">
                            @foreach (['' => 'Tous', 'pdf' => 'PDF', 'epub' => 'EPUB'] as $value => $label)
                                <label class="flex-1">
                                    <input type="radio" name="format" value="{{ $value }}" class="peer sr-only" @checked(($filters['format'] ?? '') === $value)>
                                    <span class="block cursor-pointer rounded-lg border border-slate-300 py-1.5 text-center text-sm peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-semibold peer-checked:text-brand-900 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 dark:border-ink-600 dark:peer-checked:bg-brand-950 dark:peer-checked:text-brand-100">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="label">Prix (FCFA)</legend>
                        <div class="flex items-center gap-2">
                            <label for="f-min" class="sr-only">Prix minimum</label>
                            <input id="f-min" type="number" min="0" step="500" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min" class="input text-sm">
                            <span aria-hidden="true">–</span>
                            <label for="f-max" class="sr-only">Prix maximum</label>
                            <input id="f-max" type="number" min="0" step="500" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max" class="input text-sm">
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="promo" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-500" @checked(! empty($filters['promo']))>
                        En promotion uniquement
                    </label>

                    <div class="flex gap-2">
                        <button class="btn-solid flex-1">Filtrer</button>
                        <a href="{{ $action }}" class="btn-ghost">Réinitialiser</a>
                    </div>
                </form>
            </aside>

            {{-- Résultats --}}
            <div>
                @if ($books->isEmpty())
                    <div class="card flex flex-col items-center p-12 text-center">
                        <p class="font-serif text-xl font-bold text-ink dark:text-white">Aucun e-book ne correspond à votre recherche.</p>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">Essayez d'élargir vos filtres.</p>
                        <a href="{{ route('books.index') }}" class="btn-primary mt-6">Voir tout le catalogue</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach ($books as $book)
                            <x-book-card :book="$book" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $books->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
