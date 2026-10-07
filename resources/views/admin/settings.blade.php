<x-admin-layout title="Réglages">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Réglages</h1>
    </x-slot>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl space-y-6 p-5 sm:p-6">
        @csrf @method('PUT')

        <div>
            <h2 class="font-sans text-base font-semibold text-ink dark:text-white">Pixel Meta (Facebook / Instagram)</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Mesure les visites, les clics sur « Acheter » et les achats pour vos publicités Meta.
                Le pixel ne se déclenche qu'après acceptation du bandeau cookies par le visiteur.
            </p>
            @if ($pixelId)
                <p class="mt-2"><span class="badge bg-emerald-100 text-emerald-800">Pixel actif</span></p>
            @else
                <p class="mt-2"><span class="badge bg-slate-200 text-slate-700">Pixel désactivé</span></p>
            @endif
        </div>

        <div>
            <label for="meta_pixel_id" class="label">Identifiant du pixel (ID du jeu de données)</label>
            <input id="meta_pixel_id" name="meta_pixel_id" inputmode="numeric" value="{{ old('meta_pixel_id', $pixelId) }}" class="input font-mono" placeholder="123456789012345">
            <p class="mt-1 text-xs text-slate-500">Gestionnaire d'événements Meta → Sources de données → votre pixel → Paramètres. Laissez vide pour désactiver.</p>
            <x-input-error :messages="$errors->get('meta_pixel_id')" class="mt-1" />
        </div>

        <div>
            <label for="meta_capi_token" class="label">Jeton d'accès de l'API Conversions <span class="font-normal text-slate-500">(recommandé)</span></label>
            <input id="meta_capi_token" type="password" name="meta_capi_token" autocomplete="off" class="input font-mono text-sm"
                   placeholder="{{ $hasToken ? '•••••••• (enregistré, laisser vide pour le conserver)' : 'EAAB…' }}">
            <p class="mt-1 text-xs text-slate-500">
                Le paiement a lieu sur la page Chariow : sans ce jeton, beaucoup d'achats ne remontent pas à Meta.
                Pixel → Paramètres → API Conversions → « Générer un jeton d'accès ». Stocké chiffré.
            </p>
            @if ($hasToken)
                <label class="mt-2 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remove_token" value="1" class="rounded text-brand-700"> Supprimer le jeton enregistré
                </label>
            @endif
            <x-input-error :messages="$errors->get('meta_capi_token')" class="mt-1" />
        </div>

        <div>
            <label for="meta_test_event_code" class="label">Code d'événement de test <span class="font-normal text-slate-500">(facultatif, temporaire)</span></label>
            <input id="meta_test_event_code" name="meta_test_event_code" value="{{ old('meta_test_event_code', $testEventCode) }}" class="input font-mono" placeholder="TEST12345">
            <p class="mt-1 text-xs text-slate-500">Onglet « Tester les événements » du Gestionnaire d'événements. Videz ce champ une fois les tests terminés.</p>
            <x-input-error :messages="$errors->get('meta_test_event_code')" class="mt-1" />
        </div>

        <button class="btn-solid">Enregistrer</button>
    </form>
</x-admin-layout>
