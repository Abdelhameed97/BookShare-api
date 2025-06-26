<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayPalPaymentController extends Controller
{
    protected $payPalService;

    public function __construct(PayPalService $payPalService)
    {
        $this->payPalService = $payPalService;
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with('orderItems.book')->findOrFail($request->order_id);
        $user = Auth::user();

        if ($order->client_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to pay for this order.'
            ], 403);
        }

        if ($order->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been paid.'
            ], 400);
        }

        try {
            $amount = number_format((float)$order->total_price, 2, '.', '');

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => 'paypal',
                'amount' => $amount,
                'status' => 'pending',
            ]);

            $paypalOrder = $this->payPalService->createOrder(
                $amount,
                'USD',
                $order->id,
                route('paypal.success', ['payment' => $payment->id]),
                route('paypal.cancel', ['payment' => $payment->id]),
                $order
            );

            $payment->update([
                'paypal_payment_id' => $paypalOrder->id
            ]);

            $approveLink = collect($paypalOrder->links)->firstWhere('rel', 'approve');

            return response()->json([
                'success' => true,
                'approval_url' => $approveLink->href,
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Payment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function success(Request $request, Payment $payment)
    {
        try {
            $token = $request->query('token');
            if (!$token) {
                throw new \Exception('Missing PayPal token parameter');
            }

            $result = $this->payPalService->captureOrder($payment->paypal_payment_id);

            $payment->status = 'paid';
            $payment->save();

            $order = $payment->order;
            $order->is_paid = true;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Success Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment execution failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Payment $payment)
    {
        try {
            $payment->status = 'cancelled';
            $payment->save();

            return response()->json([
                'success' => false,
                'message' => 'Payment was cancelled',
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Cancel Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status',
            ], 500);
        }
    }
}