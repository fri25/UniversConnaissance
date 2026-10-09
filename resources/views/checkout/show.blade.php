<x-app-layout title="Acheter — {{ $book->title }}">
    <div class="container-page max-w-4xl py-10">
        <ol class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500" aria-label="Étapes">
            <li class="text-brand-700 dark:text-brand-300">1. Vos coordonnées</li>
            <li aria-hidden="true">→</li>
            <li>2. Paiement</li>
            <li aria-hidden="true">→</li>
            <li>3. Téléchargement</li>
        </ol>
        <h1 class="mt-3 text-3xl font-bold text-ink dark:text-white">Finaliser votre achat</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">Aucun compte à créer : votre e-book sera envoyé à l'adresse email indiquée.</p>

        <div class="mt-8 grid gap-8 md:grid-cols-[1fr_22rem]">
            <div class="card flex h-fit gap-5 p-5">
                <img src="{{ $book->coverUrl() }}" alt="" class="aspect-cover h-28 w-28 shrink-0 self-start rounded-lg object-cover shadow-cover">
                <div>
                    <h2 class="font-serif text-xl font-bold text-ink dark:text-white">{{ $book->title }}</h2>
                    @if ($book->authors->isNotEmpty())
                        <p class="text-slate-600 dark:text-slate-400">{{ $book->authorNames() }}</p>
                    @endif
                    <p class="mt-2 text-sm text-slate-500">Format : {{ $book->formatLabel() }} @if ($book->humanFileSize()) · {{ $book->humanFileSize() }} @endif</p>
                    <ul class="mt-4 space-y-1 text-sm text-slate-700 dark:text-slate-300">
                        <li>✓ Téléchargement immédiat après paiement</li>
                        <li>✓ Lien de téléchargement envoyé par email</li>
                        <li>✓ Fichier personnalisé à votre nom</li>
                    </ul>
                    <dl class="mt-5 space-y-1 border-t border-slate-200 pt-4 text-sm dark:border-ink-600">
                        @if ($book->isOnPromo())
                            <div class="flex justify-between gap-6"><dt>Prix</dt><dd>{{ fcfa($book->old_price) }}</dd></div>
                            <div class="flex justify-between gap-6 text-rose-700 dark:text-rose-300"><dt>Réduction</dt><dd>−{{ fcfa($book->old_price - $book->price) }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-6 text-base font-bold text-ink dark:text-white"><dt>Total</dt><dd>{{ fcfa($book->price) }}</dd></div>
                    </dl>
                </div>
            </div>

            <form method="POST" action="{{ route('checkout.store', $book) }}" class="card h-fit space-y-4 p-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div>
                    <label for="name" class="label">Nom complet</label>
                    <input id="name" name="name" required autocomplete="name" value="{{ old('name', $user?->name) }}" class="input">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <label for="email" class="label">Adresse email</label>
                    @if ($user)
                        <input id="email" type="email" value="{{ $user->email }}" class="input bg-slate-50 dark:bg-ink-800" readonly>
                        <p class="mt-1 text-xs text-slate-500">L'achat sera ajouté à votre compte.</p>
                    @else
                        <input id="email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}" class="input" placeholder="vous@exemple.com">
                        <p class="mt-1 text-xs text-slate-500">Votre lien de téléchargement y sera envoyé : vérifiez-la bien.</p>
                    @endif
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <fieldset>
                    <legend class="label">Téléphone (Mobile Money)</legend>
                    @php($phoneCountry = old('phone_country', $user?->phone_country))
                    <x-phone-input
                        :country="array_key_exists((string) $phoneCountry, \App\Support\Phone::countries()) ? $phoneCountry : \App\Support\Phone::DEFAULT_COUNTRY"
                        :number="old('phone_number', $user?->phone)" />
                    <p class="mt-1 text-xs text-slate-500">Requis par notre partenaire de paiement Chariow.</p>
                    <x-input-error :messages="$errors->get('phone_country')" class="mt-1" />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
                </fieldset>

                <button class="btn-primary w-full py-3 text-base" :disabled="loading">
                    <span x-show="!loading">Payer {{ fcfa($book->price) }}</span>
                    <span x-show="loading" x-cloak>Redirection vers le paiement…</span>
                </button>
                <p class="text-center text-xs text-slate-500">
                    En cliquant sur « Payer », vous acceptez nos <a href="{{ route('pages.terms') }}" target="_blank" class="link">conditions générales de vente</a>
                    et l'accès immédiat à l'e-book (sans droit de rétractation pour un contenu numérique).
                </p>
                <p class="text-center text-xs text-slate-500">
                    @if ($gateway === 'chariow')
                        Paiement sécurisé par Chariow : Mobile Money (MTN, Moov…) ou carte bancaire.
                    @else
                        Mode démonstration : paiement simulé (aucun débit réel).
                    @endif
                </p>
            </form>
        </div>
    </div>
</x-app-layout>
