<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook error: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook error: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        Log::info('Stripe webhook received: ' . $event->type);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentFailed($paymentIntent);
                break;

                // Handle other event types as needed
        }

        return response()->json(['success' => true]);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->status = 'paid';
            $payment->save();

            $order = $payment->order;
            $order->is_paid = true;
            $order->save();

            // You might want to send a notification here
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->status = 'failed';
            $payment->save();

            // You might want to send a notification here
        }
    }
}
