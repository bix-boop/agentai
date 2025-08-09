<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['packages', 'showPackage']);
    }

    public function packages()
    {
        try {
            $packages = CreditPackage::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('price_cents')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $packages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showPackage(CreditPackage $creditPackage)
    {
        try {
            if (!$creditPackage->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not available'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $creditPackage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function purchasePackage(Request $request, CreditPackage $creditPackage)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|string|in:stripe,paypal,bank_deposit',
                'payment_data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$creditPackage->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package not available'
                ], 400);
            }

            $user = Auth::user();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'credit_package_id' => $creditPackage->id,
                'type' => 'purchase',
                'amount_cents' => $creditPackage->price_cents,
                'currency' => $creditPackage->currency,
                'credits' => $creditPackage->credits,
                'payment_method' => $request->payment_method,
                'payment_data' => $request->payment_data,
                'status' => 'pending',
                'description' => "Purchase of {$creditPackage->name}",
            ]);

            // For now, just mark as completed (implement actual payment processing later)
            $transaction->update(['status' => 'completed']);
            
            // Add credits to user
            $user->increment('credits_balance', $creditPackage->credits);

            return response()->json([
                'success' => true,
                'message' => 'Purchase completed successfully',
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $user->fresh()->credits_balance
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createStripeIntent(Request $request)
    {
        try {
            // Placeholder for Stripe integration
            return response()->json([
                'success' => true,
                'message' => 'Stripe integration not yet implemented',
                'data' => ['client_secret' => 'placeholder']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe payment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmStripePayment(Request $request)
    {
        try {
            // Placeholder for Stripe confirmation
            return response()->json([
                'success' => true,
                'message' => 'Stripe confirmation not yet implemented'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe confirmation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createPayPalOrder(Request $request)
    {
        try {
            // Placeholder for PayPal integration
            return response()->json([
                'success' => true,
                'message' => 'PayPal integration not yet implemented',
                'data' => ['order_id' => 'placeholder']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PayPal order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function capturePayPalOrder(Request $request)
    {
        try {
            // Placeholder for PayPal capture
            return response()->json([
                'success' => true,
                'message' => 'PayPal capture not yet implemented'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PayPal capture failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bankDeposit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'credit_package_id' => 'required|exists:credit_packages,id',
                'bank_reference' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $package = CreditPackage::findOrFail($request->credit_package_id);
            $user = Auth::user();

            // Create pending transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'type' => 'bank_deposit',
                'amount_cents' => $package->price_cents,
                'currency' => $package->currency,
                'credits' => $package->credits,
                'payment_method' => 'bank_deposit',
                'payment_data' => [
                    'bank_reference' => $request->bank_reference,
                    'amount' => $request->amount,
                    'notes' => $request->notes,
                ],
                'status' => 'pending',
                'description' => "Bank deposit for {$package->name}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank deposit request submitted successfully',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bank deposit submission failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function paymentHistory(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Transaction::where('user_id', $user->id);
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $transactions = $query->with(['creditPackage'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}