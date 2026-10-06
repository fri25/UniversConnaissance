<x-app-layout title="Paiement — {{ $book->title }}">
    <div class="container-page max-w-4xl py-10">
        <ol class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500" aria-label="Étapes">
            <li class="text-brand-700 dark:text-brand-300">1. Récapitulatif</li>
            <li aria-hidden="true">→</li>
            <li>2. Paiement</li>
            <li aria-hidden="true">→</li>
            <li>3. Téléchargement</li>
        </ol>
        <h1 class="mt-3 text-3xl font-bold text-ink dark:text-white">Finaliser votre achat</h1>

        <div class="mt-8 grid gap-8 md:grid-cols-[1fr_20rem]">
            <div class="card flex gap-5 p-5">
                <img src="{{ $book->coverUrl() }}" alt="" class="aspect-cover w-28 shrink-0 rounded-lg object-cover shadow-cover">
                <div>
                    <h2 class="font-serif text-xl font-bold text-ink dark:text-white">{{ $book->title }}</h2>
                    <p class="text-slate-600 dark:text-slate-400">{{ $book->authorNames() }}</p>
                    <p class="mt-2 text-sm text-slate-500">Format : {{ $book->formatLabel() }} @if ($book->humanFileSize()) · {{ $book->humanFileSize() }} @endif</p>
                    <ul class="mt-4 space-y-1 text-sm text-slate-700 dark:text-slate-300">
                        <li>✓ Téléchargement immédiat après paiement</li>
                        <li>✓ Disponible à vie dans « Mes achats » ({{ config('ebooks.max_downloads') }} téléchargements)</li>
                        <li>✓ Fichier personnalisé à votre nom</li>
                    </ul>
                </div>
            </div>

            @if ($gateway === 'chariow' && ! $book->chariow_product_id)
                <div class="card h-fit p-5 text-sm">
                    <p class="font-semibold text-ink dark:text-white">Cet e-book n'est pas encore disponible à l'achat.</p>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">Revenez très bientôt ou contactez-nous à {{ config('store.support.email') }}.</p>
                </div>
            @else
            <form method="POST" action="{{ route('checkout.store', $book) }}" class="card h-fit p-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Prix</dt><dd>{{ fcfa($book->old_price ?? $book->price) }}</dd></div>
                    @if ($book->isOnPromo())
                        <div class="flex justify-between text-rose-700 dark:text-rose-300"><dt>Réduction</dt><dd>−{{ fcfa($book->old_price - $book->price) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-ink dark:border-ink-600 dark:text-white">
                        <dt>Total</dt><dd>{{ fcfa($book->price) }}</dd>
                    </div>
                </dl>

                <fieldset class="mt-5">
                    <legend class="label">Téléphone (Mobile Money)</legend>
                    <div class="flex gap-2">
                        <label for="phone_country" class="sr-only">Pays</label>
                        <select id="phone_country" name="phone_country" class="input !w-28 shrink-0 text-sm">
                            @foreach (\App\Support\Phone::COUNTRIES as $code => [$country, $dial])
                                <option value="{{ $code }}" @selected(old('phone_country', auth()->user()->phone_country ?? 'BJ') === $code)>{{ $code }} +{{ $dial }}</option>
                            @endforeach
                        </select>
                        <label for="phone_number" class="sr-only">Numéro</label>
                        <input id="phone_number" type="tel" name="phone_number" required autocomplete="tel-national" inputmode="tel"
                               value="{{ old('phone_number', auth()->user()->phone) }}" placeholder="01 97 00 00 00" class="input text-sm">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Requis par notre partenaire de paiement ; enregistré sur votre profil.</p>
                    <x-input-error :messages="$errors->get('phone_country')" class="mt-1" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </fieldset>

                <label class="mt-5 flex items-start gap-2 text-sm">
                    <input type="checkbox" name="accept_terms" value="1" class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500" @checked(old('accept_terms'))>
                    <span>J'accepte les <a href="{{ route('pages.terms') }}" target="_blank" class="link">conditions générales de vente</a> et renonce à mon droit de rétractation pour un contenu numérique livré immédiatement.</span>
                </label>
                <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" />

                <button class="btn-primary mt-5 w-full py-3 text-base" :disabled="loading">
                    <span x-show="!loading">Payer {{ fcfa($book->price) }}</span>
                    <span x-show="loading" x-cloak>Redirection…</span>
                </button>
                <p class="mt-3 text-center text-xs text-slate-500">
                    @if ($gateway === 'chariow')
                        Paiement sécurisé par Chariow : Mobile Money (MTN, Moov…) ou carte bancaire.
                    @else
                        Mode démonstration : paiement simulé (aucun débit réel).
                    @endif
                </p>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>
