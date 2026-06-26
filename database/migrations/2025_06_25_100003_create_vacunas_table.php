<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->foreignId('mascota_id')->constrained('mascotas')->cascadeOnDelete();
            $table->foreignId('veterinario_id')->references('id')->on('users')->cascadeOnUpdate();
            $table->date('fecha_aplicacion');
            $table->date('proxima_dosis')->nullable();
            $table->string('lote', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunas');
    }
};
