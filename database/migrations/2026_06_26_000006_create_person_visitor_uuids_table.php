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
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('person_id');
            $table->string('external_ref_hash', 64)->index();
            $table->uuid('visitor_uuid');
            $table->timestamps();

            $table->unique(['person_id', 'visitor_uuid'], 'uq_person_visitor');
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
