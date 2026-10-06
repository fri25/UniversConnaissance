<?php

if (! function_exists('fcfa')) {
    /**
     * Formate un montant entier en francs CFA : « 2 500 FCFA ».
     */
    function fcfa(?int $amount): string
    {
        return number_format((int) $amount, 0, ',', "\u{202F}").' FCFA';
    }
}
