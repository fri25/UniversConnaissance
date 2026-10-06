<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorRequest;
use App\Models\Author;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $authors = Author::withCount('books')->orderBy('name')->paginate(25);

        return view('admin.authors.index', compact('authors'));
    }

    public function create(): View
    {
        return view('admin.authors.form', ['author' => new Author]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        $this->save($request, new Author);

        return redirect()->route('admin.authors.index')->with('status', 'Auteur créé.');
    }

    public function edit(Author $author): View
    {
        return view('admin.authors.form', compact('author'));
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        $this->save($request, $author);

        return redirect()->route('admin.authors.index')->with('status', 'Auteur mis à jour.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->books()->exists()) {
            return back()->with('error', 'Impossible de supprimer un auteur associé à des e-books.');
        }

        if ($author->photo) {
            Storage::disk('public')->delete($author->photo);
        }
        $author->delete();

        return back()->with('status', 'Auteur supprimé.');
    }

    private function save(AuthorRequest $request, Author $author): void
    {
        $data = $request->safe()->only(['name', 'bio']);
        $data['slug'] = $request->input('slug') ?: ($author->slug ?? Slug::unique(Author::class, $data['name']));

        if ($request->hasFile('photo')) {
            if ($author->photo) {
                Storage::disk('public')->delete($author->photo);
            }
            $data['photo'] = $request->file('photo')->store('authors', 'public');
        }

        $author->fill($data)->save();
    }
}
