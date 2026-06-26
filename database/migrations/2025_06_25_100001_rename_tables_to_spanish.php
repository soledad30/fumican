<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $renames = [
        'customers' => 'clientes',
        'species' => 'especies',
        'breeds' => 'razas',
        'pets' => 'mascotas',
        'medical_consultations' => 'consultas_medicas',
        'suppliers' => 'proveedores',
        'warehouses' => 'almacenes',
        'categories' => 'categorias',
        'medicaments' => 'medicamentos',
        'purchase_notes' => 'notas_compra',
        'purchase_note_details' => 'detalle_notas_compra',
        'sales_notes' => 'notas_venta',
        'sales_note_details' => 'detalle_notas_venta',
        'inventories' => 'inventarios',
        'stock_movements' => 'movimientos_stock',
        'visits' => 'visitas',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->renames as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse($this->renames, true) as $from => $to) {
            if (Schema::hasTable($to) && ! Schema::hasTable($from)) {
                Schema::rename($to, $from);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};
