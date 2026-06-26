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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->text('icon')->nullable();
            $table->string('link')->nullable();

            $table->foreignId('permission_id')->nullable()->constrained()
                ->onUpdate('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('menus')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
