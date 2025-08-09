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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('credit_package_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_id')->unique(); // Unique transaction identifier
            $table->enum('type', ['purchase', 'refund', 'bonus', 'admin_adjustment']);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded']);
            $table->enum('payment_method', ['stripe', 'paypal', 'bank_deposit', 'admin', 'bonus']);
            
            // Amounts
            $table->integer('credits_amount'); // Credits purchased/refunded
            $table->integer('price_cents'); // Amount paid in cents
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount_applied', 5, 2)->default(0.00); // Discount percentage applied
            
            // Payment Gateway Data
            $table->string('gateway_transaction_id')->nullable(); // Stripe/PayPal transaction ID
            $table->json('gateway_response')->nullable(); // Full gateway response
            $table->string('payment_intent_id')->nullable(); // Stripe Payment Intent ID
            $table->string('invoice_url')->nullable(); // Link to invoice/receipt
            
            // Bank Deposit Specific
            $table->text('bank_reference')->nullable(); // Bank transfer reference
            $table->timestamp('bank_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->text('notes')->nullable(); // Admin notes
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_method', 'status']);
            $table->index('transaction_id');
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
