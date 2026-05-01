<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Relación a la plantilla exacta que se usó (Crucial para inmutabilidad)
            $table->foreignId('legal_template_id')->constrained()->restrictOnDelete();

            $table->string('document_type'); // Desnormalizado para búsquedas rápidas (ej: privacy_policy)

            // Versión de cara al cliente (Ej: La política de privacidad V3 de la Empresa X)
            $table->integer('company_version');

            // EL NÚCLEO: Las respuestas exactas del Wizard en el momento de la creación
            $table->json('wizard_data');

            // SELLO DE INTEGRIDAD: Un hash SHA-256 del documento final renderizado.
            // Si el cliente pide probar ante la Agencia que el documento no mutó, este hash es la prueba matemática.
            $table->string('integrity_hash', 64)->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Evitar duplicidad de versiones para un mismo documento en la misma empresa
            $table->unique(['company_id', 'document_type', 'company_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_policies');
    }
};
