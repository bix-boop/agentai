<?php

namespace App\Services;

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\Transaction;
use App\Models\Analytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Carbon\Carbon;

class PaymentService
{
    public function __construct()
    {
        // Initialize Stripe
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripePaymentIntent(User $user, CreditPackage $package): array
    {
        if (!config('services.stripe.secret')) {
            throw new \Exception('Stripe is not configured');
        }

        try {
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'type' => 'purchase',
                'amount_cents' => $package->price_cents,
                'currency' => $package->currency,
                'credits' => $package->credits,
                'payment_method' => 'stripe',
                'status' => 'pending',
                'description' => "Purchase of {$package->name}",
            ]);

            // Create Stripe payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $package->price_cents,
                'currency' => strtolower($package->currency),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                'description' => "Phoenix AI - {$package->name}",
            ]);

            // Update transaction with Stripe data
            $transaction->update([
                'payment_data' => [
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                ]
            ]);

            return [
                'transaction_id' => $transaction->id,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $package->price_cents,
                'currency' => $package->currency,
            ];

        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirm Stripe payment
     */
    public function confirmStripePayment(string $paymentIntentId): Transaction
    {
        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status !== 'succeeded') {
                throw new \Exception('Payment not completed');
            }

            // Find transaction
            $transaction = Transaction::where('payment_data->stripe_payment_intent_id', $paymentIntentId)
                ->where('status', 'pending')
                ->firstOrFail();

            return $this->completeTransaction($transaction, [
                'stripe_payment_intent' => $paymentIntent->toArray(),
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe payment confirmation failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(array $payload, string $signature): void
    {
        $endpoint_secret = config('services.stripe.webhook_secret');
        
        if (!$endpoint_secret) {
            throw new \Exception('Stripe webhook secret not configured');
        }

        try {
            $event = Webhook::constructEvent(
                json_encode($payload),
                $signature,
                $endpoint_secret
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleStripePaymentSucceeded($event->data->object);
                    break;
                
                case 'payment_intent.payment_failed':
                    $this->handleStripePaymentFailed($event->data->object);
                    break;
                
                default:
                    Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder(User $user, CreditPackage $package): array
    {
        if (!config('services.paypal.client_id')) {
            throw new \Exception('PayPal is not configured');
        }

        try {
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'type' => 'purchase',
                'amount_cents' => $package->price_cents,
                'currency' => $package->currency,
                'credits' => $package->credits,
                'payment_method' => 'paypal',
                'status' => 'pending',
                'description' => "Purchase of {$package->name}",
            ]);

            // Create PayPal order
            $paypalOrder = $this->createPayPalOrderRequest($package, $transaction);

            // Update transaction with PayPal data
            $transaction->update([
                'payment_data' => [
                    'paypal_order_id' => $paypalOrder['id'],
                    'paypal_status' => $paypalOrder['status'],
                ]
            ]);

            return [
                'transaction_id' => $transaction->id,
                'order_id' => $paypalOrder['id'],
                'approval_url' => $this->getPayPalApprovalUrl($paypalOrder),
            ];

        } catch (\Exception $e) {
            Log::error('PayPal order creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment(string $orderId): Transaction
    {
        try {
            // Find transaction
            $transaction = Transaction::where('payment_data->paypal_order_id', $orderId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Capture PayPal order
            $captureResult = $this->capturePayPalOrderRequest($orderId);

            return $this->completeTransaction($transaction, [
                'paypal_capture' => $captureResult,
                'paypal_capture_id' => $captureResult['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal payment capture failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create bank deposit transaction
     */
    public function createBankDepositTransaction(User $user, CreditPackage $package, array $depositInfo): Transaction
    {
        try {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'transaction_id' => 'bank_' . uniqid(),
                'type' => 'purchase',
                'price_cents' => $package->price_cents,
                'currency' => $package->currency,
                'credits_amount' => $package->credits,
                'payment_method' => 'bank_deposit',
                'status' => 'pending',
                'notes' => "Bank deposit for {$package->name}",
                'gateway_response' => array_merge($depositInfo, [
                    'submitted_at' => now()->toISOString(),
                    'requires_approval' => true,
                ]),
            ]);

            // Record analytics
            Analytics::record('bank_deposits_submitted');

            Log::info('Bank deposit transaction created', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'package_id' => $package->id,
                'amount' => $package->price_cents,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            Log::error('Bank deposit transaction creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve bank deposit
     */
    public function approveBankDeposit(Transaction $transaction, string $adminNote = ''): Transaction
    {
        if ($transaction->payment_method !== 'bank_deposit') {
            throw new \Exception('Transaction is not a bank deposit');
        }

        if ($transaction->status !== 'pending') {
            throw new \Exception('Transaction is not pending approval');
        }

        try {
            return $this->completeTransaction($transaction, [
                'admin_approval' => [
                    'approved_by' => auth()->id(),
                    'approved_at' => now()->toISOString(),
                    'admin_note' => $adminNote,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bank deposit approval failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject bank deposit
     */
    public function rejectBankDeposit(Transaction $transaction, string $rejectionReason): Transaction
    {
        if ($transaction->payment_method !== 'bank_deposit') {
            throw new \Exception('Transaction is not a bank deposit');
        }

        if ($transaction->status !== 'pending') {
            throw new \Exception('Transaction is not pending approval');
        }

        try {
            $transaction->update([
                'status' => 'failed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'admin_rejection' => [
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now()->toISOString(),
                        'rejection_reason' => $rejectionReason,
                    ]
                ]),
            ]);

            Log::info('Bank deposit rejected', [
                'transaction_id' => $transaction->id,
                'reason' => $rejectionReason,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            Log::error('Bank deposit rejection failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Transaction $transaction, int $refundAmountCents): Transaction
    {
        if ($transaction->status !== 'completed') {
            throw new \Exception('Can only refund completed transactions');
        }

        if ($refundAmountCents > $transaction->amount_cents) {
            throw new \Exception('Refund amount cannot exceed original transaction amount');
        }

        try {
            DB::beginTransaction();

            // Process refund based on payment method
            $refundData = [];
            
            switch ($transaction->payment_method) {
                case 'stripe':
                    $refundData = $this->processStripeRefund($transaction, $refundAmountCents);
                    break;
                
                case 'paypal':
                    $refundData = $this->processPayPalRefund($transaction, $refundAmountCents);
                    break;
                
                case 'bank_deposit':
                    $refundData = ['manual_refund' => true, 'requires_manual_processing' => true];
                    break;
            }

            // Deduct credits from user if they still have them
            $creditsToDeduct = min($transaction->credits, $transaction->user->credits_balance);
            if ($creditsToDeduct > 0) {
                $transaction->user->decrement('credits_balance', $creditsToDeduct);
                $transaction->user->decrement('total_credits_purchased', $creditsToDeduct);
            }

            // Update transaction
            $transaction->update([
                'status' => 'refunded',
                'refunded_amount_cents' => $refundAmountCents,
                'refunded_at' => now(),
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'refund' => array_merge($refundData, [
                        'refunded_by' => auth()->id(),
                        'refunded_at' => now()->toISOString(),
                        'refund_amount_cents' => $refundAmountCents,
                        'credits_deducted' => $creditsToDeduct,
                    ])
                ]),
            ]);

            // Record analytics
            Analytics::record('refunds_processed', 1);
            Analytics::record('revenue_refunded', $refundAmountCents);

            DB::commit();

            Log::info('Refund processed successfully', [
                'transaction_id' => $transaction->id,
                'refund_amount' => $refundAmountCents,
                'credits_deducted' => $creditsToDeduct,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund processing failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $stats = [
            'total_revenue' => Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_cents'),
            
            'total_transactions' => Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            
            'completed_transactions' => Transaction::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            
            'pending_transactions' => Transaction::where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            
            'failed_transactions' => Transaction::where('status', 'failed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
        ];

        // Payment method breakdown
        $paymentMethods = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount_cents) as total_amount')
            ->get()
            ->keyBy('payment_method');

        $stats['payment_methods'] = $paymentMethods;

        // Daily revenue trend
        $dailyRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as revenue')
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date');

        $stats['daily_revenue'] = $dailyRevenue;

        // Top packages
        $topPackages = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('creditPackage:id,name')
            ->groupBy('credit_package_id')
            ->selectRaw('credit_package_id, COUNT(*) as purchases, SUM(amount_cents) as revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $stats['top_packages'] = $topPackages;

        return $stats;
    }

    /**
     * Get bank deposit instructions
     */
    public function getBankDepositInstructions(): array
    {
        return [
            'bank_name' => config('payments.bank.name', 'Phoenix AI Bank'),
            'account_name' => config('payments.bank.account_name', 'Phoenix AI Ltd'),
            'account_number' => config('payments.bank.account_number', '1234567890'),
            'routing_number' => config('payments.bank.routing_number', '987654321'),
            'swift_code' => config('payments.bank.swift_code', 'PHXAIBANK'),
            'instructions' => [
                'Include your user ID or email in the transfer reference',
                'Deposits are processed within 24 hours during business days',
                'Keep your deposit receipt for verification',
                'Contact support if your deposit is not processed within 48 hours',
            ],
            'processing_time' => '24 hours',
            'business_hours' => 'Monday to Friday, 9 AM to 5 PM UTC',
        ];
    }

    /**
     * Complete a transaction and add credits to user
     */
    private function completeTransaction(Transaction $transaction, array $additionalPaymentData = []): Transaction
    {
        try {
            DB::beginTransaction();

            // Update transaction status
            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
                'payment_data' => array_merge($transaction->payment_data ?? [], $additionalPaymentData),
            ]);

            // Add credits to user
            $user = $transaction->user;
            $user->addCredits($transaction->credits, "Purchase: {$transaction->creditPackage->name}");

            // Upgrade user tier if package includes tier upgrade
            if ($transaction->creditPackage->tier_upgrade) {
                $user->upgradeTier(
                    $transaction->creditPackage->tier_upgrade,
                    $transaction->creditPackage->tier_duration ? 
                        now()->addDays($transaction->creditPackage->tier_duration) : null
                );
            }

            // Record analytics
            Analytics::record('transactions_completed');
            Analytics::record('revenue_generated', $transaction->amount_cents);
            Analytics::record('credits_sold', $transaction->credits);

            DB::commit();

            Log::info('Transaction completed successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'credits_added' => $transaction->credits,
                'amount' => $transaction->amount_cents,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction completion failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle successful Stripe payment
     */
    private function handleStripePaymentSucceeded($paymentIntent): void
    {
        $transaction = Transaction::where('payment_data->stripe_payment_intent_id', $paymentIntent->id)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            $this->completeTransaction($transaction, [
                'stripe_webhook_processed' => true,
                'stripe_payment_intent' => $paymentIntent->toArray(),
            ]);
        }
    }

    /**
     * Handle failed Stripe payment
     */
    private function handleStripePaymentFailed($paymentIntent): void
    {
        $transaction = Transaction::where('payment_data->stripe_payment_intent_id', $paymentIntent->id)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'payment_data' => array_merge($transaction->payment_data ?? [], [
                    'stripe_failure' => [
                        'last_payment_error' => $paymentIntent->last_payment_error,
                        'failed_at' => now()->toISOString(),
                    ]
                ]),
            ]);

            Log::info('Stripe payment failed', [
                'transaction_id' => $transaction->id,
                'error' => $paymentIntent->last_payment_error,
            ]);
        }
    }

    /**
     * Create PayPal order request
     */
    private function createPayPalOrderRequest(CreditPackage $package, Transaction $transaction): array
    {
        $accessToken = $this->getPayPalAccessToken();
        
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => "TRANSACTION_{$transaction->id}",
                'amount' => [
                    'currency_code' => $package->currency,
                    'value' => number_format($package->price_cents / 100, 2, '.', ''),
                ],
                'description' => "Phoenix AI - {$package->name}",
            ]],
            'application_context' => [
                'return_url' => config('app.url') . '/payments/paypal/success',
                'cancel_url' => config('app.url') . '/payments/paypal/cancel',
                'brand_name' => 'Phoenix AI',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = $this->makePayPalRequest('/v2/checkout/orders', $orderData, $accessToken);
        
        if (!$response || !isset($response['id'])) {
            throw new \Exception('Failed to create PayPal order');
        }

        return $response;
    }

    /**
     * Capture PayPal order request
     */
    private function capturePayPalOrderRequest(string $orderId): array
    {
        $accessToken = $this->getPayPalAccessToken();
        
        $response = $this->makePayPalRequest("/v2/checkout/orders/{$orderId}/capture", [], $accessToken);
        
        if (!$response || $response['status'] !== 'COMPLETED') {
            throw new \Exception('Failed to capture PayPal order');
        }

        return $response;
    }

    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');
        $baseUrl = config('services.paypal.mode') === 'live' 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get PayPal access token');
        }

        $data = json_decode($response, true);
        return $data['access_token'];
    }

    /**
     * Make PayPal API request
     */
    private function makePayPalRequest(string $endpoint, array $data, string $accessToken): array
    {
        $baseUrl = config('services.paypal.mode') === 'live' 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => !empty($data),
            CURLOPT_POSTFIELDS => !empty($data) ? json_encode($data) : null,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("PayPal API request failed with HTTP {$httpCode}: {$response}");
        }

        return json_decode($response, true);
    }

    /**
     * Get PayPal approval URL from order response
     */
    private function getPayPalApprovalUrl(array $order): string
    {
        foreach ($order['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        
        throw new \Exception('PayPal approval URL not found');
    }

    /**
     * Process Stripe refund
     */
    private function processStripeRefund(Transaction $transaction, int $refundAmountCents): array
    {
        if (!config('services.stripe.secret')) {
            throw new \Exception('Stripe is not configured');
        }

        $paymentIntentId = $transaction->payment_data['stripe_payment_intent_id'] ?? null;
        if (!$paymentIntentId) {
            throw new \Exception('Stripe payment intent ID not found');
        }

        $refund = \Stripe\Refund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => $refundAmountCents,
            'reason' => 'requested_by_customer',
            'metadata' => [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
            ],
        ]);

        return [
            'stripe_refund_id' => $refund->id,
            'stripe_refund_status' => $refund->status,
        ];
    }

    /**
     * Process PayPal refund
     */
    private function processPayPalRefund(Transaction $transaction, int $refundAmountCents): array
    {
        $captureId = $transaction->payment_data['paypal_capture_id'] ?? null;
        if (!$captureId) {
            throw new \Exception('PayPal capture ID not found');
        }

        $accessToken = $this->getPayPalAccessToken();
        
        $refundData = [
            'amount' => [
                'currency_code' => $transaction->currency,
                'value' => number_format($refundAmountCents / 100, 2, '.', ''),
            ],
            'note_to_payer' => 'Refund for Phoenix AI credits',
        ];

        $response = $this->makePayPalRequest("/v2/payments/captures/{$captureId}/refund", $refundData, $accessToken);

        return [
            'paypal_refund_id' => $response['id'],
            'paypal_refund_status' => $response['status'],
        ];
    }
}