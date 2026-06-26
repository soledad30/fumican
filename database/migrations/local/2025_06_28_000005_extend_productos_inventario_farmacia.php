<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('unidad_medida', 30)->default('unidad')->after('nombre');
            $table->string('presentacion', 120)->nullable()->after('unidad_medida');
            $table->unsignedInteger('stock_minimo')->default(0)->after('categoria_id');
            $table->decimal('precio_venta_referencia', 12, 2)->nullable()->after('stock_minimo');
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->decimal('precio_compra', 12, 2)->default(0)->after('stock');
            $table->date('fecha_vencimiento')->nullable()->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn(['precio_compra', 'fecha_vencimiento']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidad_medida', 'presentacion', 'stock_minimo', 'precio_venta_referencia']);
        });
    }
};
