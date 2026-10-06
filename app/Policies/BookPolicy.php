<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * Un e-book actif peut être acheté une seule fois par client.
     */
    public function purchase(User $user, Book $book): bool
    {
        return $book->is_active && ! $user->hasPurchased($book);
    }
}
