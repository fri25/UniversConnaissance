<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<int>|null
     */
    private ?array $ownedBookIdsCache = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_country',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function paidOrderFor(Book $book): ?Order
    {
        return $this->orders()
            ->where('book_id', $book->id)
            ->where('status', Order::STATUS_PAID)
            ->latest('paid_at')
            ->first();
    }

    /**
     * Identifiants des livres achetés (mémorisés pour la requête en cours).
     *
     * @return list<int>
     */
    public function ownedBookIds(): array
    {
        return $this->ownedBookIdsCache ??= $this->orders()
            ->where('status', Order::STATUS_PAID)
            ->pluck('book_id')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPurchased(Book $book): bool
    {
        return $this->orders()
            ->where('book_id', $book->id)
            ->where('status', Order::STATUS_PAID)
            ->exists();
    }
}
