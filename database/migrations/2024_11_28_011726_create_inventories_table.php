<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->integer('stock');
            $table->double('price');
            $table->unsignedBigInteger('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->unsignedBigInteger('medicament_id');
            $table->foreign('medicament_id')->references('id')->on('medicaments');
            $table->unsignedBigInteger('purchase_note_detail_id')->nullable();
            $table->foreign('purchase_note_detail_id')->references('id')->on('purchase_note_details');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
