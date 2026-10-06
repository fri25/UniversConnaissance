<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(CatalogFilterRequest $request): View
    {
        return $this->render($request->filters());
    }

    public function category(CatalogFilterRequest $request, Category $category): View
    {
        return $this->render(['category' => $category->slug] + $request->filters(), $category);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function render(array $filters, ?Category $category = null): View
    {
        $books = Book::active()->forCard()
            ->filter($filters)
            ->paginate(12)
            ->withQueryString();

        return view('books.index', [
            'books' => $books,
            'filters' => $filters,
            'currentCategory' => $category,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'parent_id']),
            'authors' => Author::whereHas('books', fn ($q) => $q->where('is_active', true))
                ->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }
}
