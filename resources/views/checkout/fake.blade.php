<x-app-layout title="Paiement simulé">
    <div class="container-page max-w-lg py-12">
        <div class="card p-6">
            <div class="text-center">
                <span class="badge bg-amber-100 text-amber-900">Mode démonstration</span>
                <h1 class="mt-4 text-2xl font-bold text-ink dark:text-white">Paiement simulé</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Cette page imite la page de paiement Chariow : le client y saisit ses coordonnées, sans compte.
                </p>
            </div>

            <div class="mt-6 flex items-center gap-4 rounded-xl bg-slate-50 p-3 dark:bg-ink-900">
                <img src="{{ $book->coverUrl() }}" alt="" class="aspect-cover w-14 rounded object-cover">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-ink dark:text-white">{{ $book->title }}</p>
                    <p class="font-bold text-ink dark:text-white">{{ fcfa($book->price) }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('payment.fake.complete', $book) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="label">Nom complet</label>
                    <input id="name" name="name" required value="{{ old('name', $user?->name) }}" class="input" autocomplete="name">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label for="email" class="label">Email (réception de l'e-book)</label>
                    <input id="email" type="email" name="email" required value="{{ old('email', $user?->email) }}" class="input" autocomplete="email">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label for="phone" class="label">Téléphone Mobile Money</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" class="input" placeholder="+229 01 97 00 00 00" autocomplete="tel">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button name="outcome" value="failure" class="btn-outline w-full">Simuler un échec</button>
                    <button name="outcome" value="success" class="btn-primary w-full">Payer {{ fcfa($book->price) }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
