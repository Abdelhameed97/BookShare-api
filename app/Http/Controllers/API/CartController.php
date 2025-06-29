<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = Cart::with('book')
            ->where('user_id', Auth::id())
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'book_id' => $item->book_id,
                    'type' => $item->type,
                    'quantity' => $item->quantity,
                    'book' => $item->book,
                    'price' => $item->type === 'rent' ? $item->book->rental_price : $item->book->price
                ];
            });

        return response()->json($cartItems);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'type' => 'sometimes|in:buy,rent',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $user = Auth::user();
        $book = Book::find($validated['book_id']);
        $type = $validated['type'] ?? 'buy';
        $quantity = $validated['quantity'] ?? 1;

        if ($book->quantity < $quantity) {
            return response()->json([
                'message' => 'The requested quantity is greater than available in stock.',
                'available_quantity' => $book->quantity
            ], 400);
        }

        $existingCart = Cart::where([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'type' => $type
        ])->first();

        if ($existingCart) {
            return response()->json([
                'message' => 'The book is already in the cart.'
            ], 409);
        }

        $cart = Cart::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'type' => $type,
            'quantity' => $quantity
        ]);

        return response()->json([
            'message' => 'Added to cart successfully.',
            'data' => $cart->load('book')
        ], 201);
    }

    public function destroy($id)
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Item not found in cart.'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Item removed from cart successfully.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'type' => 'sometimes|in:buy,rent'
        ]);

        $cart = Cart::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Item not found in cart.'], 404);
        }

        if (isset($validated['quantity'])) {
            $book = Book::find($cart->book_id);
            if ($book->quantity < $validated['quantity']) {
                return response()->json([
                    'message' => 'The requested quantity is greater than available in stock.',
                    'available_quantity' => $book->quantity
                ], 400);
            }
            $cart->quantity = (int)$validated['quantity'];
        }

        if (isset($validated['type'])) {
            $cart->type = $validated['type'];
        }

        $cart->save();

        // نحسب السعر وقت العرض فقط
        $book = Book::find($cart->book_id);
        $cart->price = ($cart->type === 'rent' ? $book->rental_price : $book->price) * 1.10;
        $cart->original_price = $cart->type === 'rent' ? $book->rental_price : $book->price;

        return response()->json([
            'message' => 'Cart updated successfully.',
            'data' => $cart
        ]);
    }

    public function checkStatus($bookId)
    {
        $cartItem = Cart::where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->first();

        return response()->json([
            'isInCart' => $cartItem !== null,
            'cartItem' => $cartItem
        ]);
    }
}