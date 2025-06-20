<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        // ❌ هذا الجزء لا يعمل لغير المسجلين دخول ويؤدي إلى خطأ 500
        // if (auth()->check() && auth()->user()->isAdmin()) {
        //     return response()->json([
        //         'categories' => Category::all()
        //     ], 200);
        // }

     return response()->json([
            'categories' => Category::all()
           ], 200);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'You are not authorized to create categories'], 403);
        }

        $validatedData = $request->validated();

        $category = Category::create([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'] ?? 'general',
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified category and its books.
     */
    public function show(string $id)
    {
        $category = Category::with('books.user')->find($id);

        if (!$category) {
            return response()->json(['message' => "Category of id {$id} not found"], 404);
        }

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'books' => $category->books,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => "Category of id {$id} not found"], 404);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'You are not authorized to update categories'], 403);
        }

        $validatedData = $request->validated();
        $category->update($validatedData);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category,
        ], 200);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => "Category of id {$id} not found"], 404);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'You are not authorized to delete categories'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
