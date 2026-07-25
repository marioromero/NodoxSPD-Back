<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hace person_id nullable (para permitir identity stitching antes de que
     * exista un registro en la tabla persons) y reemplaza el unique key
     * uq_person_visitor por uno basado en external_ref_hash + visitor_uuid,
     * que es la clave de deduplicación real del motor de stitching.
     */
    public function up(): void
    {
        Schema::table('person_visitor_uuids', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->nullable()->change();

            $table->dropUnique('uq_person_visitor');

            $table->unique(['external_ref_hash', 'visitor_uuid'], 'uq_external_ref_visitor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('person_visitor_uuids', function (Blueprint $table) {
            $table->dropUnique('uq_external_ref_visitor');

            $table->unique(['person_id', 'visitor_uuid'], 'uq_person_visitor');

            $table->unsignedBigInteger('person_id')->nullable(false)->change();
        });
    }
};
