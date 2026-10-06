<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $book->reviews()->create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->to(route('books.show', $book).'#avis')->with('status', 'Merci pour votre avis !');
    }

    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update($request->validated());

        return redirect()->to(route('books.show', $review->book).'#avis')->with('status', 'Votre avis a été mis à jour.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $book = $review->book;
        $review->delete();

        return redirect()->to(route('books.show', $book).'#avis')->with('status', 'Avis supprimé.');
    }
}
