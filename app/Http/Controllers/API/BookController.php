<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['user', 'category', 'comments', 'ratings.reviewer']);

        $filters = [];

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
            $filters[] = 'title';
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
            $filters[] = 'user_id';
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
            $filters[] = 'category_id';
        }
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
            $filters[] = 'min_price';
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
            $filters[] = 'max_price';
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
            $filters[] = 'status';
        }

        $books = $query->orderBy('created_at', 'desc')->get();

        if ($books->isEmpty()) {
            $message = 'No books found.';

            if (in_array('title', $filters)) {
                $message = 'No books found with this title.';
            } elseif (in_array('user_id', $filters)) {
                $message = 'No books found for this user.';
            } elseif (in_array('category_id', $filters)) {
                $message = 'No books found in this category.';
            } elseif (in_array('min_price', $filters) || in_array('max_price', $filters)) {
                $message = 'No books found in this price range.';
            } elseif (in_array('status', $filters)) {
                $message = 'No books found with this status.';
            }

            return response()->json([
                'status' => 'error',
                'message' => $message
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $books
        ]);
    }

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $bookData = $request->except('images');
        $bookData['user_id'] = Auth::id(); // logged-in user

        $book = new Book($bookData);

        $imagesPaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagesPaths[] = $image->store('books', 'public');
            }
        }

        $book->images = $imagesPaths;
        $book->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Book created successfully (tax calculated automatically)',
            'data' => $book->load(['user', 'category'])
        ], 201);
    }

    public function show(Book $book)
    {
        if (!$book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $book->load(['user', 'category', 'comments', 'ratings'])
        ]);
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['status' => 'error', 'message' => 'Book not found'], 404);
        }

        if ($book->user_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'quantity' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $updateData = $request->except('images');

        $book->update($updateData); // This will automatically trigger tax calculation if price is included

        if ($request->hasFile('images')) {
            if ($book->images) {
                foreach ($book->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $imagesPaths = [];
            foreach ($request->file('images') as $image) {
                $imagesPaths[] = $image->store('books', 'public');
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

    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['status' => 'error', 'message' => 'Book not found'], 404);
        }

        if ($book->user_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($book->images) {
            foreach ($book->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $book->delete();

        return response()->json(['status' => 'success', 'message' => 'Book deleted successfully']);
    }
}