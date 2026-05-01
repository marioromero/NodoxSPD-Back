<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('document_type'); // ej: 'privacy_policy', 'cookie_policy', 'arco_terms'
            $table->string('name'); // ej: 'Política de Privacidad Estándar'
            $table->integer('version'); // 1, 2, 3... (Versión interna del sistema)

            // Aquí va el texto base con variables (ej. Blade sintaxis: "La empresa {{ $company->name }}...")
            $table->longText('content');

            // (Opcional pero recomendado) Estructura JSON que define qué preguntas debe hacer el Wizard Front-end para esta versión
            $table->json('wizard_schema')->nullable();

            $table->boolean('is_active')->default(false); // Solo una versión activa por 'document_type'
            $table->timestamps();

            // Para asegurar que no haya dos plantillas del mismo tipo con la misma versión
            $table->unique(['document_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_templates');
    }
};
