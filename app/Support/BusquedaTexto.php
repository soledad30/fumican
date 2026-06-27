<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class BusquedaTexto
{
    public static function patron(string $term): string
    {
        return '%'.mb_strtolower(trim($term)).'%';
    }

    public static function whereLike(Builder $query, string $column, string $term, string $boolean = 'and'): Builder
    {
        $pattern = self::patron($term);
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';

        return $query->{$method}('LOWER('.$column.') LIKE ?', [$pattern]);
    }
}
