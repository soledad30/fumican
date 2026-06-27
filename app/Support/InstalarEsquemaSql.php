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

        foreach (self::prepararStatements(File::get($path)) as $statement) {
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

    /** @return list<string> */
    private static function prepararStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inDollarBlock = false;

        foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
            $trimmed = trim($line);

            if (! $inDollarBlock && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                continue;
            }

            if (! $inDollarBlock && preg_match('/\$\$/', $line)) {
                $inDollarBlock = true;
            }

            $buffer .= $line."\n";

            if ($inDollarBlock) {
                if (preg_match('/END\s*\$\$\s*;?\s*$/i', $trimmed) || preg_match('/\$\$\s*LANGUAGE\s+\w+\s*;?\s*$/i', $trimmed)) {
                    $statement = self::limpiarStatement($buffer);
                    if ($statement !== '' && ! in_array(strtoupper($statement), ['BEGIN', 'COMMIT'], true)) {
                        $statements[] = $statement;
                    }
                    $buffer = '';
                    $inDollarBlock = false;
                }

                continue;
            }

            if (str_ends_with($trimmed, ';')) {
                $statement = self::limpiarStatement($buffer);
                if ($statement !== '' && ! in_array(strtoupper($statement), ['BEGIN', 'COMMIT'], true)) {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $resto = self::limpiarStatement($buffer);
        if ($resto !== '' && ! in_array(strtoupper($resto), ['BEGIN', 'COMMIT'], true)) {
            $statements[] = $resto;
        }

        return $statements;
    }

    private static function limpiarStatement(string $chunk): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($chunk));
        $clean = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            $clean[] = $line;
        }

        return trim(implode("\n", $clean));
    }
}
