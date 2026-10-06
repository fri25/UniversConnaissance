<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Un avis par acheteur et par livre.
     */
    public function create(User $user, Book $book): bool
    {
        return $user->hasPurchased($book)
            && ! $user->reviews()->where('book_id', $book->id)->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->is_admin || $review->user_id === $user->id;
    }
}
