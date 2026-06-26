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
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 50);
            $table->unsignedSmallInteger('age');
            $table->string('photo_url', 255)->nullable();
            $table->timestamps();

            $table->foreignId('breed_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreignId('customer_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
