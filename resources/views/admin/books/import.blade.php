<x-admin-layout title="Import CSV">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">Import CSV d'e-books</h1>
        <a href="{{ route('admin.books.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.books.import.store') }}" enctype="multipart/form-data" class="card space-y-4 p-5">
            @csrf
            <div>
                <label for="csv" class="label">Fichier CSV (séparateur , ou ;)</label>
                <input id="csv" type="file" name="csv" accept=".csv,text/csv" required class="block w-full text-sm">
                <x-input-error :messages="$errors->get('csv')" class="mt-1" />
            </div>
            <button class="btn-solid">Importer</button>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Les e-books importés sont créés <strong>inactifs</strong> : ajoutez ensuite la couverture et le fichier depuis leur fiche, puis activez-les.
                Les auteurs et catégories inconnus sont créés automatiquement.
            </p>
        </form>

        <div class="card p-5 text-sm">
            <h2 class="font-sans font-semibold text-ink dark:text-white">Colonnes attendues</h2>
            <p class="mt-2 font-mono text-xs">{{ implode(',', $columns) }}</p>
            <ul class="mt-3 list-inside list-disc space-y-1 text-slate-600 dark:text-slate-400">
                <li>Obligatoires : <code>title</code>, <code>authors</code>, <code>categories</code>, <code>price</code></li>
                <li>Plusieurs auteurs/catégories : séparés par <code>|</code></li>
                <li><code>format</code> : pdf, epub ou both — <code>language</code> : fr, en, es, de</li>
                <li>Prix en FCFA, nombres entiers</li>
            </ul>
            <p class="mt-3 font-semibold">Exemple</p>
            <pre class="mt-1 overflow-x-auto rounded-lg bg-slate-100 p-3 text-xs dark:bg-ink-900">title,authors,categories,price,old_price,format,language,pages,publisher,published_year,isbn,summary
Le Petit Guide,Jean Dupont|Awa Diallo,Business,3500,5000,pdf,fr,120,UC Éditions,2025,,Un guide pratique.</pre>
        </div>
    </div>
</x-admin-layout>
