<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderAcceptedNotification;
use App\Models\Order;

Route::get('/', function () {
    return view('welcome');
});

// web.php
Route::get('/orders/{order}/status/{status}', function ($orderId, $status) {
    $order = Order::find($orderId);
    if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
    }

    if (!in_array($status, ['accepted', 'rejected'])) {
        return response()->json(['error' => 'Invalid status'], 403);
    }

    $order->status = $status;
    $order->save();

    // إرسال إيميل للعميل بعد القبول
    if ($status === 'accepted') {
        Mail::to($order->client->email)->send(new OrderAcceptedNotification($order));
    }

    return "Order #$orderId has been $status";
});
