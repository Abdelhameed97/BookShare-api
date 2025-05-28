<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wishlists = Wishlist::with(['user', 'book'])->get();
        return response()->json($wishlists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWishlistRequest $request)
    {
        $wishlist = Wishlist::create($request->validated());

        return response()->json($wishlist, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $wishlist = Wishlist::with(['user', 'book'])->findOrFail($id);
        return response()->json($wishlist);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->update($request->validated());
        return response()->json($wishlist);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->delete();
        return response()->json(['message' => 'Rating deleted successfully']);
    }
}