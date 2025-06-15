<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Owner;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\OrderCreatedNotification;
use App\Mail\OrderAcceptedNotification;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $client = Client::where('user_id', Auth::id())->firstOrFail();
        $owner = Owner::findOrFail($request->owner_id);

        $order = Order::create([
            'client_id' => $client->id,
            'owner_id' => $owner->id,
            'book_id' => $request->book_id,
            'quantity' => $request->quantity,
            'total_price' => $request->total_price,
            'status' => 'pending',
            'payment_method' => $request->payment_method ?? 'cash',
        ]);

        // Send email notification to the owner
        Mail::to($owner->user->email)->send(new OrderCreatedNotification($order));

        return response()->json([
            'message' => 'Order created successfully and notification sent to owner',
            'order' => $order
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected,delivered'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        // Send email notification to the client if order is accepted
        if ($request->status === 'accepted') {
            Mail::to($order->client->user->email)->send(new OrderAcceptedNotification($order));
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
