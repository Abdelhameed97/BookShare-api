<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderItemQuantityRequest;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemQuantityRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderItemController extends Controller
{
    public function index()
    {
        try {
            $items = OrderItem::with(['order', 'book'])->get();
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch items', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreOrderItemQuantityRequest $request)
    {
        try {
            $order = Order::findOrFail($request->order_id);

            $user = Auth::user();
            if ($order->client->user->id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $item = $order->orderItems()->create($request->validated());

            return response()->json(['success' => true, 'message' => 'Item added', 'data' => $item], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add item', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $item = OrderItem::with(['order', 'book'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch item', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateOrderItemQuantityRequest $request, string $id)
    {
        try {
            $item = OrderItem::findOrFail($id);
            $order = $item->order;

            $user = Auth::user();
            if ($order->client->user->id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $item->update($request->validated());

            return response()->json(['success' => true, 'message' => 'Item updated', 'data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update item', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $item = OrderItem::findOrFail($id);
            $order = $item->order;

            $user = Auth::user();
            if ($order->client->user->id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $item->delete();
            return response()->json(['success' => true, 'message' => 'Item deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete item', 'error' => $e->getMessage()], 500);
        }
    }
}
