<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $books = Book::query()->orderBy('title')->get(['id', 'title']);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $reviews = Review::with(['user:id,name,email', 'book:id,title,slug'])
            ->when($request->query('status') === 'hidden', fn ($q) => $q->where('is_approved', false))
            ->when($request->query('status') === 'visible', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('books', 'reviews', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'user_id' => 'utilisateur',
            'book_id' => 'livre',
            'rating' => 'note',
            'comment' => 'commentaire',
        ]);

        if (Review::where('user_id', $validated['user_id'])->where('book_id', $validated['book_id'])->exists()) {
            throw ValidationException::withMessages([
                'book_id' => 'Cet utilisateur a déjà un avis pour ce livre.',
            ]);
        }

        Review::create($validated + ['is_approved' => true]);

        return back()->with('status', 'Avis ajouté et publié.');
    }

    public function toggle(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('status', $review->is_approved ? 'Avis publié.' : 'Avis masqué.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('status', 'Avis supprimé.');
    }
}
