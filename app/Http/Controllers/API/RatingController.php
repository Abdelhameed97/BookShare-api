<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Rating::with(['book', 'reviewer', 'reviewedUser'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRatingRequest $request)
    {
        $rating = Rating::create($request->validated());
        return response()->json($rating, status: 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rating = Rating::with(['book', 'reviewer', 'reviewedUser'])->findOrFail($id);
        return response()->json($rating);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRatingRequest $request, string $id)
    {
        $rating = Rating::findOrFail($id);
        $rating->update($request->validated());
        return response()->json($rating);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rating = Rating::findOrFail($id);
        $rating->delete();
        return response()->json(['message' => 'Rating deleted successfully']);
    }
}