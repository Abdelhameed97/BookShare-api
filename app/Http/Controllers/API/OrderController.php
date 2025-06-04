<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Owner;
use App\Models\Order;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $owner = $user->owner;

            if (!$owner) {
                return response()->json(['success' => false, 'message' => 'Only library owners can view orders'], 403);
            }

            $orders = Order::with(['orderItems.book', 'client.user', 'owner.user'])
                ->where('owner_id', $owner->id)
                ->get();

            return response()->json(['success' => true, 'data' => $orders]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $client = Client::where('user_id', $user->id)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        $owner = Owner::find($request->owner_id);
        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => 'Owner not found',
            ], 404);
        }

        $order = Order::create($request->validated());

        $totalPrice = 0;

        foreach ($request->items as $item) {
            $order->orderItems()->create([
                'book_id' => $item['book_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);

            $totalPrice += $item['quantity'] * $item['price'];
        }

        $order->update(['total_price' => $totalPrice]);

        if ($owner->user) {
            $owner->user->notify(new OrderPlacedNotification($order));
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => $order->load('orderItems')
        ], 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $order = Order::with(['client.user', 'owner.user', 'orderItems.book'])->findOrFail($id);

            if ($user->id !== $order->client->user_id && !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateOrderRequest $request, string $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $order = Order::with(['client.user', 'owner.user', 'orderItems.book'])->findOrFail($id);

            if ($user->id !== $order->owner->user_id) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $order->update([
                'status' => $request->status
            ]);

            $order->client->user->notify(new OrderStatusUpdatedNotification($order));

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order->fresh()->load('client.user', 'owner.user', 'orderItems.book')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $order = Order::with('owner.user')->findOrFail($id);

            if ($user->id !== $order->owner->user_id) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}