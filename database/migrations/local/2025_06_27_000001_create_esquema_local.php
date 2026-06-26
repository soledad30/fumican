<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema completo en español para desarrollo local (MySQL / SQLite).
 * No usar contra db_grupo23sa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('esta_activo')->default(true);
            $table->foreignId('rol_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre', 80);
            $table->string('apellido', 80)->nullable();
            $table->string('ci', 20)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('genero', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('especies', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('razas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->foreignId('especie_id')->constrained('especies')->cascadeOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('mascotas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->decimal('peso', 8, 2)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('genero', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('url_foto')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('raza_id')->nullable()->constrained('razas')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->boolean('esta_activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('consultas_medicas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('motivo')->nullable();
            $table->text('diagnostico')->nullable();
            $table->decimal('costo_consulta', 10, 2)->nullable();
            $table->string('estado', 30)->default('completada');
            $table->foreignId('mascota_id')->constrained('mascotas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->unsignedInteger('duracion_dias')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('historial_vacunacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mascota_id')->constrained('mascotas')->cascadeOnDelete();
            $table->foreignId('vacuna_id')->constrained('vacunas')->cascadeOnDelete();
            $table->date('fecha_aplicacion');
            $table->date('fecha_proxima')->nullable();
            $table->foreignId('aplicado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('dosificacion')->nullable();
            $table->string('fabricante')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('sustancia_controlada')->default(false);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_medica_id')->constrained('consultas_medicas')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->unsignedInteger('cantidad')->nullable();
            $table->text('instrucciones_dosis')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('pais', 80)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ubicacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('notas_compra', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_compra')->nullable();
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('detalles_nota_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->foreignId('nota_compra_id')->constrained('notas_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('notas_venta', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_venta')->nullable();
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->string('estado', 30)->default('completada');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas_medicas')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('detalles_nota_venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->foreignId('nota_venta_id')->constrained('notas_venta')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->timestamp('creado_en')->nullable();
        });

        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('precio', 12, 2)->default(0);
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('detalle_nota_compra_id')->nullable()->constrained('detalles_nota_compra')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas_medicas')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('mascota_id')->nullable()->constrained('mascotas')->nullOnDelete();
            $table->dateTime('fecha_pago')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('metodo_pago', 50)->default('efectivo');
            $table->string('tipo_pago', 30)->default('contado');
            $table->string('id_transaccion_externa', 100)->nullable();
            $table->foreignId('nota_venta_id')->nullable()->constrained('notas_venta')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('creado_en')->nullable();
        });
    }

    public function down(): void
    {
        $tables = [
            'pagos', 'inventarios', 'detalles_nota_venta', 'notas_venta',
            'detalles_nota_compra', 'notas_compra', 'almacenes', 'proveedores',
            'productos', 'categorias', 'historial_vacunacion', 'vacunas',
            'servicios', 'tratamientos', 'consultas_medicas', 'mascotas', 'razas',
            'especies', 'clientes', 'personal_access_tokens',
            'password_reset_tokens', 'usuarios', 'roles_permisos', 'permisos', 'roles',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
};
