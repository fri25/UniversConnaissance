<x-app-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-50 via-white to-brand-100 dark:from-ink dark:via-ink-800 dark:to-brand-950" aria-hidden="true"></div>
        <div class="absolute -right-32 -top-32 -z-10 h-[28rem] w-[28rem] rounded-full bg-brand-300/40 blur-3xl dark:bg-brand-700/30" aria-hidden="true"></div>
        <div class="absolute -bottom-40 -left-20 -z-10 h-80 w-80 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/40" aria-hidden="true"></div>

        <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
            <div class="animate-fade-up">
                <span class="badge-brand">
                    <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    {{ config('store.tagline') }}
                </span>
                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl lg:text-6xl dark:text-white">
                    Explorez l'univers de la <span class="text-gradient">connaissance</span>
                </h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600 dark:text-slate-300">
                    Des centaines de pages à dévorer : littérature, business, sciences, développement personnel…
                    Des <strong class="font-semibold text-ink dark:text-white">e-books en téléchargement instantané</strong>,
                    payés en toute sécurité par Mobile Money.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#catalogue" class="btn-primary px-7 py-3 text-base">
                        Parcourir les e-books
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                    @guest
                        <a href="{{ route('register') }}" class="btn-outline px-7 py-3 text-base">Créer un compte</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-outline px-7 py-3 text-base">Ma bibliothèque</a>
                    @endguest
                </div>
                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 text-center sm:text-left">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Formats</dt>
                        <dd class="mt-1 font-serif text-xl font-bold text-ink dark:text-white">PDF · EPUB</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Livraison</dt>
                        <dd class="mt-1 font-serif text-xl font-bold text-ink dark:text-white">Immédiate</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Paiement</dt>
                        <dd class="mt-1 font-serif text-xl font-bold text-ink dark:text-white">Mobile Money</dd>
                    </div>
                </dl>
            </div>

            {{-- Pile de couvertures --}}
            <div class="relative mx-auto hidden h-[26rem] w-full max-w-md sm:block" aria-hidden="true">
                @foreach ($newReleases->take(3) as $i => $book)
                    <img src="{{ $book->coverUrl() }}" alt=""
                         class="absolute w-52 rounded-xl shadow-cover transition duration-500 hover:z-10 hover:-translate-y-3
                                {{ ['left-0 top-10 -rotate-6', 'left-1/2 top-0 z-[1] -translate-x-1/2 rotate-0', 'right-0 top-12 rotate-6'][$i] }}">
                @endforeach
            </div>
        </div>
    </section>

    {{-- Catégories --}}
    <section class="container-page mt-12" aria-labelledby="titre-categories">
        <h2 id="titre-categories" class="sr-only">Catégories</h2>
        <div class="flex gap-3 overflow-x-auto pb-2 [scrollbar-width:none]">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                   class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-500 hover:text-brand-800 dark:border-ink-600 dark:bg-ink-800 dark:text-slate-200 dark:hover:text-brand-300">
                    {{ $category->name }} <span class="text-slate-400">· {{ $category->books_count }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Nouveautés --}}
    <section class="container-page mt-14" aria-labelledby="titre-nouveautes">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 id="titre-nouveautes" class="section-title">Nouveautés</h2>
                <p class="mt-1 text-slate-600 dark:text-slate-400">Les derniers e-books arrivés dans l'univers.</p>
            </div>
            <a href="{{ route('books.index', ['sort' => 'recent']) }}" class="link shrink-0 text-sm">Tout voir →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($newReleases->take(4) as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
    </section>

    {{-- Meilleures ventes --}}
    <section class="mt-16 bg-slate-50 py-14 dark:bg-ink-800" aria-labelledby="titre-ventes">
        <div class="container-page">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 id="titre-ventes" class="section-title">Meilleures ventes</h2>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">Ce que nos lecteurs s'arrachent en ce moment.</p>
                </div>
                <a href="{{ route('books.index', ['sort' => 'popular']) }}" class="link shrink-0 text-sm">Tout voir →</a>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($bestSellers->take(4) as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- Catalogue --}}
    <section id="catalogue" class="container-page scroll-mt-20 pt-16" aria-labelledby="titre-catalogue">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="titre-catalogue" class="section-title">Le catalogue</h2>
                <p class="mt-1 text-slate-600 dark:text-slate-400">Choisissez votre prochaine lecture.</p>
            </div>
            <form action="{{ route('books.index') }}" class="flex w-full gap-2 sm:w-auto">
                <label for="q-catalogue" class="sr-only">Rechercher</label>
                <input id="q-catalogue" type="search" name="q" placeholder="Rechercher…" class="input rounded-full sm:w-64">
                <button class="btn-solid">OK</button>
            </form>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($catalogue as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
        <div class="mt-10 text-center">
            <a href="{{ route('books.index') }}" class="btn-solid px-8 py-3">Voir tout le catalogue</a>
        </div>
    </section>

    {{-- Réassurance --}}
    <section class="container-page mt-20" aria-label="Nos engagements">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Paiement sécurisé', 'Mobile Money (MTN, Moov…) ou carte bancaire via Chariow.', 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'],
                ['Téléchargement immédiat', 'Votre e-book est disponible dès la confirmation du paiement.', 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
                ['Lisible partout', 'Téléphone, tablette, liseuse ou ordinateur.', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                ['Support réactif', 'Une question ? Notre équipe vous répond 6 jours sur 7.', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
            ] as [$title, $text, $icon])
                <div class="card flex gap-4 p-5" data-reveal>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-800 dark:bg-brand-900/60 dark:text-brand-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <div>
                        <h3 class="font-sans font-semibold text-ink dark:text-white">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
