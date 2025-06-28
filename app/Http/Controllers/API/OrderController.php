<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\OrderCreatedNotification;
use App\Mail\OrderUpdatedNotification;
use App\Mail\OrderAcceptedNotification;
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

            $ordersQuery = Order::with([
                'orderItems.book:id,title,price,rental_price',
                'client:id,name',
                'owner:id,name'
            ])->select('id', 'status', 'total_price', 'client_id', 'owner_id', 'created_at');

            if ($user->role === 'owner') {
                $ordersQuery->where('owner_id', $user->id);
            } elseif ($user->role === 'client') {
                $ordersQuery->where('client_id', $user->id);
            } elseif ($user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Unauthorized role'], 403);
            }

            $orders = $ordersQuery->latest()->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No orders found'
                ], 200);
            }

            return response()->json(['success' => true, 'data' => $orders], 200);
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
        DB::beginTransaction();

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $validated = $request->validated();

            $groupedItems = [];

            foreach ($validated['items'] as $item) {
                $book = Book::findOrFail($item['book_id']);
                $type = $item['type'];
                $ownerId = $book->user_id;
                $unitPrice = $type === 'rent' ? $book->rental_price : $book->price;

                if (!isset($groupedItems[$ownerId])) {
                    $groupedItems[$ownerId] = [];
                }

                $groupedItems[$ownerId][] = [
                    'book_id' => $book->id,
                    'quantity' => $item['quantity'],
                    'type' => $type,
                    'unit_price' => $unitPrice
                ];
            }

            $orders = [];

            foreach ($groupedItems as $ownerId => $items) {
                $totalPrice = 0;
                $totalQuantity = 0;

                foreach ($items as $item) {
                    $totalPrice += $item['unit_price'] * $item['quantity'];
                    $totalQuantity += $item['quantity'];
                }

                $order = Order::create([
                    'client_id' => $user->id,
                    'owner_id' => $ownerId,
                    'total_price' => $totalPrice,
                    'quantity' => $totalQuantity,
                    'status' => 'pending',
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                ]);

                foreach ($items as $item) {
                    $book = Book::findOrFail($item['book_id']);

                    if ($book->quantity < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Book '{$book->title}' has only {$book->quantity} copies left."
                        ], 400);
                    }

                    $book->quantity -= $item['quantity'];
                    $book->save();

                    $order->orderItems()->create([
                        'book_id' => $book->id,
                        'quantity' => $item['quantity'],
                        'type' => $item['type'],
                    ]);
                }

                $owner = User::find($ownerId);
                if ($owner) {
                    $order->load('orderItems.book', 'client');
                    $owner->notify(new OrderPlacedNotification($order));
                    Mail::to($owner->email)->send(new OrderCreatedNotification($order));
                }

                $orders[] = $order->load('orderItems.book');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orders placed successfully.',
                'data' => $orders
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
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
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $order = Order::with(['client', 'owner', 'orderItems.book'])->findOrFail($id);

            if ($user->id !== $order->client_id && $user->id !== $order->owner_id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            return response()->json(['success' => true, 'data' => $order]);
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

            $order->update(['status' => $request->status]);

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

            $order = Order::with('orderItems.book')->findOrFail($id);

            if ($user->id !== $order->client_id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            DB::beginTransaction();

            foreach ($order->orderItems as $item) {
                if ($item->book) {
                    $item->book->quantity += $item->quantity;
                    $item->book->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully and quantities restored.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ownerOrders(Request $request)
    {
        $owner = $request->user();

        $orders = Order::with('orderItems.book', 'client')
            ->where('owner_id', $owner->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function accept(Order $order, Request $request)
    {
        if (Gate::denies('update', $order)) {
            return response()->json(['success' => false, 'message' => 'You do not own this order.'], 403);
        }

        $order->status = 'accepted';
        $order->save();

        $order->load('client', 'orderItems.book');
        $order->client->notify(new OrderStatusUpdatedNotification($order, 'accepted'));

        return response()->json(['success' => true, 'message' => 'Order accepted']);
    }

    public function reject(Order $order, Request $request)
    {
        if (Gate::denies('update', $order)) {
            return response()->json(['success' => false, 'message' => 'You do not own this order.'], 403);
        }

        $order->status = 'rejected';
        $order->save();

        $order->load('client', 'orderItems.book');
        $order->client->notify(new OrderStatusUpdatedNotification($order, 'rejected'));

        return response()->json(['success' => true, 'message' => 'Order rejected']);
    }


}