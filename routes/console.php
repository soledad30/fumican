<?php

use App\Support\InstalarEsquemaSql;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('fumican:install-local {--fresh : Borra y recrea la base de datos local}', function () {
    if (config('tablas.usar_bd_grupo', true)) {
        $this->warn('USAR_BD_GRUPO=true — configure USAR_BD_GRUPO=false en .env para seeders.');
        if (! $this->confirm('¿Continuar solo con esquema SQL?', false)) {
            return 1;
        }
    }

    $driver = config('database.default');

    if ($driver === 'sqlite') {
        $dbPath = database_path('database.sqlite');
        if ($this->option('fresh') && File::exists($dbPath)) {
            File::delete($dbPath);
        }
        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
            $this->info('Creado database/database.sqlite');
        }
    }

    $this->info('Instalando esquema SQL...');
    InstalarEsquemaSql::aplicar('auditoria', 'auditoria.sql');

    if ($driver === 'sqlite') {
        InstalarEsquemaSql::aplicar(config('database.default'), 'sqlite_local.sql');
    } elseif ($this->option('fresh')) {
        $this->warn('PostgreSQL: recree tablas con psql -f database/schema/clinica_veterinaria_base.sql');
    }

    $this->info('Esquema aplicado.');

    if (! config('tablas.usar_bd_grupo', true)) {
        $this->info('Ejecutando seeders...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->line(Artisan::output());
    }

    $this->newLine();
    $this->info('Listo. Login demo (si corrió seed): juancho123sc@gmail.com / 12345678');

    return 0;
})->purpose('Instala BD local con esquema SQL en español y datos de demo');
