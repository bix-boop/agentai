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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Profile Information
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('language', 5)->default('en');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('phone')->nullable();
            $table->string('country', 2)->nullable(); // ISO country code
            
            // Account Status
            $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            
            // Credits & Subscription
            $table->integer('credits_balance')->default(0);
            $table->integer('total_credits_purchased')->default(0);
            $table->integer('total_credits_used')->default(0);
            $table->integer('current_tier')->default(1); // VIP tier level
            $table->timestamp('tier_expires_at')->nullable();
            
            // Preferences
            $table->json('preferences')->nullable(); // User preferences (theme, notifications, etc.)
            $table->json('ai_settings')->nullable(); // Default AI settings (language, tone, etc.)
            $table->boolean('email_notifications')->default(true);
            $table->boolean('marketing_emails')->default(false);
            
            // Usage Statistics
            $table->integer('total_chats')->default(0);
            $table->integer('total_messages')->default(0);
            $table->integer('favorite_ai_assistants')->default(0);
            $table->timestamp('first_chat_at')->nullable();
            
            // Security
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->json('recovery_codes')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(['is_active', 'role']);
            $table->index(['credits_balance', 'current_tier']);
            $table->index(['email_verified_at', 'is_active']);
            $table->index('last_login_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
