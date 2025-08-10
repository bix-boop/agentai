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
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric'); // e.g., 'user_registrations', 'messages_sent', 'credits_purchased'
            $table->string('dimension')->nullable(); // e.g., AI assistant ID, category ID, user ID
            $table->bigInteger('value'); // The metric value
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamps();
            
            $table->unique(['date', 'metric', 'dimension']);
            $table->index(['date', 'metric']);
            $table->index(['metric', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};