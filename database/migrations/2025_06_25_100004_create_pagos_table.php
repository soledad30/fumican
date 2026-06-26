<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('mascota_id')->nullable()->constrained('mascotas')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('consultas_medicas')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago', 50)->default('efectivo');
            $table->string('estado', 30)->default('pendiente');
            $table->string('numero_transaccion', 100)->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
