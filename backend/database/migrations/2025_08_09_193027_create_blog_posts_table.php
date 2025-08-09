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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Author
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable(); // Array of additional images
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // Array of keywords
            
            // Organization
            $table->json('categories')->nullable(); // Array of category names
            $table->json('tags')->nullable(); // Array of tag names
            
            // Status
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            
            // Engagement
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->boolean('allow_comments')->default(true);
            
            // Features
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sticky')->default(false);
            $table->integer('reading_time')->nullable(); // Estimated reading time in minutes
            
            $table->timestamps();
            
            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'status']);
            $table->index(['is_featured', 'published_at']);
            $table->index('slug');
            $table->fullText(['title', 'content', 'excerpt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
