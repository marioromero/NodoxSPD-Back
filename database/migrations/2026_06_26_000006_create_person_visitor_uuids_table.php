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
        Schema::create('person_visitor_uuids', function (Blueprint $table) {
            // Llave primaria y relación con empresa
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Referencia a la persona (sin FK para mantener desacoplado con el módulo de personas)
            $table->unsignedBigInteger('person_id');

            // Hash del identificador externo que generó el visitor_uuid
            $table->string('external_ref_hash', 64)->index();

            // UUID del visitante anónimo asignado a esta persona por la empresa
            $table->uuid('visitor_uuid');
            $table->timestamps();

            // Unicidad: una persona no puede tener el mismo visitor_uuid duplicado
            $table->unique(['person_id', 'visitor_uuid'], 'uq_person_visitor');

            // Índice para buscar visitor_uuids por empresa
            $table->index(['company_id', 'visitor_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_visitor_uuids');
    }
};
