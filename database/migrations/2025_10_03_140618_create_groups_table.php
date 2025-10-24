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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('external_id'); // شناسه گروه در پیام‌رسان
            $table->string('provider'); // telegram, bale
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('invite_link')->nullable();
            $table->string('type'); // group, supergroup, channel
            $table->json('extra')->nullable();
            $table->timestamps();
            
            // Index for better performance
            $table->index(['external_id', 'provider']);
        });
        
        // Add foreign key constraint to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint first
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
        
        Schema::dropIfExists('groups');
    }
};
