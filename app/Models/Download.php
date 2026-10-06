<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Download extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'token', 'expires_at', 'download_count', 'max_downloads', 'last_downloaded_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'download_count' => 'integer',
            'max_downloads' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function newToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function remaining(): int
    {
        return max(0, $this->max_downloads - $this->download_count);
    }

    public function isExhausted(): bool
    {
        return $this->remaining() === 0;
    }

    /**
     * Prolonge la validité du lien (nouveau jeton) sans réinitialiser le quota.
     */
    public function renew(): void
    {
        $this->forceFill([
            'token' => self::newToken(),
            'expires_at' => now()->addHours(config('ebooks.link_ttl_hours')),
        ])->save();
    }
}
