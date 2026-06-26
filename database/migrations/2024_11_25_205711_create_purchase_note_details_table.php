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
        Schema::create('purchase_note_details', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->double('purchase_price');
            $table->double('percentage');
            $table->double('subtotal');
            $table->unsignedBigInteger('purchase_note_id');
            $table->foreign('purchase_note_id')->references('id')->on('purchase_notes');
            $table->unsignedBigInteger('medicament_id');
            $table->foreign('medicament_id')->references('id')->on('medicaments');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_note_details');
    }
};
