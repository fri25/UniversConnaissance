@php($editing = $book->exists)
<x-admin-layout :title="$editing ? 'Modifier un e-book' : 'Nouvel e-book'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">{{ $editing ? 'Modifier « '.$book->title.' »' : 'Nouvel e-book' }}</h1>
        <a href="{{ route('admin.books.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    <form method="POST" enctype="multipart/form-data" x-data="{ format: @js(old('format', $book->format)) }"
          action="{{ $editing ? route('admin.books.update', $book) : route('admin.books.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-4 p-5 lg:col-span-2">
            <div>
                <label for="title" class="label">Titre *</label>
                <input id="title" name="title" value="{{ old('title', $book->title) }}" required class="input">
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>
            <div>
                <label for="slug" class="label">Slug (URL)</label>
                <input id="slug" name="slug" value="{{ old('slug', $book->slug) }}" class="input" placeholder="généré automatiquement">
                <x-input-error :messages="$errors->get('slug')" class="mt-1" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="authors" class="label">Auteur(s) *</label>
                    <select id="authors" name="authors[]" multiple size="5" class="input">
                        @foreach ($authors as $author)
                            <option value="{{ $author->id }}" @selected(in_array($author->id, old('authors', $book->authors?->pluck('id')->all() ?? [])))>{{ $author->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Ctrl/Cmd + clic pour en choisir plusieurs. <a class="link" href="{{ route('admin.authors.create') }}">Créer un auteur</a></p>
                    <x-input-error :messages="$errors->get('authors')" class="mt-1" />
                </div>
                <div>
                    <label for="categories" class="label">Catégorie(s) *</label>
                    <select id="categories" name="categories[]" multiple size="5" class="input">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(in_array($category->id, old('categories', $book->categories?->pluck('id')->all() ?? [])))>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('categories')" class="mt-1" />
                </div>
            </div>
            <div>
                <label for="summary" class="label">Résumé / quatrième de couverture</label>
                <textarea id="summary" name="summary" rows="6" class="input">{{ old('summary', $book->summary) }}</textarea>
            </div>
            <div>
                <label for="table_of_contents" class="label">Table des matières (un chapitre par ligne)</label>
                <textarea id="table_of_contents" name="table_of_contents" rows="5" class="input">{{ old('table_of_contents', $book->table_of_contents) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="publisher" class="label">Éditeur</label>
                    <input id="publisher" name="publisher" value="{{ old('publisher', $book->publisher) }}" class="input">
                </div>
                <div>
                    <label for="published_year" class="label">Année</label>
                    <input id="published_year" type="number" name="published_year" value="{{ old('published_year', $book->published_year) }}" class="input">
                    <x-input-error :messages="$errors->get('published_year')" class="mt-1" />
                </div>
                <div>
                    <label for="isbn" class="label">ISBN</label>
                    <input id="isbn" name="isbn" value="{{ old('isbn', $book->isbn) }}" class="input">
                </div>
                <div>
                    <label for="language" class="label">Langue *</label>
                    <select id="language" name="language" class="input">
                        @foreach (\App\Models\Book::LANGUAGES as $code => $label)
                            <option value="{{ $code }}" @selected(old('language', $book->language) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pages" class="label">Pages</label>
                    <input id="pages" type="number" name="pages" value="{{ old('pages', $book->pages) }}" class="input">
                </div>
                <div>
                    <label for="format" class="label">Format *</label>
                    <select id="format" name="format" class="input" x-model="format">
                        @foreach (\App\Models\Book::FORMATS as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-4 p-5">
                <div>
                    <label for="price" class="label">Prix (FCFA) *</label>
                    <input id="price" type="number" min="0" step="50" name="price" value="{{ old('price', $book->price) }}" required class="input">
                    <x-input-error :messages="$errors->get('price')" class="mt-1" />
                </div>
                <div>
                    <label for="old_price" class="label">Ancien prix (promo)</label>
                    <input id="old_price" type="number" min="0" step="50" name="old_price" value="{{ old('old_price', $book->old_price) }}" class="input">
                    <x-input-error :messages="$errors->get('old_price')" class="mt-1" />
                </div>
                <div>
                    <label for="chariow_product_id" class="label">ID du produit Chariow</label>
                    <input id="chariow_product_id" name="chariow_product_id" value="{{ old('chariow_product_id', $book->chariow_product_id) }}" class="input font-mono text-sm" placeholder="prd_abc123">
                    <p class="mt-1 text-xs text-slate-500">Créez le produit dans Chariow au <strong>même prix</strong>, puis collez son identifiant. Sans lui, l'achat est indisponible.</p>
                    <x-input-error :messages="$errors->get('chariow_product_id')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded text-brand-700" @checked(old('is_active', $book->is_active))> Actif (visible en boutique)</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="rounded text-brand-700" @checked(old('is_featured', $book->is_featured))> Mis en avant</label>
            </div>

            <div class="card space-y-4 p-5">
                <div>
                    <label for="cover" class="label">Couverture (JPG/PNG/WebP, ratio 2:3)</label>
                    @if ($book->cover)<img src="{{ $book->coverUrl() }}" alt="" class="mb-2 h-32 rounded">@endif
                    <input id="cover" type="file" name="cover" accept="image/*" class="block w-full text-sm">
                    <x-input-error :messages="$errors->get('cover')" class="mt-1" />
                </div>
                <div>
                    <label for="file" class="label">Fichier complet <span x-text="format === 'epub' ? '(EPUB)' : '(PDF)'"></span> {{ $editing ? '' : '*' }}</label>
                    @if ($book->file_path)<p class="mb-1 text-xs text-slate-500">Actuel : {{ basename($book->file_path) }} ({{ $book->humanFileSize() }})</p>@endif
                    <input id="file" type="file" name="file" class="block w-full text-sm">
                    <p class="mt-1 text-xs text-slate-500">Stocké en privé, jamais exposé publiquement.</p>
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                </div>
                <div x-show="format === 'both'" x-cloak>
                    <label for="epub_file" class="label">Fichier EPUB</label>
                    @if ($book->epub_path)<p class="mb-1 text-xs text-slate-500">Actuel : {{ basename($book->epub_path) }}</p>@endif
                    <input id="epub_file" type="file" name="epub_file" accept=".epub" class="block w-full text-sm">
                    <x-input-error :messages="$errors->get('epub_file')" class="mt-1" />
                </div>
                <div>
                    <label for="sample" class="label">Extrait gratuit (PDF/EPUB)</label>
                    @if ($book->sample_path)<p class="mb-1 text-xs text-slate-500">Actuel : {{ basename($book->sample_path) }}</p>@endif
                    <input id="sample" type="file" name="sample" accept=".pdf,.epub" class="block w-full text-sm">
                    <x-input-error :messages="$errors->get('sample')" class="mt-1" />
                </div>
            </div>

            <button class="btn-solid w-full py-3">{{ $editing ? 'Enregistrer les modifications' : 'Créer l\'e-book' }}</button>
        </div>
    </form>
</x-admin-layout>
