<?php

namespace App\Support;

class Phone
{
    /**
     * Pays proposés au paiement (ISO 3166-1 alpha-2 => [nom, indicatif]).
     */
    public const COUNTRIES = [
        'BJ' => ['Bénin', '229'],
        'CI' => ['Côte d\'Ivoire', '225'],
        'SN' => ['Sénégal', '221'],
        'TG' => ['Togo', '228'],
        'BF' => ['Burkina Faso', '226'],
        'ML' => ['Mali', '223'],
        'NE' => ['Niger', '227'],
        'GN' => ['Guinée', '224'],
        'CM' => ['Cameroun', '237'],
        'GA' => ['Gabon', '241'],
        'CG' => ['Congo', '242'],
        'CD' => ['RD Congo', '243'],
        'FR' => ['France', '33'],
        'BE' => ['Belgique', '32'],
        'CA' => ['Canada', '1'],
    ];

    /**
     * Numéro national, chiffres uniquement : retire espaces, ponctuation et
     * l'indicatif du pays s'il a été saisi (+229…, 00229…).
     */
    public static function digits(string $phone, ?string $country = null): string
    {
        $trimmed = trim($phone);
        $digits = preg_replace('/\D+/', '', $trimmed);
        $dial = self::COUNTRIES[$country][1] ?? null;

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
