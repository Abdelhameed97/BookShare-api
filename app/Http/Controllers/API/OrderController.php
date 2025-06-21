<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Book;
use App\Models\Order;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\OrderCreatedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdatedNotification;


class OrderController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $orders = Order::with(['orderItems.book', 'client', 'owner'])
                ->where('owner_id', $user->id)
                ->orWhere('client_id', $user->id)
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

    public function store(StoreOrderRequest $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $groupedItems = [];

            // Group items by owner
            foreach ($request->items as $item) {
                $book = Book::findOrFail($item['book_id']);
                $ownerId = $book->user_id;

                if (!isset($groupedItems[$ownerId])) {
                    $groupedItems[$ownerId] = [];
                }

                $groupedItems[$ownerId][] = [
                    'book_id' => $book->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ];
            }

            $orders = [];

            foreach ($groupedItems as $ownerId => $items) {
                $totalPrice = 0;
                $quantity = 0;

                foreach ($items as $item) {
                    $totalPrice += $item['price'] * $item['quantity'];
                    $quantity += $item['quantity'];
                }

                $order = Order::create([
                    'client_id' => $user->id,
                    'owner_id' => $ownerId,
                    'total_price' => $totalPrice,
                    'status' => 'pending'
                ]);

                foreach ($items as $item) {
                    $order->orderItems()->create($item);
                }

                // Notify the owner
                $owner = User::find($ownerId);
                if ($owner) {
                    $order->load('orderItems.book', 'client');
                    $owner->notify(new OrderPlacedNotification($order));
                    $order->load('client', 'owner');
                    Mail::to($owner->email)->send(new OrderCreatedNotification($order));
                }

                $orders[] = $order->load('orderItems.book');
            }

            return response()->json([
                'success' => true,
                'message' => 'Orders placed successfully.',
                'data' => $orders
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }


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

            $order = Order::with(['client', 'owner', 'orderItems.book'])->findOrFail($id);

            if ($user->id !== $order->client_id && $user->id !== $order->owner_id && !$user->is_admin) {
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

    public function update(UpdateOrderRequest $request, string $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $order = Order::with(['client', 'owner', 'orderItems.book'])->findOrFail($id);

            if ($user->id !== $order->owner_id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $order->update([
                'status' => $request->status
            ]);

            $order->client->notify(new OrderStatusUpdatedNotification($order));

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order->fresh()->load('client', 'owner', 'orderItems.book')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $order = Order::with('owner')->findOrFail($id);

            if ($user->id !== $order->owner_id && !$user->is_admin) {
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