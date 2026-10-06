<header x-data="{ open: false }" @keydown.escape.window="open = false"
        class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-md dark:border-ink-600/80 dark:bg-ink/85">
    <div class="container-page flex h-16 items-center gap-4">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ config('store.name') }} — accueil">
            <x-application-logo />
        </a>

        <nav class="ml-4 hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
            <a href="{{ route('books.index') }}"
               @class(['rounded-full px-3 py-2 text-sm font-medium', 'text-brand-700 dark:text-brand-300' => request()->routeIs('books.*', 'categories.*'), 'text-slate-700 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300' => ! request()->routeIs('books.*', 'categories.*')])>
                Boutique
            </a>
            <a href="{{ route('dashboard') }}"
               @class(['rounded-full px-3 py-2 text-sm font-medium', 'text-brand-700 dark:text-brand-300' => request()->routeIs('dashboard'), 'text-slate-700 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300' => ! request()->routeIs('dashboard')])>
                Mes achats
            </a>
        </nav>

        <form action="{{ route('books.index') }}" method="GET" role="search" class="relative ml-auto hidden max-w-sm flex-1 md:block">
            <label for="search-desktop" class="sr-only">Rechercher un e-book</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
            <input id="search-desktop" type="search" name="q" value="{{ request('q') }}" placeholder="Titre, auteur, ISBN…"
                   class="input rounded-full py-2 pl-9 text-sm">
        </form>

        <div class="ml-auto flex items-center gap-1 md:ml-0">
            <button type="button" @click="$store.theme.toggle()" class="btn-ghost h-10 w-10 !p-0"
                    :aria-label="$store.theme.dark ? 'Passer en mode clair' : 'Passer en mode sombre'">
                <svg x-show="!$store.theme.dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 15.002A9.72 9.72 0 0 1 18 15.75 9.75 9.75 0 0 1 8.25 6c0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25 9.75 9.75 0 0 0 12.75 21a9.753 9.753 0 0 0 9-5.998Z"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
            </button>

            @auth
                <div class="hidden lg:block">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="btn-ghost">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-900">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                                <span class="max-w-[8rem] truncate">{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('dashboard')">Mes achats</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">Mon profil</x-dropdown-link>
                            @if (Auth::user()->is_admin)
                                <x-dropdown-link :href="route('admin.dashboard')">Administration</x-dropdown-link>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-ghost hidden lg:inline-flex">Connexion</a>
                <a href="{{ route('register') }}" class="btn-primary hidden lg:inline-flex">Rejoindre</a>
            @endauth

            <button type="button" @click="open = !open" class="btn-ghost h-10 w-10 !p-0 lg:hidden"
                    :aria-expanded="open.toString()" aria-controls="menu-mobile" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Menu mobile --}}
    <div id="menu-mobile" x-show="open" x-cloak x-transition.origin.top
         class="border-t border-slate-200 bg-white pb-6 lg:hidden dark:border-ink-600 dark:bg-ink">
        <div class="container-page space-y-4 pt-4">
            <form action="{{ route('books.index') }}" method="GET" role="search" class="md:hidden">
                <label for="search-mobile" class="sr-only">Rechercher un e-book</label>
                <input id="search-mobile" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un e-book…" class="input rounded-full">
            </form>
            <nav class="grid gap-1 text-base font-medium" aria-label="Navigation mobile">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Accueil</a>
                <a href="{{ route('books.index') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Boutique</a>
                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Mes achats</a>
                <a href="{{ route('pages.help') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Aide</a>
                @auth
                    <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Mon profil</a>
                    @if (Auth::user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-slate-100 dark:hover:bg-ink-700">Administration</a>
                    @endif
                @endauth
            </nav>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-outline w-full">Déconnexion</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" class="btn-outline">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary">Rejoindre</a>
                </div>
            @endauth
        </div>
    </div>
</header>
