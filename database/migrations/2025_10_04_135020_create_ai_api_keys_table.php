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
        Schema::create('ai_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_service_id')->constrained('ai_services')->onDelete('cascade');
            $table->string('name'); // Key identifier/description
            $table->string('api_key'); // The actual API key
            $table->integer('usage_count')->default(0); // How many times this key has been used
            $table->integer('max_usage_per_day')->nullable(); // Daily usage limit if any
            $table->integer('current_daily_usage')->default(0); // Current day usage
            $table->date('last_usage_date')->nullable(); // Last date this key was used
            $table->datetime('last_used_at')->nullable(); // Last timestamp this key was used
            $table->json('usage_stats')->nullable(); // Additional usage statistics
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->integer('priority')->default(1); // Higher number = higher priority
            $table->timestamps();

            $table->index(['ai_service_id', 'is_active', 'is_available']);
            $table->index(['ai_service_id', 'usage_count']);
            $table->index(['last_usage_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_api_keys');
    }
};