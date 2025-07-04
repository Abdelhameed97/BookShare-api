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
use App\Models\Coupon;
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
            ])->select('id', 'status', 'total_price', 'client_id', 'owner_id', 'created_at', 'shipping_fee', 'tax', 'discount');

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
            $validated = $request->validated();

            $groupedItems = [];
            $shippingFee = 25;
            $subtotal = 0;
            $totalTax = 0;

            foreach ($validated['items'] as $item) {
                $book = Book::findOrFail($item['book_id']);
                $type = $item['type'];
                $ownerId = $book->user_id;
                $unitPrice = $type === 'rent' ? $book->rental_price : $book->price;

                $tax = $unitPrice * 0.10 * $item['quantity'];
                $totalTax += $tax;

                if (!isset($groupedItems[$ownerId])) {
                    $groupedItems[$ownerId] = [];
                }

                $groupedItems[$ownerId][] = [
                    'book_id' => $book->id,
                    'quantity' => $item['quantity'],
                    'type' => $type,
                    'unit_price' => $unitPrice,
                    'tax' => $tax
                ];

                $subtotal += $unitPrice * $item['quantity'];
            }

            if ($subtotal > 200) {
                $shippingFee = 0;
            }

            $discount = 0;
            if (isset($validated['coupon_code'])) {
                $coupon = Coupon::where('code', $validated['coupon_code'])
                    ->where('expires_at', '>', now())
                    ->first();

                if ($coupon) {
                    $discount = $coupon->type === 'fixed' ?
                        $coupon->value : ($subtotal * $coupon->value / 100);
                    $coupon->increment('used_count');
                }
            }

            $total = $subtotal + $totalTax + $shippingFee - $discount;

            $orders = [];

            foreach ($groupedItems as $ownerId => $items) {
                $orderQuantity = 0;
                $orderTax = 0;

                foreach ($items as $item) {
                    $orderQuantity += $item['quantity'];
                    $orderTax += $item['tax'];
                }

                $order = Order::create([
                    'client_id' => $user->id,
                    'owner_id' => $ownerId,
                    'total_price' => $total,
                    'quantity' => $orderQuantity,
                    'status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                    'shipping_fee' => $shippingFee,
                    'tax' => $orderTax,
                    'discount' => $discount,
                    'coupon_code' => $validated['coupon_code'] ?? null
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

                    OrderItem::create([
                        'order_id' => $order->id,
                        'book_id' => $book->id,
                        'quantity' => $item['quantity'],
                        'type' => $item['type']
                    ]);
                }

                $orders[] = $order->load('orderItems.book');
            }

            if ($validated['clear_cart'] ?? false) {
                $user->carts()->delete();
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

            // Restore quantity if status changed to rejected and it was not rejected before
            if ($request->status === 'rejected' && $order->status !== 'rejected') {
                foreach ($order->orderItems as $item) {
                    if ($item->book) {
                        $item->book->quantity += $item->quantity;
                        $item->book->save();
                    }
                }
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

        if ($order->status !== 'rejected') {
            foreach ($order->orderItems as $item) {
                if ($item->book) {
                    $item->book->quantity += $item->quantity;
                    $item->book->save();
                }
            }
        }

        $order->status = 'rejected';
        $order->save();

        $order->load('client', 'orderItems.book');
        $order->client->notify(new OrderStatusUpdatedNotification($order, 'rejected'));

        return response()->json(['success' => true, 'message' => 'Order rejected and quantities restored']);
    }
}