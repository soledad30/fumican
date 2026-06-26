<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veterinarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nombre', 80);
            $table->string('apellido', 80)->nullable();
            $table->string('ci', 20)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('licencia', 50)->nullable();
            $table->boolean('es_especialista')->default(false);
            $table->string('especialidad', 100)->nullable();
            $table->boolean('esta_activo')->default(true);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veterinarios');
    }
};
