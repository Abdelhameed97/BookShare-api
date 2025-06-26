<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $payments = Payment::with('order', 'user')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    public function show($id)
    {
        $payment = Payment::with(['order', 'user'])->findOrFail($id);
        $user = Auth::user();

        if ($payment->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            $user = Auth::user();
            $order = Order::findOrFail($request->order_id);

            if (Gate::denies('create-payment', $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to pay for this order.'
                ], 403);
            }

            if ($order->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is only allowed for accepted orders.'
                ], 403);
            }

            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.'
                ], 400);
            }

            $existingPayment = Payment::where('order_id', $order->id)->first();

            if ($request->method === 'cash') {
                if ($existingPayment) {
                    $existingPayment->update([
                        'method' => 'cash',
                        'status' => 'paid',
                        'user_id' => $user->id,
                    ]);
                    $payment = $existingPayment;
                } else {
                    $payment = Payment::create([
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'method' => 'cash',
                        'amount' => $order->total_price,
                        'status' => 'paid',
                    ]);
                }

                $order->is_paid = true;
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Cash payment recorded successfully.',
                    'data' => $payment->load('order', 'user')
                ]);
            }

            // Stripe or PayPal
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'A payment already exists for this order.'
                ], 400);
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => $request->method,
                'amount' => $order->total_price,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->method === 'stripe'
                    ? 'Payment intent created. Please complete your payment.'
                    : 'Redirecting to PayPal...',
                'data' => $payment->load('order', 'user')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdatePaymentRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $payment = Payment::findOrFail($id);
            $order = $payment->order;

            if ($order->user_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this payment.'
                ], 403);
            }

            if ($order->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update payment for an unaccepted order.'
                ], 403);
            }

            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.'
                ], 400);
            }

            $payment->update([
                'method' => $request->method,
                'status' => 'paid',
                'user_id' => $user->id,
            ]);

            $order->is_paid = true;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully.',
                'data' => $payment->load('order', 'user')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $user = Auth::user();

        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can delete payments.'
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully.'
        ]);
    }

    // PaymentController.php - Update authorization checks
    public function getOrderPayment($orderId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $order = Order::with(['user', 'payment'])->findOrFail($orderId);

        if ($order->user_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this payment'
            ], 403);
        }

        $payment = $order->payment;

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for this order'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    public function createStripePaymentIntent(Request $request)
    {
        try {
            $user = Auth::user();
            $order = Order::findOrFail($request->order_id);

            if ($order->client_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to pay for this order.'
                ], 403);
            }

            if ($order->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is only allowed for accepted orders.'
                ], 403);
            }

            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.'
                ], 400);
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            $intent = PaymentIntent::create([
                'amount' => $order->total_price * 100,
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ],
            ]);

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id' => $user->id,
                    'method' => 'stripe',
                    'amount' => $order->total_price,
                    'status' => 'pending',
                    'stripe_payment_id' => $intent->id,
                ]
            );

            return response()->json([
                'success' => true,
                'clientSecret' => $intent->client_secret,
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Stripe payment intent.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmStripePayment(Request $request)
    {
        try {
            $paymentIntentId = $request->payment_intent_id;
            $payment = Payment::where('stripe_payment_id', $paymentIntentId)->firstOrFail();

            Stripe::setApiKey(config('services.stripe.secret'));

            $intent = PaymentIntent::retrieve($paymentIntentId);

            if ($intent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed yet.',
                    'stripe_status' => $intent->status
                ], 400);
            }

            $payment->update(['status' => 'paid']);

            $order = $payment->order;
            $order->is_paid = true;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully.',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm Stripe payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
