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
        Schema::create('ai_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('expertise')->nullable(); // e.g., "Digital Marketing", "Legal Advice"
            $table->text('welcome_message');
            $table->string('avatar')->nullable(); // Profile image path
            
            // AI Configuration
            $table->text('system_prompt'); // Training instructions
            $table->decimal('temperature', 3, 2)->default(0.7); // 0.0 to 2.0
            $table->decimal('frequency_penalty', 3, 2)->default(0.0); // -2.0 to 2.0
            $table->decimal('presence_penalty', 3, 2)->default(0.0); // -2.0 to 2.0
            $table->integer('max_tokens')->default(1000);
            $table->string('model')->default('gpt-3.5-turbo');
            
            // Message Configuration
            $table->integer('min_message_length')->default(1);
            $table->integer('max_message_length')->default(500);
            $table->integer('conversation_memory')->default(10); // Number of previous messages to remember
            
            // Features
            $table->boolean('enable_voice')->default(false);
            $table->boolean('enable_image_generation')->default(false);
            $table->boolean('enable_web_search')->default(false);
            $table->json('supported_languages')->nullable(); // Array of language codes
            $table->json('response_tones')->nullable(); // Array of available tones
            $table->json('writing_styles')->nullable(); // Array of available styles
            
            // Access Control
            $table->boolean('is_public')->default(true);
            $table->json('required_packages')->nullable(); // Credit package IDs that can access this AI
            $table->integer('minimum_tier')->default(1);
            
            // Status & Analytics
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.0);
            $table->integer('total_ratings')->default(0);
            
            // Content Safety
            $table->boolean('content_filter_enabled')->default(true);
            $table->json('blocked_words')->nullable();
            
            $table->timestamps();
            
            $table->index(['is_active', 'is_public']);
            $table->index(['category_id', 'is_active']);
            $table->index('slug');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assistants');
    }
};
