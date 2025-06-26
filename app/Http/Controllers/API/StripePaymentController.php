<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

class StripePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id'
            ]);

            $user = Auth::user();
            $order = Order::with('client')->findOrFail($request->order_id);

            if ($order->client_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to pay for this order'
                ], 403);
            }

            if (Gate::denies('create-payment', $order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to pay for this order'
                ], 403);
            }

            if ($order->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only accepted orders can be paid'
                ], 400);
            }

            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid'
                ], 400);
            }

            $customer = $this->getOrCreateStripeCustomer($user);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($order->total_price * 100),
                'currency' => 'usd',
                'customer' => $customer->id,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id
                ]
            ]);

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id' => $user->id,
                    'method' => 'stripe',
                    'amount' => $order->total_price,
                    'status' => 'pending',
                    'stripe_payment_id' => $paymentIntent->id
                ]
            );

            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'payment' => $payment
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe API error',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function getOrCreateStripeCustomer(User $user)
    {
        if ($user->stripe_id) {
            try {
                return Customer::retrieve($user->stripe_id);
            } catch (\Exception $e) {
                $user->stripe_id = null;
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id
            ]
        ]);

        $user->stripe_id = $customer->id;
        $user->save();

        return $customer;
    }

    public function confirmPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'order_id' => 'required|exists:orders,id'
            ]);

            $user = Auth::user();
            $order = Order::findOrFail($request->order_id);

            $payment = Payment::where([
                ['stripe_payment_id', $request->payment_intent_id],
                ['order_id', $order->id]
            ])->firstOrFail();

            if ($payment->user_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to confirm this payment'
                ], 403);
            }

            $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_id);

            if ($paymentIntent->status === 'succeeded') {
                $payment->status = 'paid';
                $payment->save();

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
                'message' => 'Payment not completed',
                'status' => $paymentIntent->status
            ], 400);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe API error',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
