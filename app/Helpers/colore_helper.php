<?php

/**
 * Helper colore — sceglie il colore di testo (nero o bianco) più leggibile
 * su uno sfondo colorato scelto liberamente (es. colore profilo dipendente).
 */

if (! function_exists('colore_testo')) {
    /**
     * Restituisce '#000000' o '#ffffff' in base alla luminanza percepita
     * di $hex (formula YIQ, soglia standard 128), così il testo resta
     * leggibile qualunque sia il colore di sfondo scelto.
     */
    function colore_testo(string $hex): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $yiq >= 128 ? '#000000' : '#ffffff';
    }
}
