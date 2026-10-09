<?php

namespace App\Support;

class Phone
{
    /**
     * Pays par défaut du formulaire d'achat.
     */
    public const DEFAULT_COUNTRY = 'BJ';

    /**
     * Autres noms courants, pour la recherche (insensible aux accents).
     */
    private const ALIASES = [
        'CD' => 'RDC République démocratique du Congo Zaïre',
        'CG' => 'République du Congo',
        'CI' => 'Ivory Coast',
        'GB' => 'Angleterre Royaume-Uni UK Grande-Bretagne Écosse',
        'US' => 'USA Amérique',
        'NL' => 'Hollande',
        'CZ' => 'Tchéquie',
        'MK' => 'Macédoine',
        'SZ' => 'Swaziland',
        'CV' => 'Cabo Verde',
        'MM' => 'Birmanie',
        'KR' => 'Corée',
        'KP' => 'Corée du Nord',
        'AE' => 'Emirats Dubaï',
        'GQ' => 'Guinée-Équatoriale',
    ];

    /**
     * Tous les pays : code ISO 3166-1 alpha-2 => [nom, indicatif].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function countries(): array
    {
        return config('countries');
    }

    /**
     * Liste pour le sélecteur avec recherche.
     *
     * @return list<array{code: string, name: string, dial: string, search: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::countries() as $code => [$name, $dial]) {
            $options[] = [
                'code' => $code,
                'name' => $name,
                'dial' => $dial,
                'search' => trim($name.' '.(self::ALIASES[$code] ?? '').' '.$code.' +'.$dial),
            ];
        }

        return $options;
    }

    /**
     * Numéro national, chiffres uniquement : retire espaces, ponctuation et
     * l'indicatif du pays s'il a été saisi (+229…, 00229…).
     */
    public static function digits(string $phone, ?string $country = null): string
    {
        $trimmed = trim($phone);
        $digits = preg_replace('/\D+/', '', $trimmed);
        $dial = self::countries()[$country][1] ?? null;

        if ($dial !== null) {
            if (str_starts_with($digits, '00'.$dial)) {
                return substr($digits, 2 + strlen($dial));
            }
            if (str_starts_with($trimmed, '+') && str_starts_with($digits, $dial)) {
                return substr($digits, strlen($dial));
            }
        }

        return $digits;
    }
}
