@props(['title', 'intro' => null])
<x-app-layout :title="$title">
    <x-slot name="header">
        <h1 class="text-3xl font-bold text-ink sm:text-4xl dark:text-white">{{ $title }}</h1>
        @if ($intro)
            <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-300">{{ $intro }}</p>
        @endif
    </x-slot>
    <div class="container-page max-w-3xl py-10">
        <div class="space-y-8 leading-relaxed text-slate-700 dark:text-slate-300 [&_h2]:font-serif [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-ink dark:[&_h2]:text-white [&_p]:mt-2 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
            {{ $slot }}
        </div>
        <p class="mt-12 text-sm text-slate-500">Dernière mise à jour : {{ now()->translatedFormat('F Y') }}</p>
    </div>
</x-app-layout>
