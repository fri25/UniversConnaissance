<footer class="mt-20 border-t border-slate-200 bg-slate-50 dark:border-ink-600 dark:bg-ink-800">
    <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <x-application-logo />
            <p class="mt-3 font-serif text-lg italic text-slate-600 dark:text-slate-300">{{ config('store.tagline') }}</p>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                E-books PDF et EPUB en téléchargement instantané, lisibles sur tous vos appareils.
            </p>
        </div>

        <div>
            <h2 class="font-sans text-sm font-semibold uppercase tracking-wider text-ink dark:text-white">Navigation</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="text-slate-600 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300" href="{{ route('home') }}">Accueil</a></li>
                <li><a class="text-slate-600 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300" href="{{ route('books.index') }}">Boutique</a></li>
                <li><a class="text-slate-600 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300" href="{{ route('books.index', ['promo' => 1]) }}">Promotions</a></li>
                <li><a class="text-slate-600 hover:text-brand-700 dark:text-slate-300 dark:hover:text-brand-300" href="{{ route('dashboard') }}">Mes achats</a></li>
            </ul>
        </div>

        <div>
            <h2 class="font-sans text-sm font-semibold uppercase tracking-wider text-ink dark:text-white">Support</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <li><a class="hover:text-brand-700 dark:hover:text-brand-300" href="{{ route('pages.help') }}">Centre d'aide</a></li>
                <li><a class="hover:text-brand-700 dark:hover:text-brand-300" href="tel:{{ preg_replace('/\s+/', '', config('store.support.phone')) }}">{{ config('store.support.phone') }}</a></li>
                <li><a class="hover:text-brand-700 dark:hover:text-brand-300" href="mailto:{{ config('store.support.email') }}">{{ config('store.support.email') }}</a></li>
                <li class="text-xs text-slate-500 dark:text-slate-400">{{ config('store.support.hours') }}</li>
            </ul>
        </div>

        <div>
            <h2 class="font-sans text-sm font-semibold uppercase tracking-wider text-ink dark:text-white">Suivez-nous</h2>
            <div class="mt-4 flex gap-3">
                @foreach ([
                    'facebook' => ['Facebook', 'M14 8h3V4h-3c-2.8 0-4 1.7-4 4.2V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.3-.6.6-.6Z'],
                    'instagram' => ['Instagram', 'M12 7.2A4.8 4.8 0 1 0 12 16.8 4.8 4.8 0 0 0 12 7.2Zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2ZM17 5.8a1.1 1.1 0 1 0 0 2.2 1.1 1.1 0 0 0 0-2.2ZM16.5 2h-9A5.5 5.5 0 0 0 2 7.5v9A5.5 5.5 0 0 0 7.5 22h9a5.5 5.5 0 0 0 5.5-5.5v-9A5.5 5.5 0 0 0 16.5 2Zm3.8 14.5a3.8 3.8 0 0 1-3.8 3.8h-9a3.8 3.8 0 0 1-3.8-3.8v-9a3.8 3.8 0 0 1 3.8-3.8h9a3.8 3.8 0 0 1 3.8 3.8Z'],
                    'whatsapp' => ['WhatsApp', 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z'],
                    'tiktok' => ['TikTok', 'M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.7 5.7 0 1 0 4.9 5.7V9a7.4 7.4 0 0 0 4.3 1.4V7.3a4.3 4.3 0 0 1-3.2-1.5Z'],
                ] as $key => [$label, $path])
                    <a href="{{ config('store.socials.'.$key) }}" target="_blank" rel="noopener"
                       class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:text-brand-700 dark:bg-ink-700 dark:text-slate-300 dark:ring-ink-600 dark:hover:text-brand-300"
                       aria-label="{{ $label }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg>
                    </a>
                @endforeach
            </div>
            <p class="mt-5 text-xs text-slate-500 dark:text-slate-400">Paiement sécurisé Mobile Money &amp; carte bancaire</p>
        </div>
    </div>

    <div class="border-t border-slate-200 dark:border-ink-600">
        <div class="container-page flex flex-col gap-3 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between dark:text-slate-400">
            <p>&copy; {{ now()->year }} {{ config('store.name') }}. Tous droits réservés.</p>
            <nav class="flex flex-wrap gap-x-4 gap-y-1" aria-label="Liens légaux">
                <a class="hover:text-brand-700 dark:hover:text-brand-300" href="{{ route('pages.legal') }}">Mentions légales</a>
                <a class="hover:text-brand-700 dark:hover:text-brand-300" href="{{ route('pages.terms') }}">CGV</a>
                <a class="hover:text-brand-700 dark:hover:text-brand-300" href="{{ route('pages.privacy') }}">Confidentialité</a>
            </nav>
        </div>
    </div>
</footer>
