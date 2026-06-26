<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'auditoria';

    public function up(): void
    {
        Schema::connection('auditoria')->create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('accion', 50);
            $table->string('modulo', 80);
            $table->text('descripcion')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });

        Schema::connection('auditoria')->create('visitas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 255)->unique();
            $table->unsignedBigInteger('contador')->default(0);
            $table->timestamp('ultima_visita')->nullable();
        });

        Schema::connection('auditoria')->create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('icono', 80)->nullable();
            $table->string('enlace', 255)->nullable();
            $table->string('permiso_bd', 80)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
        });

        Schema::connection('auditoria')->create('planes_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pago_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('nota_venta_id')->nullable();
            $table->decimal('monto_total', 12, 2);
            $table->unsignedTinyInteger('num_cuotas');
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        Schema::connection('auditoria')->create('cuotas_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_pago_id')->constrained('planes_pago')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->decimal('monto', 12, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('metodo_pago', 30)->nullable();
            $table->string('id_transaccion_externa', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('auditoria')->dropIfExists('cuotas_pago');
        Schema::connection('auditoria')->dropIfExists('planes_pago');
        Schema::connection('auditoria')->dropIfExists('menus');
        Schema::connection('auditoria')->dropIfExists('visitas');
        Schema::connection('auditoria')->dropIfExists('bitacora');
    }
};
