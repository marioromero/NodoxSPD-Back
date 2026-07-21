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
        Schema::create('consent_purposes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 80)->unique();
            $table->string('category', 60)->index();
            $table->string('label');
            $table->text('description');
            $table->string('legal_basis', 60);
            $table->boolean('requires_consent');
            $table->boolean('default_value');
            $table->string('widget_action', 60)->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_purposes');
    }
};
