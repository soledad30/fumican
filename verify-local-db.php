<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Servicios\Cliente;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Servicio;
use App\Models\Usuario;
use App\Models\Usuarios\Permiso;
use App\Models\Usuarios\Rol;
use App\Models\Ventas\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$checks = [];

$checks['usar_bd_grupo'] = config('tablas.usar_bd_grupo') === false;
$checks['db_local'] = in_array(config('database.default'), ['sqlite', 'pgsql', 'mysql'], true);
$checks['usuarios'] = Usuario::count() >= 2;
$checks['roles'] = Rol::count() >= 4;
$checks['permisos'] = Permiso::count() >= 10;
$checks['clientes'] = Cliente::count() >= 5;
$checks['mascotas'] = Mascota::count() >= 5;
$checks['productos'] = Producto::count() >= 10;
$checks['servicios'] = Servicio::count() >= 5;

$demo = Usuario::where('email', 'juancho123sc@gmail.com')->first();
$checks['login_demo'] = $demo && Hash::check('12345678', $demo->password);
$checks['rol_propietario'] = $demo?->rol?->nombre === 'propietario';

try {
    $checks['auditoria_menus'] = DB::connection('auditoria')->table('menus')->count() >= 0;
    $checks['auditoria_bitacora'] = DB::connection('auditoria')->getSchemaBuilder()->hasTable('bitacora');
} catch (Throwable $e) {
    $checks['auditoria_menus'] = false;
    $checks['auditoria_bitacora'] = false;
}

$allOk = ! in_array(false, $checks, true);

foreach ($checks as $name => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ').$name.PHP_EOL;
}

echo PHP_EOL.($allOk ? 'BD local verificada correctamente.' : 'Hay fallos en la verificación.').PHP_EOL;

exit($allOk ? 0 : 1);
