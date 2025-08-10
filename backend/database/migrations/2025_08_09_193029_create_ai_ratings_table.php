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
        Schema::create('ai_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_assistant_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->between(1, 5); // 1-5 star rating
            $table->text('review')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'ai_assistant_id']); // One rating per user per AI
            $table->index(['ai_assistant_id', 'rating']);
            $table->index(['is_public', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_ratings');
    }
};