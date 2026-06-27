<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstalarEsquemaSql
{
    public static function aplicar(string $connection, string $archivo): void
    {
        $path = database_path('schema/'.$archivo);

        if (! File::exists($path)) {
            return;
        }

        $sql = File::get($path);
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*\n/', $sql)),
            fn (string $s) => $s !== '' && ! str_starts_with($s, '--')
        );

        foreach ($statements as $statement) {
            if (str_starts_with(strtoupper($statement), 'PRAGMA')) {
                DB::connection($connection)->statement($statement);

                continue;
            }

            DB::connection($connection)->unprepared($statement);
        }
    }

    public static function aplicarSiFalta(string $connection, string $archivo, string $tablaReferencia): void
    {
        if (Schema::connection($connection)->hasTable($tablaReferencia)) {
            return;
        }

        self::aplicar($connection, $archivo);
    }

    public static function archivoPrincipal(): string
    {
        return match (config('database.default')) {
            'pgsql' => 'clinica_veterinaria_base.sql',
            'sqlite' => 'sqlite_local.sql',
            default => 'sqlite_local.sql',
        };
    }
}
