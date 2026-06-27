<?php

namespace App\Providers;

use App\Listeners\RegistrarEventosAutenticacion;
use App\Models\Usuario;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Support\InstalarEsquemaSql;
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

        InstalarEsquemaSql::aplicarSiFalta('auditoria', 'auditoria.sql', 'bitacora');
    }
}
