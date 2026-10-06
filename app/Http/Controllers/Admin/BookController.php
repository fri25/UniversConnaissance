<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $books = Book::with('authors:id,name')
            ->withCount('paidOrders as sales_count')
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.books.index', compact('books'));
    }

    public function create(): View
    {
        return view('admin.books.form', $this->formData(new Book(['language' => 'fr', 'format' => 'pdf', 'is_active' => true])));
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $book = new Book;
        $this->save($request, $book);

        return redirect()->route('admin.books.index')->with('status', "« {$book->title} » a été créé.");
    }

    public function edit(Book $book): View
    {
        return view('admin.books.form', $this->formData($book->load('authors', 'categories')));
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->save($request, $book);

        return redirect()->route('admin.books.index')->with('status', "« {$book->title} » a été mis à jour.");
    }

    public function destroy(Book $book): RedirectResponse
    {
        // Un livre déjà vendu reste téléchargeable par ses acheteurs : on le masque.
        if ($book->orders()->exists()) {
            $book->update(['is_active' => false]);

            return back()->with('status', "« {$book->title} » a des commandes : il a été désactivé au lieu d'être supprimé.");
        }

        $private = Storage::disk(config('ebooks.disk'));
        $private->delete(array_filter([$book->file_path, $book->epub_path, $book->sample_path]));
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        $book->delete();

        return back()->with('status', 'E-book supprimé.');
    }

    private function save(BookRequest $request, Book $book): void
    {
        $data = $request->safe()->except(['authors', 'categories', 'cover', 'file', 'epub_file', 'sample', 'slug']);
        $data['slug'] = $request->filled('slug')
            ? $request->input('slug')
            : ($book->slug ?? Slug::unique(Book::class, $request->input('title')));

        if ($data['format'] !== 'both') {
            $data['epub_path'] = null;
        }

        $private = config('ebooks.disk');

        if ($request->hasFile('cover')) {
            $data['cover'] = $this->replace($book->cover, $request->file('cover'), 'covers', 'public');
        }
        if ($request->hasFile('file')) {
            $data['file_path'] = $this->replace($book->file_path, $request->file('file'), 'ebooks', $private);
            $data['file_size'] = $request->file('file')->getSize();
        }
        if ($request->hasFile('epub_file')) {
            $data['epub_path'] = $this->replace($book->epub_path, $request->file('epub_file'), 'ebooks', $private);
        }
        if ($request->hasFile('sample')) {
            $data['sample_path'] = $this->replace($book->sample_path, $request->file('sample'), 'samples', $private);
        }

        $book->fill($data)->save();
        $book->authors()->sync($request->input('authors', []));
        $book->categories()->sync($request->input('categories', []));
    }

    private function replace(?string $old, UploadedFile $file, string $dir, string $disk): string
    {
        if ($old) {
            Storage::disk($disk)->delete($old);
        }

        return $file->store($dir, $disk);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Book $book): array
    {
        return [
            'book' => $book,
            'authors' => Author::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ];
    }
}
