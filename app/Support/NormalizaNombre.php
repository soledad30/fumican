<?php

namespace App\Support;

class NormalizaNombre
{
    public static function rol(string $nombre): string
    {
        $texto = trim($nombre);

        if ($texto === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $texto = transliterator_transliterate('Any-Latin; Latin-ASCII', $texto) ?: $texto;
        } else {
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        }

        return mb_strtolower(preg_replace('/\s+/', ' ', $texto) ?? $texto);
    }
}
