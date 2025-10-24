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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade');
            $table->string('provider'); // telegram, bale, rubika
            $table->string('external_message_id')->nullable(); // شناسه پیام در پیام‌رسان
            $table->string('message_type'); // text, photo, video, file, ...
            $table->longText('content')->nullable();
            $table->string('file_id')->nullable();
            $table->string('file_url')->nullable();
            $table->string('ai_service')->default('openrouter'); // پیش‌فرض openrouter
            $table->string('ai_model')->nullable(); // مدل AI استفاده شده
            $table->longText('ai_response')->nullable(); // پاسخ AI
            $table->json('ai_usage')->nullable(); // اطلاعات استفاده از AI
            $table->longText('processing_error')->nullable(); // خطای پردازش
            $table->timestamp('processed_at')->nullable(); // زمان پردازش
            $table->json('extra')->nullable(); // اطلاعات اضافی
            $table->json('raw_payload'); // ذخیره کل پیام دریافتی
            $table->timestamps();
            
            // Index for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['group_id', 'created_at']);
            $table->index(['external_message_id', 'provider']);
            $table->index(['ai_service', 'created_at']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};