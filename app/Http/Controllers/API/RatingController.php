<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Models\Rating;
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
        try {
            $rating = Rating::create($request->validated());
            return response()->json(['success' => true, 'message' => 'Rating created successfully', 'data' => $rating], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create rating', 'error' => $e->getMessage()], 500);
        }
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

    public function update(UpdateRatingRequest $request, string $id)
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

            $rating->update($request->validated());

            return response()->json(['success' => true, 'message' => 'Rating updated successfully', 'data' => $rating]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update rating', 'error' => $e->getMessage()], 500);
        }
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
