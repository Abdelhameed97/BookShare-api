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
    public function index(Request $request)
    {
        try {
            $query = Rating::with(['book', 'reviewer', 'reviewedUser']);

            if ($request->has('book_id')) {
                $query->where('book_id', $request->book_id);
            }

            if ($request->has('reviewer_id')) {
                $query->where('reviewer_id', $request->reviewer_id);
            }

            $ratings = $query->get();

            return response()->json([
                'success' => true,
                'data' => $ratings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ratings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreRatingRequest $request)
    {
        try {
            $userId = Auth::id();
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

            // Check for existing rating
            $existingRating = Rating::where('book_id', $book->id)
                ->where('reviewer_id', $userId)
                ->first();

            if ($existingRating) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already rated this book.',
                    'data' => $existingRating
                ], 409);
            }

            $rating = Rating::create([
                'book_id' => $book->id,
                'reviewer_id' => $userId,
                'reviewed_user_id' => $book->user_id,
                'rating' => $request->rating,
                'comment' => $request->comment ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rating added successfully.',
                'data' => $rating->load(['book', 'reviewer', 'reviewedUser'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $rating = Rating::with(['book', 'reviewer', 'reviewedUser'])->findOrFail($id);

            $user = Auth::user();
            if (!$user || ($user->id !== $rating->reviewer_id && !$user->is_admin)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            return response()->json(['success' => true, 'data' => $rating]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Rating not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch rating', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateRatingRequest $request, $id)
    {
        try {
            $rating = Rating::findOrFail($id);
            $user = Auth::user();

            // Verify user can update this rating
            if ($user->id !== $rating->reviewer_id && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $rating->update([
                'rating' => $request->rating,
                'comment' => $request->comment ?? $rating->comment
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rating updated successfully.',
                'data' => $rating->fresh(['book', 'reviewer', 'reviewedUser'])
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rating not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $rating = Rating::findOrFail($id);

            // Verify user can delete this rating
            if ($user->id !== $rating->reviewer_id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: You are not allowed to delete this rating.'
                ], 403);
            }

            $rating->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rating deleted successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rating.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}