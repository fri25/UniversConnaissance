<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'old_price' => $this->input('old_price') === '' ? null : $this->input('old_price'),
        ]);
    }

    public function rules(): array
    {
        $book = $this->route('book');
        $creating = $book === null;
        $format = $this->input('format');

        // Fichier principal : PDF pour pdf/both, EPUB pour epub.
        $epubRules = ['extensions:epub', 'mimetypes:application/epub+zip,application/zip,application/octet-stream'];
        $mainRules = $format === 'epub' ? $epubRules : ['mimes:pdf'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('books', 'slug')->ignore($book?->id)],
            // HTML de l'éditeur : nettoyé par RichText avant enregistrement (images en base64 incluses).
            'description' => ['nullable', 'string', 'max:15000000'],
            'language' => ['required', Rule::in(array_keys(Book::LANGUAGES))],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'format' => ['required', Rule::in(array_keys(Book::FORMATS))],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'old_price' => ['nullable', 'integer', 'gt:price'],
            'chariow_product_id' => ['nullable', 'string', 'max:100', 'alpha_dash'],
            'chariow_product_url' => ['nullable', 'url:https', 'max:500'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'file' => [$creating ? 'required' : 'nullable', 'file', ...$mainRules, 'max:204800'],
            'epub_file' => [$creating && $format === 'both' ? 'required' : 'nullable', 'file', ...$epubRules, 'max:204800'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $book = $this->route('book');
                // Passage en format "both" sans EPUB existant : l'EPUB devient obligatoire.
                if ($book && $this->input('format') === 'both' && ! $book->epub_path && ! $this->hasFile('epub_file')) {
                    $validator->errors()->add('epub_file', 'Le fichier EPUB est requis pour le format PDF + EPUB.');
                }
                // Le fichier principal change de nature (PDF <-> EPUB) : il faut le remplacer.
                if ($book && ($book->format === 'epub') !== ($this->input('format') === 'epub') && ! $this->hasFile('file')) {
                    $validator->errors()->add('file', 'Changer de format impose de téléverser le nouveau fichier complet.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'price' => 'prix',
            'old_price' => 'ancien prix',
            'published_year' => 'année',
            'authors' => 'auteurs',
            'categories' => 'catégories',
            'cover' => 'couverture',
            'file' => 'fichier complet',
            'epub_file' => 'fichier EPUB',
            'sample' => 'extrait',
            'chariow_product_id' => 'produit Chariow',
            'chariow_product_url' => 'lien de paiement Chariow',
        ];
    }
}
