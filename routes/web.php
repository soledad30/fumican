<?php

use App\Http\Controllers\Calidad\CalidadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Portal\PortalClienteController;
use App\Http\Controllers\Reportes\BitacoraController;
use App\Http\Controllers\Reportes\MatrizAccesoController;
use App\Http\Controllers\Reportes\ReporteController;
use App\Http\Controllers\Reservas\ReservaController;
use App\Http\Controllers\Servicios\ClienteController;
use App\Http\Controllers\Servicios\ConsultaMedicaController;
use App\Http\Controllers\Servicios\EspecieController;
use App\Http\Controllers\Servicios\MascotaController;
use App\Http\Controllers\Servicios\PagoController;
use App\Http\Controllers\Servicios\RecepcionController;
use App\Http\Controllers\Servicios\RazaController;
use App\Http\Controllers\Servicios\ServicioController;
use App\Http\Controllers\Servicios\VacunaController;
use App\Http\Controllers\Servicios\VeterinarioController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\Usuarios\RolController;
use App\Http\Controllers\Usuarios\UsuarioController;
use App\Http\Controllers\Ventas\AlmacenController;
use App\Http\Controllers\Ventas\CategoriaController;
use App\Http\Controllers\Ventas\NotaCompraController;
use App\Http\Controllers\Ventas\NotaVentaController;
use App\Http\Controllers\Ventas\ProductoController;
use App\Http\Controllers\Ventas\ProveedorController;
use App\Http\Controllers\WelcomeController;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('mi-cuenta')->as('portal.')->group(function () {
        Route::get('/', [PortalClienteController::class, 'index'])->name('index');
        Route::post('reservas', [PortalClienteController::class, 'storeReserva'])->name('reservas.store');
        Route::get('mascotas/{mascota}/carnet', [PortalClienteController::class, 'carnet'])->name('carnet');
        Route::get('mascotas/{mascota}/carnet/pdf', [PortalClienteController::class, 'carnetPdf'])->name('carnet.pdf');
    });

    // ─── Usuarios ───────────────────────────────────────────────────────────
    Route::prefix('usuarios')->middleware('permiso:administrar_sistema')->group(function () {
        Route::prefix('personal')->as('usuarios.')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('index');
            Route::get('buscar', [UsuarioController::class, 'search'])->name('search');
            Route::post('/', [UsuarioController::class, 'store'])->name('store');
            Route::put('{id}', [UsuarioController::class, 'update'])->name('update');
            Route::delete('{id}', [UsuarioController::class, 'destroy'])->name('destroy');
        });
        Route::prefix('roles')->as('roles.')->group(function () {
            Route::get('/', [RolController::class, 'index'])->name('index');
            Route::post('/', [RolController::class, 'store'])->name('store');
            Route::put('{id}', [RolController::class, 'update'])->name('update');
            Route::delete('{id}', [RolController::class, 'destroy'])->name('destroy');
        });
    });

    // ─── Servicios clínicos ─────────────────────────────────────────────────
    Route::prefix('servicios')->group(function () {

        Route::prefix('recepcion')->as('recepcion.')->middleware('permiso:crear_clientes')->group(function () {
            Route::get('/', [RecepcionController::class, 'index'])->name('index');
            Route::get('clientes', [RecepcionController::class, 'clientes'])->name('clientes');
            Route::get('clientes-sin-usuario', [RecepcionController::class, 'clientesSinUsuario'])->name('clientes-sin-usuario');
            Route::get('clientes/{cliente}', [RecepcionController::class, 'showCliente'])->name('clientes.show');
            Route::get('clientes/{cliente}/mascotas', [RecepcionController::class, 'mascotasCliente'])->name('clientes.mascotas');
            Route::get('razas', [RecepcionController::class, 'razas'])->name('razas');
            Route::post('preparar-datos', [RecepcionController::class, 'prepararDatos'])->name('preparar-datos');
            Route::post('clientes', [RecepcionController::class, 'storeCliente'])->name('clientes.store');
            Route::put('clientes/{customer}', [RecepcionController::class, 'updateCliente'])->name('clientes.update');
            Route::post('mascotas', [RecepcionController::class, 'storeMascota'])->name('mascotas.store');
            Route::put('mascotas/{pet}', [RecepcionController::class, 'updateMascota'])->name('mascotas.update');
            Route::post('usuario', [RecepcionController::class, 'storeUsuario'])->name('usuario.store');
        });

        Route::prefix('clientes')->as('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->middleware('permiso:listar_clientes')->name('index');
            Route::get('buscar', [ClienteController::class, 'search'])->middleware('permiso:listar_clientes')->name('search');
            Route::get('autocompletar', [ClienteController::class, 'autocomplete'])->middleware('permiso:listar_clientes')->name('autocomplete');
            Route::post('/', [ClienteController::class, 'store'])->middleware('permiso:crear_clientes')->name('store');
            Route::put('{customer}', [ClienteController::class, 'update'])->middleware('permiso:editar_clientes')->name('update');
            Route::delete('{customer}', [ClienteController::class, 'destroy'])->middleware('permiso:eliminar_clientes')->name('destroy');
        });

        Route::prefix('consultas-medicas')->as('consultas-medicas.')->group(function () {
            Route::get('/', [ConsultaMedicaController::class, 'index'])->middleware('permiso:listar_consultas')->name('index');
            Route::get('buscar', [ConsultaMedicaController::class, 'search'])->middleware('permiso:listar_consultas')->name('search');
            Route::get('reporte', [ConsultaMedicaController::class, 'generateConsultationsReport'])->middleware('permiso:listar_consultas')->name('report');
            Route::get('mascotas/{pet}/historial-reporte', [ConsultaMedicaController::class, 'generatePetHistoryReport'])->middleware('permiso:listar_consultas')->name('historial-reporte');
            Route::post('/', [ConsultaMedicaController::class, 'store'])->middleware('permiso:crear_consultas')->name('store');
            Route::match(['patch', 'post'], '{id}/estado', [ConsultaMedicaController::class, 'cambiarEstado'])
                ->whereNumber('id')
                ->middleware('permiso:editar_consultas')
                ->name('cambiar-estado');
            Route::put('{id}', [ConsultaMedicaController::class, 'update'])->whereNumber('id')->middleware('permiso:editar_consultas')->name('update');
            Route::delete('{id}', [ConsultaMedicaController::class, 'destroy'])->whereNumber('id')->middleware('permiso:eliminar_consultas')->name('destroy');
        });

        Route::prefix('tratamientos')->as('tratamientos.')->group(function () {
            Route::post('/', [TratamientoController::class, 'store'])->middleware('permiso:crear_consultas')->name('store');
            Route::put('{id}', [TratamientoController::class, 'update'])->middleware('permiso:editar_consultas')->name('update');
            Route::delete('{id}', [TratamientoController::class, 'destroy'])->middleware('permiso:eliminar_consultas')->name('destroy');
            Route::get('consulta/{consultaId}', [TratamientoController::class, 'porConsulta'])->middleware('permiso:listar_consultas')->name('por-consulta');
        });

        Route::get('especies/buscar', [EspecieController::class, 'search'])->middleware('permiso:listar_mascotas')->name('especies.search');
        Route::resource('especies', EspecieController::class)->middleware('permiso:editar_mascotas')->names('especies');

        Route::get('razas/buscar', [RazaController::class, 'search'])->middleware('permiso:listar_mascotas')->name('razas.search');
        Route::resource('razas', RazaController::class)->middleware('permiso:editar_mascotas')->names('razas');

        Route::prefix('veterinarios')->as('veterinarios.')->group(function () {
            Route::get('/', [VeterinarioController::class, 'index'])->middleware('permiso:listar_veterinarios')->name('index');
            Route::get('buscar', [VeterinarioController::class, 'search'])->middleware('permiso:listar_veterinarios')->name('search');
            Route::post('/', [VeterinarioController::class, 'store'])->middleware('permiso:crear_veterinarios')->name('store');
            Route::put('{veterinario}', [VeterinarioController::class, 'update'])->middleware('permiso:editar_veterinarios')->name('update');
            Route::delete('{veterinario}', [VeterinarioController::class, 'destroy'])->middleware('permiso:eliminar_veterinarios')->name('destroy');
        });

        Route::prefix('mascotas')->as('mascotas.')->group(function () {
            Route::get('/', [MascotaController::class, 'index'])->middleware('permiso:listar_mascotas')->name('index');
            Route::get('buscar', [MascotaController::class, 'search'])->middleware('permiso:listar_mascotas')->name('search');
            Route::post('/', [MascotaController::class, 'store'])->middleware('permiso:crear_mascotas')->name('store');
            Route::put('{pet}', [MascotaController::class, 'update'])->middleware('permiso:editar_mascotas')->name('update');
            Route::delete('{pet}', [MascotaController::class, 'destroy'])->middleware('permiso:eliminar_mascotas')->name('destroy');
            Route::get('autocompletar', [MascotaController::class, 'autocomplete'])->middleware('permiso:listar_mascotas')->name('autocomplete');
            Route::get('{pet}/historial', [MascotaController::class, 'historial'])->middleware('permiso:listar_mascotas')->name('historial');
            Route::get('{pet}/resumen-clinico', [MascotaController::class, 'resumenClinico'])->middleware('permiso:editar_consultas')->name('resumen-clinico');
            Route::post('preparar-datos', [MascotaController::class, 'prepareStoreData'])->middleware('permiso:crear_mascotas')->name('preparar-datos');
        });

        Route::prefix('catalogo')->as('servicios.')->group(function () {
            Route::get('/', [ServicioController::class, 'index'])->middleware('permiso:listar_servicios')->name('index');
            Route::get('buscar', [ServicioController::class, 'search'])->middleware('permiso:listar_servicios')->name('search');
            Route::post('/', [ServicioController::class, 'store'])->middleware('permiso:gestionar_servicios')->name('store');
            Route::put('{id}', [ServicioController::class, 'update'])->middleware('permiso:gestionar_servicios')->name('update');
            Route::delete('{id}', [ServicioController::class, 'destroy'])->middleware('permiso:gestionar_servicios')->name('destroy');
        });

        Route::prefix('vacunas')->as('vacunas.')->group(function () {
            Route::get('/', [VacunaController::class, 'index'])->middleware('permiso:listar_historial')->name('index');
            Route::get('buscar', [VacunaController::class, 'search'])->middleware('permiso:listar_historial')->name('search');
            Route::post('/', [VacunaController::class, 'store'])->middleware('permiso:gestionar_historial')->name('store');
            Route::put('{id}', [VacunaController::class, 'update'])->middleware('permiso:gestionar_historial')->name('update');
            Route::delete('{id}', [VacunaController::class, 'destroy'])->middleware('permiso:gestionar_historial')->name('destroy');
            Route::post('historial', [VacunaController::class, 'storeHistorial'])->middleware('permiso:gestionar_historial')->name('historial.store');
            Route::put('historial/{id}', [VacunaController::class, 'updateHistorial'])->middleware('permiso:gestionar_historial')->name('historial.update');
            Route::delete('historial/{id}', [VacunaController::class, 'destroyHistorial'])->middleware('permiso:gestionar_historial')->name('historial.destroy');
        });

        Route::prefix('pagos')->as('pagos.')->group(function () {
            Route::get('/', [PagoController::class, 'index'])->middleware('permiso:listar_pagos')->name('index');
            Route::get('buscar', [PagoController::class, 'search'])->middleware('permiso:listar_pagos')->name('search');
            Route::post('/', [PagoController::class, 'store'])->middleware('permiso:gestionar_pagos')->name('store');
            Route::put('{id}', [PagoController::class, 'update'])->middleware('permiso:gestionar_pagos')->name('update');
            Route::delete('{id}', [PagoController::class, 'destroy'])->middleware('permiso:gestionar_pagos')->name('destroy');
            Route::post('cuotas/{cuotaId}/pagar', [PagoController::class, 'pagarCuota'])->middleware('permiso:gestionar_pagos')->name('cuotas.pagar');
        });
    });

    // ─── Ventas e inventario ────────────────────────────────────────────────
    Route::prefix('ventas')->group(function () {

        Route::prefix('proveedores')->as('proveedores.')->group(function () {
            Route::get('/', [ProveedorController::class, 'index'])->middleware('permiso:listar_compras')->name('index');
            Route::post('/', [ProveedorController::class, 'store'])->middleware('permiso:gestionar_compras')->name('store');
            Route::put('{id}', [ProveedorController::class, 'update'])->middleware('permiso:gestionar_compras')->name('update');
            Route::delete('{id}', [ProveedorController::class, 'destroy'])->middleware('permiso:gestionar_compras')->name('destroy');
            Route::get('buscar', [ProveedorController::class, 'search'])->middleware('permiso:listar_compras')->name('search');
        });

        Route::prefix('categorias')->as('categorias.')->group(function () {
            Route::get('/', [CategoriaController::class, 'index'])->middleware('permiso:listar_productos')->name('index');
            Route::post('/', [CategoriaController::class, 'store'])->middleware('permiso:gestionar_productos')->name('store');
            Route::put('{id}', [CategoriaController::class, 'update'])->middleware('permiso:gestionar_productos')->name('update');
            Route::delete('{id}', [CategoriaController::class, 'destroy'])->middleware('permiso:gestionar_productos')->name('destroy');
            Route::get('buscar', [CategoriaController::class, 'search'])->middleware('permiso:listar_productos')->name('search');
        });

        Route::prefix('productos')->as('productos.')->group(function () {
            Route::get('/', [ProductoController::class, 'index'])->middleware('permiso:listar_productos')->name('index');
            Route::post('/', [ProductoController::class, 'store'])->middleware('permiso:gestionar_productos')->name('store');
            Route::get('{id}/editar', [ProductoController::class, 'edit'])->middleware('permiso:listar_productos')->name('edit');
            Route::put('{id}', [ProductoController::class, 'update'])->middleware('permiso:gestionar_productos')->name('update');
            Route::delete('{id}', [ProductoController::class, 'destroy'])->middleware('permiso:gestionar_productos')->name('destroy');
            Route::get('buscar', [ProductoController::class, 'search'])->middleware('permiso:listar_productos')->name('search');
            Route::get('reporte', [ProductoController::class, 'generatePdf'])->middleware('permiso:listar_productos')->name('report');
        });

        Route::prefix('almacenes')->as('almacenes.')->group(function () {
            Route::get('/', [AlmacenController::class, 'index'])->middleware('permiso:listar_inventarios')->name('index');
            Route::post('/', [AlmacenController::class, 'store'])->middleware('permiso:gestionar_inventarios')->name('store');
            Route::put('{id}', [AlmacenController::class, 'update'])->middleware('permiso:gestionar_inventarios')->name('update');
            Route::post('{id}/eliminar', [AlmacenController::class, 'destroy'])->middleware('permiso:gestionar_inventarios')->name('destroy');
            Route::get('buscar', [AlmacenController::class, 'search'])->middleware('permiso:listar_inventarios')->name('search');
            Route::get('{id}', [AlmacenController::class, 'show'])->middleware('permiso:listar_inventarios')->name('show');
            Route::get('{warehouseId}/producto/{medicamentId}/inventario', [AlmacenController::class, 'showInventoryMedicament'])
                ->middleware('permiso:listar_inventarios')->name('inventario');
            Route::post('{warehouseId}/producto/{medicamentId}/inventario', [AlmacenController::class, 'storeInventory'])
                ->middleware('permiso:gestionar_inventarios')->name('inventario.store');
            Route::put('{warehouseId}/producto/{medicamentId}/inventario/{inventoryId}', [AlmacenController::class, 'updateInventory'])
                ->middleware('permiso:gestionar_inventarios')->name('inventario.update');
            Route::delete('{warehouseId}/producto/{medicamentId}/inventario/{inventoryId}', [AlmacenController::class, 'destroyInventory'])
                ->middleware('permiso:gestionar_inventarios')->name('inventario.destroy');
        });

        Route::prefix('notas-compra')->as('notas-compra.')->group(function () {
            Route::get('/', [NotaCompraController::class, 'index'])->middleware('permiso:listar_compras')->name('index');
            Route::get('crear', [NotaCompraController::class, 'create'])->middleware('permiso:gestionar_compras')->name('create');
            Route::post('/', [NotaCompraController::class, 'store'])->middleware('permiso:gestionar_compras')->name('store');
            Route::get('reporte', [NotaCompraController::class, 'report'])->middleware('permiso:listar_compras')->name('report');
            Route::get('{id}/pdf', [NotaCompraController::class, 'generatePdf'])->middleware('permiso:listar_compras')->name('pdf');
            Route::get('{id}/editar', [NotaCompraController::class, 'edit'])->middleware('permiso:gestionar_compras')->name('edit');
            Route::put('{id}', [NotaCompraController::class, 'update'])->middleware('permiso:gestionar_compras')->name('update');
            Route::get('{id}', [NotaCompraController::class, 'show'])->middleware('permiso:listar_compras')->name('show');
            Route::delete('{id}', [NotaCompraController::class, 'destroy'])->middleware('permiso:gestionar_compras')->name('destroy');
            Route::get('buscar', [NotaCompraController::class, 'search'])->middleware('permiso:listar_compras')->name('search');
        });

        Route::prefix('notas-venta')->as('notas-venta.')->group(function () {
            Route::get('/', [NotaVentaController::class, 'index'])->middleware('permiso:listar_ventas')->name('index');
            Route::get('crear', [NotaVentaController::class, 'create'])->middleware('permiso:gestionar_ventas')->name('create');
            Route::post('/', [NotaVentaController::class, 'store'])->middleware('permiso:gestionar_ventas')->name('store');
            Route::get('buscar', [NotaVentaController::class, 'search'])->middleware('permiso:listar_ventas')->name('search');
            Route::get('reporte', [NotaVentaController::class, 'report'])->middleware('permiso:listar_ventas')->name('report');
            Route::get('{id}/editar', [NotaVentaController::class, 'edit'])->middleware('permiso:gestionar_ventas')->name('edit');
            Route::put('{id}', [NotaVentaController::class, 'update'])->middleware('permiso:gestionar_ventas')->name('update');
            Route::get('{id}', [NotaVentaController::class, 'show'])->middleware('permiso:listar_ventas')->name('show');
            Route::get('{id}/pdf', [NotaVentaController::class, 'generatePdf'])->middleware('permiso:listar_ventas')->name('pdf');
            Route::delete('{id}', [NotaVentaController::class, 'destroy'])->middleware('permiso:gestionar_ventas')->name('destroy');
        });
    });

    Route::middleware('permiso:administrar_sistema')->group(function () {
        Route::get('/calidad/prompt', [CalidadController::class, 'index'])->name('calidad.prompt.index');
        Route::post('/calidad/generate', [CalidadController::class, 'generate'])->name('calidad.prompt.generate');
    });

    Route::prefix('reportes')->as('reportes.')->middleware('permiso:ver_reportes')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora');
        Route::get('matriz-acceso', [MatrizAccesoController::class, 'index'])
            ->name('matriz-acceso')
            ->middleware('permiso:administrar_sistema');
    });
});

Route::post('/reservas/pdf', [ReservaController::class, 'reservePdf'])->name('reservas.pdf');
Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    Route::get('/busqueda-global', [GlobalSearchController::class, 'search'])->name('busqueda.global');
});

Route::redirect('/users/users', '/usuarios/personal');
Route::redirect('/users/roles', '/usuarios/roles');
Route::redirect('/services/customers', '/servicios/clientes');
Route::redirect('/services/pets', '/servicios/mascotas');
Route::redirect('/services/medical-consultations', '/servicios/consultas-medicas');
Route::redirect('/services/servicios', '/servicios/catalogo');
Route::redirect('/services/vacunas', '/servicios/vacunas');
Route::redirect('/services/pagos', '/servicios/pagos');
Route::redirect('/sales/suppliers', '/ventas/proveedores');
Route::redirect('/ventas/categorias', '/ventas/productos');
Route::redirect('/sales/medicaments', '/ventas/productos');
Route::redirect('/sales/warehouses', '/ventas/almacenes');
Route::redirect('/sales/purchases', '/ventas/notas-compra');
Route::redirect('/sales/sales-note', '/ventas/notas-venta');
Route::redirect('/global-search', '/busqueda-global');
Route::redirect('/reserve-pdf', '/reservas/pdf');
