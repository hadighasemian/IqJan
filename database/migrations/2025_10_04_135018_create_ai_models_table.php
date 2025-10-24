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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_service_id')->constrained('ai_services')->onDelete('cascade');
            $table->string('name'); // deepseek/deepseek-chat-v3.1:free
            $table->string('display_name'); // DeepSeek Chat v3.1
            $table->string('provider'); // deepseek, google, openai, etc.
            $table->enum('pricing_type', ['free', 'paid', 'limited'])->default('free');
            $table->decimal('cost_per_token', 10, 8)->nullable(); // Cost per token if paid
            $table->integer('max_tokens')->nullable(); // Maximum tokens for this model
            $table->json('capabilities')->nullable(); // What this model can do
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(1); // Higher number = higher priority
            $table->timestamps();

            $table->unique(['ai_service_id', 'name']);
            $table->index(['ai_service_id', 'is_active']);
            $table->index(['pricing_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};