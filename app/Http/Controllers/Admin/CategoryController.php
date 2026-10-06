<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('parent:id,name')->withCount('books')->orderBy('name')->paginate(25);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category, 'parents' => Category::orderBy('name')->get()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->save($request, new Category);

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->save($request, $category);

        return redirect()->route('admin.categories.index')->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'Impossible de supprimer une catégorie contenant des e-books.');
        }

        $category->delete();

        return back()->with('status', 'Catégorie supprimée.');
    }

    private function save(CategoryRequest $request, Category $category): void
    {
        $data = $request->safe()->only(['name', 'description', 'parent_id']);
        $data['slug'] = $request->input('slug') ?: ($category->slug ?? Slug::unique(Category::class, $data['name']));

        $category->fill($data)->save();
    }
}
