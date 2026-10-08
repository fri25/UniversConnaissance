@php
    $editing = $book->exists;
    // Livres créés avant l'éditeur riche : on reprend l'ancien résumé comme point de départ.
    $initialDescription = old('description', $book->description
        ?? ($book->summary ? '<p>'.nl2br(e($book->summary)).'</p>' : ''));
@endphp
<x-admin-layout :title="$editing ? 'Modifier un e-book' : 'Nouvel e-book'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink dark:text-white">{{ $editing ? 'Modifier « '.$book->title.' »' : 'Nouvel e-book' }}</h1>
        <a href="{{ route('admin.books.index') }}" class="btn-ghost">← Retour</a>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
        <style>
            .ql-toolbar.ql-snow { border-radius: .75rem .75rem 0 0; border-color: rgb(203 213 225); background: #fff; }
            .ql-container.ql-snow { border-radius: 0 0 .75rem .75rem; border-color: rgb(203 213 225); font-family: Inter, sans-serif; font-size: 16px; background: #fff; color: #0B1F2A; }
            .ql-editor { min-height: 320px; line-height: 1.7; }
            .ql-editor img { max-width: 100%; height: auto; border-radius: .5rem; }
            /* Libellés du menu « taille » (valeurs en pixels). */
            .ql-snow .ql-picker.ql-size { width: 82px; }
            .ql-snow .ql-picker.ql-size .ql-picker-label[data-value]::before,
            .ql-snow .ql-picker.ql-size .ql-picker-item[data-value]::before { content: attr(data-value); }
            .ql-snow .ql-picker.ql-size .ql-picker-label:not([data-value])::before,
            .ql-snow .ql-picker.ql-size .ql-picker-item:not([data-value])::before { content: 'Normal'; }
        </style>
    @endpush

    <form method="POST" enctype="multipart/form-data" x-data="{ format: @js(old('format', $book->format)) }" id="book-form"
          action="{{ $editing ? route('admin.books.update', $book) : route('admin.books.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-5 p-5 lg:col-span-2">
            <div>
                <label for="title" class="label">Titre *</label>
                <input id="title" name="title" value="{{ old('title', $book->title) }}" required class="input">
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <span class="label">Description</span>
                <p class="mb-2 text-xs text-slate-500">Mettez en forme librement : titres, gras, couleurs, taille du texte, listes, alignement, liens et images (bouton image ou copier-coller).</p>
                <div id="description-editor">{!! \App\Support\RichText::sanitize($initialDescription) !!}</div>
                <input type="hidden" name="description" id="description-input" value="{{ $initialDescription }}">
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
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

            <details class="text-sm">
                <summary class="cursor-pointer text-slate-600 dark:text-slate-400">Adresse de la page (slug)</summary>
                <div class="mt-2">
                    <label for="slug" class="sr-only">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $book->slug) }}" class="input" placeholder="générée automatiquement à partir du titre">
                    <x-input-error :messages="$errors->get('slug')" class="mt-1" />
                </div>
            </details>
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
                    <x-input-error :messages="$errors->get('chariow_product_id')" class="mt-1" />
                </div>
                <div>
                    <label for="chariow_product_url" class="label">Lien de la page de paiement Chariow</label>
                    <input id="chariow_product_url" type="url" name="chariow_product_url" value="{{ old('chariow_product_url', $book->chariow_product_url) }}" class="input text-sm" placeholder="https://maboutique.mychariow.com/p/mon-livre">
                    <p class="mt-1 text-xs text-slate-500">Créez le produit dans Chariow au <strong>même prix</strong>, puis renseignez son ID et son lien. Sans ces deux champs, l'achat est indisponible.</p>
                    <x-input-error :messages="$errors->get('chariow_product_url')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded text-brand-700" @checked(old('is_active', $book->is_active))> Actif (visible en boutique)</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" class="rounded text-brand-700" @checked(old('is_featured', $book->is_featured))> Mis en avant</label>
            </div>

            <div class="card space-y-4 p-5">
                <div>
                    <label for="cover" class="label">Couverture (JPG/PNG/WebP, format carré)</label>
                    @if ($book->cover)<img src="{{ $book->coverUrl() }}" alt="" class="mb-2 aspect-square h-32 rounded object-cover">@endif
                    <p class="mb-2 text-xs text-slate-500">Une image non carrée est recadrée automatiquement au centre (900 × 900 px).</p>
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
            </div>

            <button class="btn-solid w-full py-3">{{ $editing ? 'Enregistrer les modifications' : 'Créer l\'e-book' }}</button>
        </div>
    </form>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
        <script>
            (function () {
                if (! window.Quill) {
                    // CDN indisponible : on garde un champ texte simple plutôt que de bloquer la saisie.
                    var box = document.getElementById('description-editor');
                    var hidden = document.getElementById('description-input');
                    var area = document.createElement('textarea');
                    area.className = 'input';
                    area.rows = 10;
                    area.value = hidden.value;
                    area.addEventListener('input', function () { hidden.value = area.value; });
                    box.replaceWith(area);
                    return;
                }

                // Tailles et alignements en styles en ligne (rendu identique sur la boutique).
                var Size = Quill.import('attributors/style/size');
                Size.whitelist = ['12px', '14px', '18px', '22px', '28px', '36px'];
                Quill.register(Size, true);
                Quill.register(Quill.import('attributors/style/align'), true);

                var quill = new Quill('#description-editor', {
                    theme: 'snow',
                    placeholder: 'Présentez votre e-book…',
                    modules: {
                        toolbar: {
                            container: [
                                [{ header: [2, 3, false] }],
                                [{ size: Size.whitelist.concat([false]) }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ color: [] }, { background: [] }],
                                [{ align: [] }],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['blockquote', 'link', 'image'],
                                ['clean'],
                            ],
                            handlers: { image: uploadImage },
                        },
                    },
                });

                var input = document.getElementById('description-input');
                var sync = function () { input.value = quill.getLength() > 1 ? quill.getSemanticHTML() : ''; };
                quill.on('text-change', sync);
                document.getElementById('book-form').addEventListener('submit', sync);

                function uploadImage() {
                    var picker = document.createElement('input');
                    picker.type = 'file';
                    picker.accept = 'image/png,image/jpeg,image/gif,image/webp';
                    picker.onchange = function () {
                        var file = picker.files[0];
                        if (! file) { return; }
                        if (file.size > 5 * 1024 * 1024) { alert('Image trop lourde (5 Mo maximum).'); return; }
                        var data = new FormData();
                        data.append('image', file);
                        fetch(@js(route('admin.editor.images')), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: data,
                        })
                            .then(function (r) { if (! r.ok) { throw new Error(); } return r.json(); })
                            .then(function (json) {
                                var range = quill.getSelection(true);
                                quill.insertEmbed(range.index, 'image', json.url, 'user');
                                quill.setSelection(range.index + 1);
                            })
                            .catch(function () { alert('L\'image n\'a pas pu être envoyée. Vérifiez son format (JPG, PNG, GIF, WebP) et sa taille.'); });
                    };
                    picker.click();
                }
            })();
        </script>
    @endpush
</x-admin-layout>
