<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Models\Cart;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Book;


class WishlistController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'User not authenticated'
                ], 401);
            }

            if ($user->is_admin) {
                $wishlists = Wishlist::with(['user', 'book'])->get();
            } else {
                $wishlists = Wishlist::with(['user', 'book'])
                    ->where('user_id', $user->id)
                    ->get();
            }

            return response()->json(['success' => true, 'data' => $wishlists], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wishlists',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    // ✅ إضافة كتاب إلى الويشليست
    // ✅ تحقق من أن المستخدم ليس هو صاحب الكتاب
    // ✅ تحقق من عدم وجود الكتاب بالفعل في الويشليست
    // ✅ إنشاء الويشليست
    // ✅ إرجاع رسالة نجاح مع بيانات الويشليست
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $userId = Auth::id();
        $book = Book::find($request->book_id);

        // ✅ تحقق إن المستخدم مش هو نفسه صاحب الكتاب
        if ($book->user_id == $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot add your own book to your wishlist.'
            ], 403);
        }

        // ✅ تحقق من عدم التكرار
        $exists = Wishlist::where('user_id', $userId)
                        ->where('book_id', $book->id)
                        ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book already in your wishlist.'
            ], 409);
        }

        // ✅ إنشاء الويشليست
        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'book_id' => $book->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Book added to wishlist.',
            'data' => $wishlist
        ], 201);
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

            $wishlist->update($validatedData);

            unset($validatedData['user_id']);

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

    public function moveAllToCart(Request $request)
    {
        try {
            $user = Auth::user();
            $wishlistItems = Wishlist::where('user_id', $user->id)->with('book')->get();
            $movedCount = 0;

            foreach ($wishlistItems as $item) {
                if ($item->book && $item->book->status === 'available') {
                    $exists = DB::table('carts')->where([
                        ['user_id', '=', $user->id],
                        ['book_id', '=', $item->book_id],
                    ])->exists();

                    if (!$exists) {
                        Cart::create([
                            'user_id' => $user->id,
                            'book_id' => $item->book_id,
                            'quantity' => 1,
                        ]);

                        $item->delete();
                        $movedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'moved_items_count' => $movedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error while moving items to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
