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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Setting identifier (e.g., 'openai_api_key', 'site_name')
            $table->text('value')->nullable(); // Setting value (can be JSON for complex settings)
            $table->string('type')->default('string'); // string, integer, boolean, json, file
            $table->string('group')->default('general'); // Group settings (general, api, payment, etc.)
            $table->string('label'); // Human-readable label
            $table->text('description')->nullable(); // Help text
            $table->boolean('is_encrypted')->default(false); // Encrypt sensitive values
            $table->boolean('is_public')->default(false); // Can be accessed by frontend
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('options')->nullable(); // For select/radio inputs
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['group', 'sort_order']);
            $table->index('key');
            $table->index(['is_public', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
