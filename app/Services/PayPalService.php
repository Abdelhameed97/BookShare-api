<?php

namespace App\Services;

use App\Models\Order;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PayPalService
{
    protected $client;

    public function __construct()
    {
        $clientId = config('paypal.client_id');
        $clientSecret = config('paypal.client_secret');

        if (config('paypal.mode') === 'sandbox') {
            $environment = new SandboxEnvironment($clientId, $clientSecret);
        } else {
            $environment = new ProductionEnvironment($clientId, $clientSecret);
        }

        $this->client = new PayPalHttpClient($environment);
    }

    public function createOrder($amount, $currency, $orderId, $returnUrl, $cancelUrl, Order $order)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');

        $order->loadMissing('orderItems.book');

        $items = [];
        $totalAmount = 0;

        foreach ($order->orderItems as $orderItem) {
            if (!$orderItem->book) continue;

            $quantity = (int) $orderItem->quantity;
            $price = number_format((float) $orderItem->book->price, 2, '.', '');

            if ($quantity <= 0 || !is_numeric($price) || $price <= 0) continue;

            $items[] = [
                'name' => $orderItem->book->title,
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value' => $price
                ],
                'quantity' => $quantity
            ];

            $totalAmount += ($price * $quantity);
        }

        // Fallback if no valid items found
        if (empty($items)) {
            $items[] = [
                'name' => "Order #{$orderId}",
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value' => $amount
                ],
                'quantity' => 1
            ];
            $totalAmount = $amount;
        }

        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $orderId,
                    'description' => "Payment for Order #{$orderId}",
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($totalAmount, 2, '.', ''),
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $currency,
                                'value' => number_format($totalAmount, 2, '.', '')
                            ]
                        ]
                    ],
                    'items' => $items
                ]
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => config('app.name'),
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING'
            ]
        ];

        try {
            $response = $this->client->execute($request);
            return $response->result;
        } catch (\Exception $e) {
            logger()->error('PayPal Order Creation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function captureOrder($orderId)
    {
        $request = new OrdersCaptureRequest($orderId);

        try {
            $response = $this->client->execute($request);
            return $response->result;
        } catch (\Exception $e) {
            logger()->error('PayPal Order Capture Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}