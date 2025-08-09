<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        try {
            // Log the webhook for debugging
            Log::info('Stripe webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Placeholder for Stripe webhook processing
            return response()->json([
                'success' => true,
                'message' => 'Stripe webhook processed'
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    public function paypal(Request $request)
    {
        try {
            // Log the webhook for debugging
            Log::info('PayPal webhook received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Placeholder for PayPal webhook processing
            return response()->json([
                'success' => true,
                'message' => 'PayPal webhook processed'
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal webhook failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }
}