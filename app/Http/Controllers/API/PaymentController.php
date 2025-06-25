<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    // Show all payments for the authenticated user
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

    // Show a specific payment
    public function show($id)
    {
        $payment = Payment::with(['order', 'user'])->find($id);
        $user = Auth::user();

        if ($payment->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    // Store a new payment
    public function store(StorePaymentRequest $request)
    {
        try {
            $user = Auth::user();
            $order = Order::findOrFail($request->order_id);

            // Authorization check
            if ($order->client_id !== $user->id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to pay for this order.'
                ], 403);
            }

            // Validate order status
            if ($order->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is only allowed for accepted orders.'
                ], 403);
            }

            // Check if already paid
            if ($order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been paid.'
                ], 400);
            }

            // Check for existing payment
            if (Payment::where('order_id', $order->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A payment already exists for this order.'
                ], 400);
            }

            // Create payment
            $paymentData = [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => $request->method,
                'amount' => $order->total_price,
                'status' => $request->method === 'stripe' ? 'pending' : 'paid',
            ];

            $payment = Payment::create($paymentData);

            // For non-Stripe payments, mark order as paid immediately
            if ($request->method !== 'stripe') {
                $order->is_paid = true;
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => $request->method === 'stripe' ?
                    'Payment intent created. Please complete your payment.' :
                    'Payment completed successfully.',
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

    // Update an existing payment
    public function update(UpdatePaymentRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $payment = Payment::findOrFail($id);
            $order = $payment->order;

            if ($order->client_id !== $user->id && !$user->is_admin) {
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

            // Update payment
            $payment->update([
                'method' => $request->method,
                'status' => 'paid',
                'user_id' => $user->id,
            ]);

            // Mark order as paid
            $order->is_paid = true;
            $order->save();

            $payment->refresh();

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

    // Delete a payment (admin only)
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

    // Get payment for specific order
    public function getOrderPayment($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $user = Auth::user();

        if ($order->client_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for this order'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment->load('order', 'user')
        ]);
    }
}