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
        Schema::create('business_activities', function (Blueprint $table) {
            // Clave primaria tipo string (no autoincremental)
            $table->string('code', 10)->primary();

            // FK al sector/rubro
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();

            $table->string('description', 255);
            $table->string('subject_to_vat', 5);
            $table->string('tax_category', 5);
            $table->string('available_online', 5);

            // Nota: No incluimos $table->timestamps() porque en el modelo
            // establecimos public $timestamps = false; al ser un catálogo estático.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_activities');
    }
};
