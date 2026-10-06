<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function show(Request $request, Book $book): View
    {
        abort_unless($book->is_active || $request->user()?->is_admin, 404);

        $book->load(['authors', 'categories'])
            ->loadAvg('approvedReviews as rating_avg', 'rating')
            ->loadCount('approvedReviews as rating_count');

        $reviews = $book->approvedReviews()->with('user:id,name')->latest()->take(20)->get();

        $ratingBreakdown = $book->approvedReviews()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $sameCategory = Book::active()->forCard()
            ->whereKeyNot($book->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $book->categories->pluck('id')))
            ->inRandomOrder()->take(4)->get();

        $sameAuthor = Book::active()->forCard()
            ->whereKeyNot($book->id)
            ->whereHas('authors', fn ($q) => $q->whereIn('authors.id', $book->authors->pluck('id')))
            ->take(4)->get();

        $user = $request->user();
        $purchase = $user?->paidOrderFor($book);
        $userReview = $user ? Review::where('user_id', $user->id)->where('book_id', $book->id)->first() : null;

        return view('books.show', compact(
            'book', 'reviews', 'ratingBreakdown', 'sameCategory', 'sameAuthor', 'purchase', 'userReview'
        ));
    }

    /**
     * Extrait gratuit, servi depuis le stockage privé.
     */
    public function sample(Book $book): StreamedResponse
    {
        $disk = Storage::disk(config('ebooks.disk'));

        abort_unless($book->is_active && $book->sample_path && $disk->exists($book->sample_path), 404);

        $extension = pathinfo($book->sample_path, PATHINFO_EXTENSION) ?: 'pdf';

        return $disk->download($book->sample_path, $book->slug.'-extrait.'.$extension);
    }
}
