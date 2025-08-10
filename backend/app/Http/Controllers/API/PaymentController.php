<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth:sanctum')->except(['packages', 'showPackage']);
        $this->paymentService = $paymentService;
    }

    /**
     * Get all credit packages (public endpoint)
     */
    public function packages(): JsonResponse
    {
        try {
            $packages = \App\Models\CreditPackage::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $packages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve credit packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific credit package (public endpoint)
     */
    public function showPackage(\App\Models\CreditPackage $creditPackage): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $creditPackage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve credit package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase a credit package (redirects to appropriate payment method)
     */
    public function purchasePackage(Request $request, \App\Models\CreditPackage $creditPackage): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:stripe,paypal,bank_deposit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $paymentMethod = $request->input('payment_method');
            
            switch ($paymentMethod) {
                case 'stripe':
                    return $this->createStripeIntent($request);
                case 'paypal':
                    return $this->createPayPalOrder($request);
                case 'bank_deposit':
                    return $this->createBankDeposit($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid payment method'
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripeIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_package_id' => 'required|exists:credit_packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $package = CreditPackage::findOrFail($request->credit_package_id);
            $user = Auth::user();

            if (!$package->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This credit package is not available',
                ], 400);
            }

            $result = $this->paymentService->createStripePaymentIntent($user, $package);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm Stripe payment
     */
    public function confirmStripePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = $this->paymentService->confirmStripePayment($request->payment_intent_id);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'data' => $transaction->load(['creditPackage', 'user:id,name,credits_balance']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_package_id' => 'required|exists:credit_packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $package = CreditPackage::findOrFail($request->credit_package_id);
            $user = Auth::user();

            if (!$package->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This credit package is not available',
                ], 400);
            }

            $result = $this->paymentService->createPayPalOrder($user, $package);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PayPal order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = $this->paymentService->capturePayPalPayment($request->order_id);

            return response()->json([
                'success' => true,
                'message' => 'PayPal payment captured successfully',
                'data' => $transaction->load(['creditPackage', 'user:id,name,credits_balance']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PayPal payment capture failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create bank deposit transaction
     */
    public function createBankDeposit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_package_id' => 'required|exists:credit_packages,id',
            'depositor_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'deposit_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $package = CreditPackage::findOrFail($request->credit_package_id);
            $user = Auth::user();

            if (!$package->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This credit package is not available',
                ], 400);
            }

            $depositInfo = [
                'depositor_name' => $request->depositor_name,
                'reference' => $request->reference,
                'deposit_date' => $request->deposit_date ?? now()->toDateString(),
            ];

            $transaction = $this->paymentService->createBankDepositTransaction($user, $package, $depositInfo);

            return response()->json([
                'success' => true,
                'message' => 'Bank deposit transaction created. Awaiting approval.',
                'data' => [
                    'transaction' => $transaction,
                    'bank_instructions' => $this->paymentService->getBankDepositInstructions(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bank deposit transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bank deposit instructions
     */
    public function getBankInstructions(): JsonResponse
    {
        try {
            $instructions = $this->paymentService->getBankDepositInstructions();

            return response()->json([
                'success' => true,
                'data' => $instructions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get bank instructions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's transaction history
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = Auth::user()->transactions()
            ->with(['creditPackage'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->payment_method, function ($query, $method) {
                $query->where('payment_method', $method);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Admin: Approve bank deposit
     */
    public function approveBankDeposit(Request $request, Transaction $transaction): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedTransaction = $this->paymentService->approveBankDeposit(
                $transaction,
                $request->admin_note ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Bank deposit approved successfully',
                'data' => $updatedTransaction->load(['creditPackage', 'user:id,name,credits_balance']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve bank deposit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Reject bank deposit
     */
    public function rejectBankDeposit(Request $request, Transaction $transaction): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedTransaction = $this->paymentService->rejectBankDeposit(
                $transaction,
                $request->rejection_reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Bank deposit rejected',
                'data' => $updatedTransaction,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject bank deposit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get payment statistics
     */
    public function paymentStats(Request $request): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        try {
            $days = $request->get('days', 30);
            $stats = $this->paymentService->getPaymentStats($days);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Process refund
     */
    public function processRefund(Request $request, Transaction $transaction): JsonResponse
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'refund_amount' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $refundAmount = $request->refund_amount ?? $transaction->amount_cents;
            $updatedTransaction = $this->paymentService->processRefund($transaction, $refundAmount);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $updatedTransaction,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}