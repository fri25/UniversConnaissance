<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => $title ?? null])
    @include('layouts.partials.meta-pixel')
</head>
<body class="font-sans">
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-brand-50 via-white to-brand-100 dark:from-ink dark:via-ink-800 dark:to-brand-950" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl" aria-hidden="true"></div>

        <a href="{{ route('home') }}" aria-label="{{ config('store.name') }} — accueil">
            <x-application-logo class="scale-110" />
        </a>
        <p class="mt-2 font-serif italic text-slate-600 dark:text-slate-300">{{ config('store.tagline') }}</p>

        <div class="card mt-8 w-full max-w-md p-6 shadow-xl shadow-brand-900/5 sm:p-8">
            {{ $slot }}
        </div>

        <a href="{{ route('books.index') }}" class="link mt-6 text-sm">← Retour à la boutique</a>
    </div>
    <x-cookie-consent />
</body>
</html>
