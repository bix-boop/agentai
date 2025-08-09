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
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('image')->nullable(); // Package image (256x256 recommended)
            $table->integer('credits'); // Number of credits in this package
            $table->integer('price_cents'); // Price in cents (e.g., $10.00 = 1000)
            $table->string('currency', 3)->default('USD');
            $table->integer('tier')->default(1); // VIP tier level
            $table->json('features')->nullable(); // Array of features included
            $table->json('ai_access')->nullable(); // Array of AI assistant IDs this package grants access to
            $table->boolean('is_popular')->default(false); // Highlight as popular choice
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('purchase_count')->default(0); // Track popularity
            $table->decimal('discount_percentage', 5, 2)->default(0.00); // Discount %
            $table->timestamp('sale_ends_at')->nullable(); // Limited time offers
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index(['tier', 'is_active']);
            $table->index('is_popular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
