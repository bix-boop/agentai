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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_assistant_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // Auto-generated or user-defined
            $table->json('settings')->nullable(); // Chat-specific settings (language, tone, etc.)
            $table->integer('message_count')->default(0);
            $table->integer('credits_used')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_archived']);
            $table->index(['ai_assistant_id', 'created_at']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
