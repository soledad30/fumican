<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

final class FormatoPdf
{
    public static function fecha(mixed $valor, string $formato = 'd/m/Y'): string
    {
        if ($valor === null || $valor === '') {
            return 'N/A';
        }

        try {
            if ($valor instanceof DateTimeInterface) {
                return $valor->format($formato);
            }

            return Carbon::parse($valor)->format($formato);
        } catch (Throwable) {
            return 'N/A';
        }
    }

    public static function nombreCompleto(?object $persona): string
    {
        if ($persona === null) {
            return 'N/A';
        }

        $full = $persona->full_name ?? null;
        if (is_string($full) && trim($full) !== '') {
            return trim($full);
        }

        $nombre = trim(
            ($persona->first_name ?? $persona->nombre ?? '')
            .' '
            .($persona->last_name ?? $persona->apellido ?? '')
        );

        return $nombre !== '' ? $nombre : 'N/A';
    }
}
