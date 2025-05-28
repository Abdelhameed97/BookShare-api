<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of books with optional search filters.
     */
    public function index(Request $request)
    {
        $query = Book::with(['user', 'category', 'comments', 'ratings']);

        // Search by book title
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        // Search by library (user)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Optional: Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(10)
        ]);
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|string',
            'price' => 'required|numeric|min:0',
            'rental_price' => 'nullable|numeric|min:0',
            'educational_level' => 'nullable|string',
            'genre' => 'nullable|string',
            'status' => 'required|in:available,rented,sold',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book = new Book($request->except('images'));
        $book->user_id = Auth::id();

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagesPaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('books', 'public');
                $imagesPaths[] = $path;
            }
            $book->images = $imagesPaths;
        }

        $book->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Book created successfully',
            'data' => $book->load(['user', 'category'])
        ], 201);
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        return response()->json([
            'status' => 'success',
            'data' => $book->load(['user', 'category', 'comments', 'ratings'])
        ]);
    }

    /**
     * Update the specified book.
     */
    public function update(Request $request, Book $book)
    {
        // Check if user owns the book
        if ($book->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'rental_price' => 'nullable|numeric|min:0',
            'educational_level' => 'nullable|string',
            'genre' => 'nullable|string',
            'status' => 'sometimes|in:available,rented,sold',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $book->update($request->except('images'));

        // Handle new image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($book->images) {
                foreach ($book->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }

            $imagesPaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('books', 'public');
                $imagesPaths[] = $path;
            }
            $book->images = $imagesPaths;
            $book->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Book updated successfully',
            'data' => $book->load(['user', 'category'])
        ]);
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book)
    {
        // Check if user owns the book
        if ($book->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete associated images
        if ($book->images) {
            foreach ($book->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $book->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Book deleted successfully'
        ]);
    }
}
