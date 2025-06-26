<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook error: Invalid payload');
            return response()->json(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook error: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_BAD_REQUEST);
        }

        Log::info('Stripe webhook received: ' . $event->type);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'payment_intent.amount_capturable_updated':
                $this->handlePaymentIntentAmountCapturableUpdated($event->data->object);
                break;

                // Add more event handlers as needed
        }

        return response()->json(['success' => true]);
    }

    protected function handlePaymentIntentSucceeded(PaymentIntent $paymentIntent)
    {
        $payment = Payment::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->status = 'paid';
            $payment->save();

            $order = $payment->order;
            $order->is_paid = true;
            $order->save();

            // Send payment success notification
            // $order->user->notify(new PaymentSuccessNotification($order));
        }
    }

    protected function handlePaymentIntentFailed(PaymentIntent $paymentIntent)
    {
        $payment = Payment::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->status = 'failed';
            $payment->save();

            // Send payment failure notification
            // $order->user->notify(new PaymentFailedNotification($order));
        }
    }

    protected function handlePaymentIntentAmountCapturableUpdated(PaymentIntent $paymentIntent)
    {
        // Handle cases where payment requires capture
    }
}