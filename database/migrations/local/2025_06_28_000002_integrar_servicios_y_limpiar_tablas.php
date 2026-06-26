<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('consultas_medicas', 'servicio_id')) {
            Schema::table('consultas_medicas', function (Blueprint $table) {
                $table->foreignId('servicio_id')->nullable()->after('usuario_id')
                    ->constrained('servicios')->nullOnDelete();
            });
        }

        Schema::table('pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos', 'consulta_id')) {
                $table->foreignId('consulta_id')->nullable()->after('id')
                    ->constrained('consultas_medicas')->nullOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'servicio_id')) {
                $table->foreignId('servicio_id')->nullable()->after('consulta_id')
                    ->constrained('servicios')->nullOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->after('servicio_id')
                    ->constrained('clientes')->nullOnDelete();
            }
            if (! Schema::hasColumn('pagos', 'mascota_id')) {
                $table->foreignId('mascota_id')->nullable()->after('cliente_id')
                    ->constrained('mascotas')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('pagos', 'nota_venta_id')) {
            DB::statement('ALTER TABLE pagos MODIFY nota_venta_id BIGINT UNSIGNED NULL');
        }

        foreach (['sessions', 'cache_locks', 'cache', 'failed_jobs', 'job_batches', 'jobs'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }

    public function down(): void
    {
        Schema::table('consultas_medicas', function (Blueprint $table) {
            if (Schema::hasColumn('consultas_medicas', 'servicio_id')) {
                $table->dropForeign(['servicio_id']);
                $table->dropColumn('servicio_id');
            }
        });

        Schema::table('pagos', function (Blueprint $table) {
            foreach (['mascota_id', 'cliente_id', 'servicio_id', 'consulta_id'] as $col) {
                if (Schema::hasColumn('pagos', $col)) {
                    $table->dropForeign([$col]);
                    $table->dropColumn($col);
                }
            }
        });
    }
};
