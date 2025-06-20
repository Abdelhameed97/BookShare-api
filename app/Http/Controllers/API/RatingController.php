<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Models\Rating;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function index()
    {
        try {
            $ratings = Rating::with(['book', 'reviewer', 'reviewedUser'])->get();
            return response()->json(['success' => true, 'data' => $ratings]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch ratings', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreRatingRequest $request)
    {
        $userId = Auth::id(); // Get current user ID
        $book = Book::find($request->book_id);

        if (!$book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found.'
            ], 404);
        }

        if ($book->user_id === $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot rate your own book.'
            ], 403);
        }

        // Check for duplicate rating
        $alreadyRated = Rating::where('book_id', $book->id)
                            ->where('reviewer_id', $userId)
                            ->exists();

        if ($alreadyRated) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already rated this book.'
            ], 409);
        }

        $rating = Rating::create([
            'book_id' => $book->id,
            'reviewer_id' => $userId,
            'reviewed_user_id' => $book->user_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rating added successfully.',
            'data' => $rating
        ], 201);
    }


    public function show(string $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $rating = Rating::with(['book', 'reviewer', 'reviewedUser'])->findOrFail($id);

            if ($user->id !== $rating->reviewer_id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            return response()->json(['success' => true, 'data' => $rating]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch rating', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateRatingRequest $request, $id)
    {
        $rating = Rating::find($id);

        if (!$rating) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rating not found.'
            ], 404);
        }

        // Check if the authenticated user is the reviewer or an admin
        $user = auth()->user();
        if ($user->id !== $rating->reviewer_id && $user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $rating->update($request->only(['rating', 'comment']));

        return response()->json([
            'status' => 'success',
            'message' => 'Rating updated successfully.',
            'data' => $rating->fresh(['book', 'reviewer', 'reviewedUser']),
        ]);
    }


    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $rating = Rating::findOrFail($id);

            if ($user->id !== $rating->reviewer_id && !$user->is_admin) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $rating->delete();

            return response()->json(['success' => true, 'message' => 'Rating deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete rating', 'error' => $e->getMessage()], 500);
        }
    }
}
