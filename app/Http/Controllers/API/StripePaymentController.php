<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);
        $user = Auth::user();

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Create or retrieve Stripe customer
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $order->total_price * 100, // in cents
                'currency' => 'egp',
                'customer' => $user->stripe_id,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ],
            ]);

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => 'stripe',
                'stripe_payment_id' => $paymentIntent->id,
                'stripe_customer_id' => $user->stripe_id,
                'amount' => $order->total_price,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $payment = Payment::findOrFail($request->payment_id);
        $user = Auth::user();

        if ($payment->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_id);

            if ($paymentIntent->status === 'succeeded') {
                $payment->status = 'paid';
                $payment->save();

                $order = $payment->order;
                $order->is_paid = true;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed successfully',
                    'payment' => $payment,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment not yet completed',
                'status' => $paymentIntent->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
