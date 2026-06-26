<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->onDelete('cascade');
            $table->foreignId('sales_note_detail_id')
                ->constrained('sales_note_details')
                ->onDelete('cascade');
            $table->integer('quantity');    // negativo: consumo, positivo: reversa
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
};
