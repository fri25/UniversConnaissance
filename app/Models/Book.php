<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    use HasFactory;

    public const FORMATS = ['pdf' => 'PDF', 'epub' => 'EPUB', 'both' => 'PDF + EPUB'];

    public const LANGUAGES = ['fr' => 'Français', 'en' => 'Anglais', 'es' => 'Espagnol', 'de' => 'Allemand'];

    public const NEW_DAYS = 30;

    protected $fillable = [
        'title', 'slug', 'summary', 'table_of_contents', 'isbn', 'publisher', 'published_year',
        'language', 'pages', 'format', 'price', 'old_price', 'cover', 'sample_path',
        'file_path', 'epub_path', 'chariow_product_id', 'chariow_product_url', 'file_size', 'is_active', 'is_featured',
    ];

    protected $hidden = ['file_path', 'epub_path'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'price' => 'integer',
            'old_price' => 'integer',
            'pages' => 'integer',
            'published_year' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function paidOrders(): HasMany
    {
        return $this->orders()->where('status', Order::STATUS_PAID);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Charge ce qu'il faut pour afficher une carte e-book.
     */
    public function scopeForCard(Builder $query): Builder
    {
        return $query->with(['authors:id,name,slug', 'categories:id,name,slug'])
            ->withAvg('approvedReviews as rating_avg', 'rating')
            ->withCount('approvedReviews as rating_count');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->when($filters['q'] ?? null, function (Builder $q, string $term) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $q->where(function (Builder $q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('isbn', 'like', $like)
                    ->orWhere('publisher', 'like', $like)
                    ->orWhereHas('authors', fn (Builder $a) => $a->where('name', 'like', $like));
            });
        });

        $query->when($filters['category'] ?? null, function (Builder $q, string $slug) {
            $q->whereHas('categories', fn (Builder $c) => $c->where('slug', $slug)
                ->orWhereIn('parent_id', Category::where('slug', $slug)->select('id')));
        });

        $query->when($filters['author'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
            'authors', fn (Builder $a) => $a->where('slug', $slug)
        ));

        $query->when($filters['language'] ?? null, fn (Builder $q, string $lang) => $q->where('language', $lang));

        $query->when($filters['format'] ?? null, function (Builder $q, string $format) {
            $q->whereIn('format', [$format, 'both']);
        });

        $query->when(is_numeric($filters['min_price'] ?? null), fn (Builder $q) => $q->where('price', '>=', (int) $filters['min_price']));
        $query->when(is_numeric($filters['max_price'] ?? null), fn (Builder $q) => $q->where('price', '<=', (int) $filters['max_price']));

        $query->when(! empty($filters['promo']), fn (Builder $q) => $q->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'));

        return match ($filters['sort'] ?? 'recent') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'title' => $query->orderBy('title'),
            'popular' => $query->withCount('paidOrders as sales_count')->orderByDesc('sales_count'),
            'rating' => $query->orderByDesc('rating_avg'),
            default => $query->latest(),
        };
    }

    /**
     * Le prestataire de paiement actif sait-il encaisser cet e-book ?
     */
    public function isPurchasable(): bool
    {
        return $this->is_active
            && app(\App\Payments\PaymentManager::class)->gateway()->checkoutUrl($this) !== null;
    }

    public function isOnPromo(): bool
    {
        return $this->old_price !== null && $this->old_price > $this->price;
    }

    public function discountPercent(): ?int
    {
        return $this->isOnPromo() ? (int) round(100 - ($this->price * 100 / $this->old_price)) : null;
    }

    public function isNew(): bool
    {
        return $this->created_at !== null && $this->created_at->gt(now()->subDays(self::NEW_DAYS));
    }

    public function formatLabel(): string
    {
        return self::FORMATS[$this->format] ?? strtoupper($this->format);
    }

    public function languageLabel(): string
    {
        return self::LANGUAGES[$this->language] ?? strtoupper($this->language);
    }

    /**
     * @return list<string>
     */
    public function availableFormats(): array
    {
        return match ($this->format) {
            'both' => ['pdf', 'epub'],
            default => [$this->format],
        };
    }

    /**
     * Chemin du fichier complet (disque privé) pour un format donné.
     */
    public function filePathFor(string $format): ?string
    {
        if (! in_array($format, $this->availableFormats(), true)) {
            return null;
        }

        return ($this->format === 'both' && $format === 'epub') ? $this->epub_path : $this->file_path;
    }

    public function coverUrl(): string
    {
        return $this->cover
            ? Storage::disk('public')->url($this->cover)
            : asset('images/cover-placeholder.svg');
    }

    public function authorNames(): string
    {
        return $this->authors->pluck('name')->join(', ', ' et ');
    }

    public function humanFileSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = $this->file_size;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return number_format($size, $i > 1 ? 1 : 0, ',', ' ').' '.$units[$i];
    }
}
