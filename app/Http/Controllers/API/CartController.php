<?php

namespace App\Http\Controllers\Api;
use App\Models\Cart;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        return Cart::with('book')
            ->where('user_id', Auth::id())
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'type' => 'required|in:buy,rent',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $cart = Cart::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'book_id' => $validated['book_id'],
                'type' => $validated['type']
            ],
            [
                'quantity' => $validated['quantity'] ?? 1
            ]
        );

        return response()->json(['message' => 'Added to cart', 'data' => $cart], 201);
    }

    public function destroy($id)
    {
        $cart = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $cart->delete();

        return response()->json(['message' => 'Removed from cart']);

    }
   
}


