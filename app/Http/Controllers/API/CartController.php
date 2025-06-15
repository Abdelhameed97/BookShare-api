<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Display cart contents
    public function index()
    {
        return Cart::with('book')
            ->where('user_id', Auth::id())
            ->get();
    }

    // Add a book to the cart
    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'type' => 'required|in:buy,rent',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $userId = Auth::id();
        $book = Book::find($validated['book_id']);
        $requestedQuantity = $validated['quantity'] ?? 1;

        // Check available quantity
        if ($book->quantity < $requestedQuantity) {
            return response()->json([
                'message' => 'The requested quantity is greater than available in stock.',
                'available_quantity' => $book->quantity
            ], 400);
        }

        // Check if the item already exists in the cart
        $existingCart = Cart::where([
            'user_id' => $userId,
            'book_id' => $validated['book_id'],
            'type' => $validated['type']
        ])->first();

        if ($existingCart) {
            return response()->json([
                'message' => 'The book is already in the cart.'
            ], 409);
        }

        // Add to cart
        $cart = Cart::create([
            'user_id' => $userId,
            'book_id' => $validated['book_id'],
            'type' => $validated['type'],
            'quantity' => $requestedQuantity
        ]);

        return response()->json([
            'message' => 'Added to cart successfully.',
            'data' => $cart
        ], 201);
    }

    // Delete an item from the cart
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

    // Update the quantity of an item in the cart
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Cart::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->first();

        if (!$cart) {
            return response()->json(['message' => 'Item not found in cart.'], 404);
        }

        // Check availability of the requested quantity
        $book = Book::find($cart->book_id);
        if ($book->quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'The requested quantity is greater than available in stock.',
                'available_quantity' => $book->quantity
            ], 400);
        }

        $cart->quantity = $validated['quantity'];
        $cart->save();

        return response()->json(['message' => 'Quantity updated successfully.', 'data' => $cart]);
    }
}