@php
    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Book',
        'name' => $book->title,
        'url' => route('books.show', $book),
        'image' => $book->coverUrl(),
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $book->summary), 300),
        'author' => $book->authors->map(fn ($a) => ['@type' => 'Person', 'name' => $a->name])->all(),
        'publisher' => $book->publisher ? ['@type' => 'Organization', 'name' => $book->publisher] : null,
        'datePublished' => $book->published_year ? (string) $book->published_year : null,
        'inLanguage' => $book->language,
        'isbn' => $book->isbn,
        'numberOfPages' => $book->pages,
        'bookFormat' => 'https://schema.org/EBook',
        'genre' => $book->categories->pluck('name')->all(),
        'aggregateRating' => $book->rating_count ? [
            '@type' => 'AggregateRating',
            'ratingValue' => round($book->rating_avg, 1),
            'reviewCount' => $book->rating_count,
            'bestRating' => 5,
        ] : null,
        'offers' => [
            '@type' => 'Offer',
            'price' => $book->price,
            'priceCurrency' => 'XOF',
            'availability' => 'https://schema.org/InStock',
            'url' => route('books.show', $book),
        ],
    ], fn ($v) => $v !== null && $v !== []);
    $canReview = auth()->check() && ! $userReview && auth()->user()->can('create', [\App\Models\Review::class, $book]);
