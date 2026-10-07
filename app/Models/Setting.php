<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Réglages modifiables depuis l'admin (clé / valeur), mis en cache.
 * Les valeurs sensibles (jetons) sont chiffrées en base.
 */
class Setting extends Model
{
    public const ENCRYPTED = ['meta_capi_token'];

    private const CACHE_KEY = 'settings.all';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::all_()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (in_array($key, self::ENCRYPTED, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED, true)) {
            $value = Crypt::encryptString($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, ?string>
     */
    private static function all_(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            // Table absente (installation en cours) : aucun réglage.
            return [];
        }
    }
}
