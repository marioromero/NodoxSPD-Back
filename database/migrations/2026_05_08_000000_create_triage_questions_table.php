<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_questions', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug');
            $table->string('key');
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('type', ['boolean', 'select', 'multiselect', 'text', 'number']);
            $table->json('options')->nullable();
            $table->json('required_condition')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module_slug', 'key']);
            $table->index('module_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_questions');
    }
};