@endphp
<x-app-layout :title="$book->title.' — '.$book->authorNames()" :description="\Illuminate\Support\Str::limit(strip_tags((string) $book->summary), 155)">
    @push('head')
        <meta property="og:type" content="book">
        <meta property="og:image" content="{{ $book->coverUrl() }}">
        <link rel="canonical" href="{{ route('books.show', $book) }}">
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    <div class="container-page pb-28 pt-8 lg:pb-8">
        <nav class="text-sm text-slate-500 dark:text-slate-400" aria-label="Fil d'Ariane">
            <a href="{{ route('home') }}" class="hover:text-brand-700">Accueil</a> /
            <a href="{{ route('books.index') }}" class="hover:text-brand-700">Boutique</a>
            @if ($book->categories->isNotEmpty())
                / <a href="{{ route('categories.show', $book->categories->first()) }}" class="hover:text-brand-700">{{ $book->categories->first()->name }}</a>
            @endif
        </nav>

        @unless ($book->is_active)
            <p class="mt-4 rounded-lg bg-amber-100 px-4 py-2 text-sm text-amber-900">Cet e-book est désactivé : seuls les administrateurs le voient.</p>
        @endunless

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,22rem)_1fr] xl:gap-16">
            {{-- Couverture --}}
            <div class="mx-auto w-full max-w-xs lg:max-w-none">
                <div class="relative lg:sticky lg:top-24">
                    <img src="{{ $book->coverUrl() }}" alt="Couverture de {{ $book->title }}" width="600" height="900"
                         class="aspect-cover w-full rounded-2xl object-cover shadow-cover">
                    @if ($book->isOnPromo())
                        <span class="badge absolute left-3 top-3 bg-rose-600 text-white shadow">Promo −{{ $book->discountPercent() }} %</span>
                    @elseif ($book->isNew())
                        <span class="badge absolute left-3 top-3 bg-brand-500 text-ink shadow">Nouveau</span>
                    @endif
                    @if ($book->sample_path)
                        <a href="{{ route('books.sample', $book) }}" class="btn-outline mt-4 w-full">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                            Lire un extrait gratuit
                        </a>
                    @endif
                </div>
            </div>

            {{-- Informations --}}
            <div class="min-w-0">
                <div class="flex flex-wrap gap-2">
                    @foreach ($book->categories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="badge-brand hover:underline">{{ $category->name }}</a>
                    @endforeach
                    @foreach ($book->availableFormats() as $fmt)
                        <span class="badge bg-ink text-white dark:bg-white dark:text-ink">{{ strtoupper($fmt) }}</span>
                    @endforeach
                </div>

                <h1 class="mt-4 text-3xl font-extrabold leading-tight text-ink sm:text-4xl dark:text-white">{{ $book->title }}</h1>
                <p class="mt-2 text-lg text-slate-600 dark:text-slate-300">
                    par
                    @foreach ($book->authors as $author)
                        <a href="{{ route('books.index', ['author' => $author->slug]) }}" class="link">{{ $author->name }}</a>@if (! $loop->last), @endif
                    @endforeach
                </p>
                <a href="#avis" class="mt-3 inline-block">
                    <x-rating-stars :value="$book->rating_avg" :count="$book->rating_count" size="h-5 w-5" />
                </a>

                {{-- Bloc achat --}}
                <div class="card mt-6 p-5 sm:p-6">
                    <x-book-price :book="$book" large />
                    @if ($book->isOnPromo())
                        <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">Vous économisez {{ fcfa($book->old_price - $book->price) }}</p>
                    @endif

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        @if ($purchase)
                            @foreach ($book->availableFormats() as $fmt)
                                <a href="{{ route('library.download', [$purchase, $fmt]) }}" class="btn-solid flex-1 py-3">
                                    Télécharger ({{ strtoupper($fmt) }})
                                </a>
                            @endforeach
                        @elseif ($book->isPurchasable())
                            <a href="{{ route('books.buy', $book) }}" class="btn-primary flex-1 py-3 text-base">Acheter maintenant</a>
                        @else
                            <span class="btn-outline flex-1 cursor-not-allowed py-3 text-base opacity-70" aria-disabled="true">Bientôt disponible</span>
                        @endif
                    </div>
                    @if ($purchase)
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">✓ Vous possédez cet e-book depuis le {{ $purchase->paid_at->translatedFormat('d F Y') }}.</p>
                    @endif

                    <ul class="mt-6 grid gap-3 border-t border-slate-200 pt-5 text-sm sm:grid-cols-2 dark:border-ink-600">
                        @foreach ([
                            ['Paiement Mobile Money sécurisé', 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
                            ['Téléchargement immédiat après paiement', 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
                            ['Lisible sur téléphone, tablette, liseuse et ordinateur', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                            ['Support actif 6j/7', 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z'],
                        ] as [$label, $icon])
                            <li class="flex items-start gap-2.5 text-slate-700 dark:text-slate-300">
                                <svg class="h-5 w-5 shrink-0 text-brand-700 dark:text-brand-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Caractéristiques --}}
                <section class="mt-8" aria-labelledby="titre-details">
                    <h2 id="titre-details" class="text-xl font-bold text-ink dark:text-white">Caractéristiques</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                        @foreach (array_filter([
                            'Auteur(s)' => $book->authorNames(),
                            'Éditeur' => $book->publisher,
                            'Année' => $book->published_year,
                            'Langue' => $book->languageLabel(),
                            'Pages' => $book->pages,
                            'Format' => $book->formatLabel(),
                            'Taille du fichier' => $book->humanFileSize(),
                            'ISBN' => $book->isbn,
                            'Catégorie(s)' => $book->categories->pluck('name')->join(', '),
                        ]) as $label => $value)
                            <div>
                                <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="font-medium text-ink dark:text-slate-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                {{-- Résumé / auteur / sommaire --}}
                <div class="mt-10" x-data="{ tab: 'resume' }">
                    <div class="flex gap-1 overflow-x-auto border-b border-slate-200 dark:border-ink-600" role="tablist">
                        @foreach (['resume' => 'Résumé', 'auteur' => 'À propos de l\'auteur', 'sommaire' => 'Table des matières'] as $key => $label)
                            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="(tab === '{{ $key }}').toString()"
                                    :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-800 dark:text-brand-200' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                                    class="-mb-px whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-semibold transition">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <div class="prose-content pt-5 text-slate-700 dark:text-slate-300">
                        <div x-show="tab === 'resume'" role="tabpanel">
                            {!! nl2br(e($book->summary ?: 'Résumé à venir.')) !!}
                        </div>
                        <div x-show="tab === 'auteur'" x-cloak role="tabpanel" class="space-y-6">
                            @foreach ($book->authors as $author)
                                <div>
                                    <h3 class="font-serif text-lg font-bold text-ink dark:text-white">{{ $author->name }}</h3>
                                    <p class="mt-1">{{ $author->bio ?: 'Biographie à venir.' }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div x-show="tab === 'sommaire'" x-cloak role="tabpanel">
                            @if ($book->table_of_contents)
                                <ol class="list-inside list-decimal space-y-1.5">
                                    @foreach (preg_split('/\r?\n/', trim($book->table_of_contents)) as $chapter)
                                        @if (trim($chapter) !== '')
                                            <li>{{ trim($chapter) }}</li>
                                        @endif
                                    @endforeach
                                </ol>
                            @else
                                <p>Table des matières non communiquée.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Avis --}}
        <section id="avis" class="mt-16 scroll-mt-24" aria-labelledby="titre-avis">
            <h2 id="titre-avis" class="section-title">Avis des lecteurs</h2>
            <div class="mt-6 grid gap-8 lg:grid-cols-[18rem_1fr]">
                <div class="card h-fit p-5">
                    <p class="font-serif text-4xl font-bold text-ink dark:text-white">{{ $book->rating_count ? number_format($book->rating_avg, 1, ',', '') : '–' }}<span class="text-lg text-slate-400">/5</span></p>
                    <x-rating-stars class="mt-1" :value="$book->rating_avg" size="h-5 w-5" />
                    <p class="mt-1 text-sm text-slate-500">{{ $book->rating_count }} avis vérifié{{ $book->rating_count > 1 ? 's' : '' }}</p>
                    <div class="mt-4 space-y-1.5">
                        @for ($star = 5; $star >= 1; $star--)
                            @php($n = $ratingBreakdown[$star] ?? 0)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-3 text-slate-600 dark:text-slate-400">{{ $star }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-ink-600">
                                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $book->rating_count ? round($n * 100 / $book->rating_count) : 0 }}%"></div>
                                </div>
                                <span class="w-6 text-right text-slate-500">{{ $n }}</span>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="space-y-6">
                    @if ($canReview)
                        <form method="POST" action="{{ route('reviews.store', $book) }}" class="card p-5" x-data="{ rating: {{ (int) old('rating', 5) }} }">
                            @csrf
                            <h3 class="font-sans font-semibold text-ink dark:text-white">Donnez votre avis</h3>
                            @include('books.partials.review-fields')
                            <button class="btn-solid mt-4">Publier mon avis</button>
                        </form>
                    @elseif ($userReview)
                        <details class="card p-5" @if ($errors->any()) open @endif>
                            <summary class="cursor-pointer font-semibold text-ink dark:text-white">Modifier mon avis</summary>
                            <form method="POST" action="{{ route('reviews.update', $userReview) }}" x-data="{ rating: {{ (int) old('rating', $userReview->rating) }} }">
                                @csrf @method('PUT')
                                @include('books.partials.review-fields', ['comment' => $userReview->comment])
                                <div class="mt-4 flex gap-2">
                                    <button class="btn-solid">Enregistrer</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('reviews.destroy', $userReview) }}" class="mt-2" onsubmit="return confirm('Supprimer votre avis ?')">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-700 hover:underline dark:text-red-300">Supprimer mon avis</button>
                            </form>
                        </details>
                    @elseif (! $purchase)
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-ink-800 dark:text-slate-400">
                            Seuls les lecteurs ayant acheté cet e-book peuvent laisser un avis.
                        </p>
                    @endif

                    @forelse ($reviews as $review)
                        <article class="border-b border-slate-200 pb-5 dark:border-ink-600">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-900">{{ mb_strtoupper(mb_substr($review->user->name, 0, 1)) }}</span>
                                <span class="font-semibold text-ink dark:text-white">{{ $review->user->name }}</span>
                                <x-rating-stars :value="$review->rating" />
                                <span class="badge bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">Achat vérifié</span>
                                <time class="text-xs text-slate-500" datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->translatedFormat('d M Y') }}</time>
                            </div>
                            @if ($review->comment)
                                <p class="mt-2 text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-slate-600 dark:text-slate-400">Aucun avis pour le moment.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Suggestions --}}
        @if ($sameAuthor->isNotEmpty())
            <section class="mt-16" aria-labelledby="titre-meme-auteur">
                <h2 id="titre-meme-auteur" class="section-title">Du même auteur</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($sameAuthor as $related)
                        <x-book-card :book="$related" />
                    @endforeach
                </div>
            </section>
        @endif
        @if ($sameCategory->isNotEmpty())
            <section class="mt-16" aria-labelledby="titre-meme-categorie">
                <h2 id="titre-meme-categorie" class="section-title">Dans la même catégorie</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($sameCategory as $related)
                        <x-book-card :book="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- CTA collant (mobile) --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:hidden dark:border-ink-600 dark:bg-ink/95">
        <div class="mx-auto flex max-w-md items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ink dark:text-white">{{ $book->title }}</p>
                <x-book-price :book="$book" />
            </div>
            @if ($purchase)
                <a href="{{ route('dashboard') }}" class="btn-solid">Télécharger</a>
            @elseif ($book->isPurchasable())
                <a href="{{ route('books.buy', $book) }}" class="btn-primary">Acheter maintenant</a>
            @else
                <span class="btn-outline opacity-70" aria-disabled="true">Bientôt disponible</span>
            @endif
        </div>
    </div>
</x-app-layout>
