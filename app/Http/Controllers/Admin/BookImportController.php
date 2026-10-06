<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookImportRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Import CSV de fiches e-books. Les fichiers (couverture, PDF/EPUB) se
 * téléversent ensuite depuis la fiche : les livres importés sont donc inactifs.
 */
class BookImportController extends Controller
{
    public const COLUMNS = [
        'title', 'authors', 'categories', 'price', 'old_price', 'format', 'language',
        'pages', 'publisher', 'published_year', 'isbn', 'summary', 'chariow_product_id',
    ];

    public function create(): View
    {
        return view('admin.books.import', ['columns' => self::COLUMNS]);
    }

    public function store(BookImportRequest $request): RedirectResponse
    {
        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $firstLine = fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), fgetcsv($handle, 0, $delimiter) ?: []);

        if (array_diff(['title', 'authors', 'categories', 'price'], $header) !== []) {
            fclose($handle);

            return back()->with('error', 'Colonnes obligatoires manquantes : title, authors, categories, price.');
        }

        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $data = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
            $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

            $validator = Validator::make($data, [
                'title' => ['required', 'string', 'max:255'],
                'authors' => ['required', 'string'],
                'categories' => ['required', 'string'],
                'price' => ['required', 'integer', 'min:0'],
                'old_price' => ['nullable', 'integer'],
                'format' => ['nullable', Rule::in(array_keys(Book::FORMATS))],
                'language' => ['nullable', Rule::in(array_keys(Book::LANGUAGES))],
                'pages' => ['nullable', 'integer', 'min:1'],
                'published_year' => ['nullable', 'integer'],
                'isbn' => ['nullable', 'string', 'max:20'],
                'chariow_product_id' => ['nullable', 'string', 'max:100', 'alpha_dash'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Ligne {$line} : ".implode(' ', $validator->errors()->all());

                continue;
            }

            DB::transaction(function () use ($data) {
                $book = Book::create([
                    'title' => $data['title'],
                    'slug' => Slug::unique(Book::class, $data['title']),
                    'summary' => $data['summary'] ?? null,
                    'isbn' => $data['isbn'] ?? null,
                    'publisher' => $data['publisher'] ?? null,
                    'published_year' => $data['published_year'] ?? null,
                    'language' => $data['language'] ?? 'fr',
                    'pages' => $data['pages'] ?? null,
                    'format' => $data['format'] ?? 'pdf',
                    'price' => (int) $data['price'],
                    'old_price' => isset($data['old_price']) && (int) $data['old_price'] > (int) $data['price'] ? (int) $data['old_price'] : null,
                    'chariow_product_id' => $data['chariow_product_id'] ?? null,
                    'is_active' => false,
                ]);

                $book->authors()->sync($this->resolve(Author::class, $data['authors']));
                $book->categories()->sync($this->resolve(Category::class, $data['categories']));
            });

            $created++;
        }

        fclose($handle);

        $message = "{$created} e-book(s) importé(s), à compléter (fichiers) puis activer.";

        return redirect()->route('admin.books.index', ['status' => 'inactive'])
            ->with('status', $message)
            ->with('import_errors', $errors);
    }

    /**
     * @param  class-string<Author|Category>  $model
     * @return list<int>
     */
    private function resolve(string $model, string $names): array
    {
        return collect(preg_split('/\s*[|;]\s*/', $names))
            ->filter()
            ->map(fn (string $name) => $model::firstOrCreate(
                ['name' => $name],
                ['slug' => Slug::unique($model, $name)],
            )->id)
            ->values()
            ->all();
    }
}
