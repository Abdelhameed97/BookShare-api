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
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $owner = Owner::findOrFail($request->owner_id);

        $order = Order::create([
            'client_id' => $client->id,
            'owner_id' => $owner->id,
            'status' => 'pending',
        ]);
        // send notification to owner
        $owner->user->notify(new OrderPlacedNotification($order));

        return response()->json($order, 201);
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
