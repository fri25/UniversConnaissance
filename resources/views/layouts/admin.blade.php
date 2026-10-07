<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => ($title ? $title.' · ' : '').'Administration'])
    <meta name="robots" content="noindex">
</head>
<body class="bg-slate-50 font-sans dark:bg-ink" x-data="{ nav: false }">
    @php($links = [
        ['admin.dashboard', 'admin.dashboard', 'Tableau de bord', 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5'],
        ['admin.books.index', 'admin.books.*', 'E-books', 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
        ['admin.authors.index', 'admin.authors.*', 'Auteurs', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
        ['admin.categories.index', 'admin.categories.*', 'Catégories', 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z'],
        ['admin.orders.index', 'admin.orders.*', 'Commandes', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['admin.users.index', 'admin.users.*', 'Utilisateurs', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['admin.settings.edit', 'admin.settings.*', 'Réglages', 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['admin.reviews.index', 'admin.reviews.*', 'Avis', 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z'],
    ])

    {{-- Barre supérieure mobile --}}
    <div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 lg:hidden dark:border-ink-600 dark:bg-ink-800">
        <a href="{{ route('admin.dashboard') }}"><x-application-logo /></a>
        <button type="button" class="btn-ghost h-10 w-10 !p-0" @click="nav = !nav" :aria-expanded="nav.toString()" aria-label="Menu d'administration">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <div class="flex">
        <aside :class="nav ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 dark:border-ink-600 dark:bg-ink-800">
            <div class="flex h-16 items-center px-5">
                <a href="{{ route('admin.dashboard') }}"><x-application-logo /></a>
            </div>
            <p class="px-5 text-xs font-semibold uppercase tracking-wider text-slate-500">Administration</p>
            <nav class="mt-2 flex-1 space-y-1 px-3" aria-label="Administration">
                @foreach ($links as [$route, $pattern, $label, $icon])
                    <a href="{{ route($route) }}"
                       @class([
                           'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                           'bg-brand-50 text-brand-900 dark:bg-brand-950 dark:text-brand-100' => request()->routeIs($pattern),
                           'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-ink-700' => ! request()->routeIs($pattern),
                       ])>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="space-y-1 border-t border-slate-200 p-3 dark:border-ink-600">
                <button type="button" @click="$store.theme.toggle()" class="btn-ghost w-full justify-start">
                    <span x-text="$store.theme.dark ? 'Mode clair' : 'Mode sombre'"></span>
                </button>
                <a href="{{ route('home') }}" class="btn-ghost w-full justify-start">← Voir la boutique</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-ghost w-full justify-start">Déconnexion</button>
                </form>
            </div>
        </aside>
        <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-ink/50 lg:hidden" aria-hidden="true"></div>

        <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-6xl">
                @isset($header)
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">{{ $header }}</div>
                @endisset
                <div class="-mx-4 sm:-mx-6 lg:-mx-8 [&>div]:max-w-none"><x-flash /></div>
                <div class="mt-4">{{ $slot }}</div>
            </div>
        </main>
    </div>
</body>
</html>
