<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->is_admin) {
                $wishlists = Wishlist::with(relations: ['user', 'book'])->get();
            } else {
                $wishlists = Wishlist::with(['user', 'book'])->where('user_id', $user->id)->get();
            }

            return response()->json(['success' => true, 'data' => $wishlists], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch wishlists', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreWishlistRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $validatedData['user_id'] = Auth::id();

            $wishlist = Wishlist::create($validatedData);

            return response()->json(['success' => true, 'message' => 'Wishlist created successfully', 'data' => $wishlist], 201);
        } catch (ValidationException $ve) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create wishlist', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $wishlist = Wishlist::with(['user', 'book'])->findOrFail($id);

            $user = Auth::user();
            if (!$user->is_admin && $wishlist->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to view this wishlist'], 403);
            }

            return response()->json(['success' => true, 'data' => $wishlist], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Wishlist not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch wishlist', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(StoreWishlistRequest $request, string $id)
    {
        try {
            $wishlist = Wishlist::findOrFail($id);
            $user = Auth::user();

            if (!$user->is_admin && $wishlist->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to update this wishlist'], 403);
            }

            $validatedData = $request->validated();

            unset($validatedData['user_id']);

            $wishlist->update($validatedData);

            return response()->json(['success' => true, 'message' => 'Wishlist updated successfully', 'data' => $wishlist], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Wishlist not found'], 404);
        } catch (ValidationException $ve) {
            return response()->json(['success' => false, 'message' => 'Validation errors', 'errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update wishlist', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $wishlist = Wishlist::findOrFail($id);
            $user = Auth::user();

            if (!$user->is_admin && $wishlist->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to delete this wishlist'], 403);
            }

            $wishlist->delete();
            return response()->json(['success' => true, 'message' => 'Wishlist deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Wishlist not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete wishlist', 'error' => $e->getMessage()], 500);
        }
    }
}
