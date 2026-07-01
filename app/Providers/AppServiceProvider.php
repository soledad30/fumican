<?php

namespace App\Providers;

use App\Listeners\RegistrarEventosAutenticacion;
use App\Models\Usuario;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use App\Support\InstalarEsquemaSql;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale(config('app.locale', 'es'));

        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
        }

        Route::bind('user', fn (string $value) => Usuario::findOrFail($value));

        if (config('database.default') === 'pgsql') {
            $conn = config('database.default');
            $faltan = ! Schema::connection($conn)->hasTable('bitacora')
                || ! Schema::connection($conn)->hasTable('cuotas_credito')
                || ! Schema::connection($conn)->hasTable('movimientos_inventario');

            if ($faltan) {
                InstalarEsquemaSql::aplicar($conn, 'clinica_veterinaria_auditoria_pg.sql');
            }

            if (! Schema::connection($conn)->hasColumn('movimientos_inventario', 'detalle_nota_venta_id')) {
                InstalarEsquemaSql::aplicar($conn, 'clinica_veterinaria_relaciones.sql');
            }
        }

        Event::listen(Login::class, [RegistrarEventosAutenticacion::class, 'handleLogin']);
        Event::listen(Failed::class, [RegistrarEventosAutenticacion::class, 'handleFailed']);
    }
}
