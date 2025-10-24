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
        Schema::create('ai_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // openrouter, openai, gemini, etc.
            $table->string('display_name'); // OpenRouter, OpenAI, Google Gemini, etc.
            $table->string('api_url');
            $table->string('default_model')->nullable();
            $table->json('config')->nullable(); // Additional configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(false);
            $table->integer('priority')->default(1); // Higher number = higher priority
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_services');
    }
};