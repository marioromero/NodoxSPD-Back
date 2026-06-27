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
        Schema::table('companies', function (Blueprint $table) {
            $table->json('allowed_domains')->nullable()->after('legal_settings');
            $table->string('integration_secret', 64)->nullable()->after('allowed_domains');
            $table->json('widget_config')->nullable()->after('integration_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['widget_config', 'integration_secret', 'allowed_domains']);
        });
    }
};
