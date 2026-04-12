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
            $table->string('codigo', 10)->primary();

            $table->string('rubro', 150);
            $table->string('descripcion', 255);
            $table->string('afecto_iva', 5);
            $table->string('categoria_tributaria', 5);
            $table->string('disponible_internet', 5);

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
