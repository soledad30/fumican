<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('fumican:install-local {--fresh : Borra y recrea la base de datos local}', function () {
    if (config('tablas.usar_bd_grupo', true)) {
        $this->warn('USAR_BD_GRUPO=true — configure USAR_BD_GRUPO=false en .env para seeders.');
        if (! $this->confirm('¿Continuar solo con migraciones?', false)) {
            return 1;
        }
    }

    $dbPath = database_path('database.sqlite');
    if (! File::exists($dbPath)) {
        File::put($dbPath, '');
        $this->info('Creado database/database.sqlite');
    }

    if ($this->option('fresh')) {
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/local',
            '--force' => true,
        ]);
        $this->line(Artisan::output());
    }

    $this->info('Migrando esquema local...');
    Artisan::call('migrate', [
        '--path' => 'database/migrations/local',
        '--force' => true,
    ]);
    $this->line(Artisan::output());

    if (! config('tablas.usar_bd_grupo', true)) {
        $this->info('Ejecutando seeders...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->line(Artisan::output());
    }

    $this->info('Migrando auditoría...');
    Artisan::call('migrate', [
        '--database' => 'auditoria',
        '--path' => 'database/migrations/2025_06_26_000001_create_auditoria_tables.php',
        '--force' => true,
    ]);
    $this->line(Artisan::output());

    $this->newLine();
    $this->info('Listo. Login: juancho123sc@gmail.com / 12345678');

    return 0;
})->purpose('Instala SQLite local con esquema en español y datos de demo');
