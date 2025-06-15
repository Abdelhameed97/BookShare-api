<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Owner;
use App\Models\Order;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdatedNotification;

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
    $validated = $request->validate([
        'owner_id' => 'required|exists:libraries,id',
        'client_id' => 'required|exists:clients,id',
        'book_id' => 'required|exists:books,id',
        'quantity' => 'required|integer|min:1',
        'total_price' => 'required|numeric|min:0',
        'status' => 'in:pending,accepted,rejected,delivered',
    ]);

    $order = Order::create($validated);

    // Get the library owner (assuming one-to-one relation with user)
    $owner = Owner::where('owner_id', $validated['owner_id'])->first();

    if ($owner && $owner->user) {
        $owner->user->notify(new OrderPlacedNotification($order));
    }

    return response()->json(['message' => 'Order created and owner notified.'], 201);
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
        $order->update([
            'status' => $request->status // accepted / rejected
        ]);

        // send notification to client
        $order->client->user->notify(new OrderStatusUpdatedNotification($order));

        return response()->json(['message' => 'Order status updated.']);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
