<?php

namespace App\Providers;

use App\Listeners\RegistrarEventosAutenticacion;
use App\Models\Usuario;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::bind('user', fn (string $value) => Usuario::findOrFail($value));

        $this->asegurarBaseAuditoria();

        Event::listen(Login::class, [RegistrarEventosAutenticacion::class, 'handleLogin']);
        Event::listen(Failed::class, [RegistrarEventosAutenticacion::class, 'handleFailed']);
    }

    private function asegurarBaseAuditoria(): void
    {
        $dbPath = database_path('auditoria.sqlite');

        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        try {
            Artisan::call('migrate', [
                '--database' => 'auditoria',
                '--path' => 'database/migrations/2025_06_26_000001_create_auditoria_tables.php',
                '--force' => true,
            ]);
        } catch (\Throwable) {
            // La migración puede ejecutarse manualmente
        }
    }
}
