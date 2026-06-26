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
        Schema::create('medical_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->date('dewormed_at')->nullable();
            $table->string('previous_illnesses')->nullable();
            $table->string('previous_interventions')->nullable();
            $table->string('general_condition', 120);
            $table->decimal('weight', 8, 2, true);
            $table->string('appetite', 120);
            $table->string('hydratation', 120);
            $table->string('mucosa', 120);
            $table->string('digestive_system', 120)->nullable();
            $table->string('genitourinary_system', 120)->nullable();
            $table->string('respiratory_system', 120)->nullable();
            $table->decimal('temperature')->nullable();
            $table->decimal('heart_rate')->nullable();
            $table->decimal('respiratory_rate')->nullable();
            $table->string('clinical_observation')->nullable();
            $table->string('complementary_tests', 150)->nullable();
            $table->string('pronostic', 150)->nullable();
            $table->string('presumptive_diagnosis', 120)->nullable();
            $table->string('confirmatory_diagnosis', 120)->nullable();
            $table->string('treatment')->nullable();
            $table->decimal('consultation_fee', 8, 2)->nullable();
            $table->timestamps();

            $table->foreignId('pet_id')->constrained();
            $table->foreignId('veterinarian_id')->references('id')->on('users')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_consultations');
    }
};
