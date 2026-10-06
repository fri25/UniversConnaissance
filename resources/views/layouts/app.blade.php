<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => $title, 'description' => $description])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col font-sans">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-ink">
        Aller au contenu
    </a>

    @include('layouts.navigation')

    <main id="contenu" class="flex-1">
        @isset($header)
            <div class="border-b border-slate-200 bg-slate-50 dark:border-ink-600 dark:bg-ink-800">
                <div class="container-page py-8">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <x-flash />

        {{ $slot }}
    </main>

    @include('layouts.footer')

    @stack('scripts')
</body>
</html>
