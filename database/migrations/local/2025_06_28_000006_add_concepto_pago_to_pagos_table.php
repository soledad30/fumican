<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pagos', 'concepto_pago')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->string('concepto_pago', 30)->nullable()->after('tipo_pago');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'concepto_pago')) {
                $table->dropColumn('concepto_pago');
            }
        });
    }
};
