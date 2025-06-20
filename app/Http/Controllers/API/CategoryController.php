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
        // if (!auth()->user()->isAdmin()) {
        //     return response()->json(['message' => 'You are not authorized to view categories'], 403);
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

        // Check if the user is an admin
        // This assumes you have a method isAdmin() in your User model
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'You are not authorized to create categories'], 403);
        }

        $validatedData = $request->validated();

        $category = Category::create([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'] ?? 'general', // Default to 'general' if not provided
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => "Category of id {$id} not found"], 404);
        }

        return response()->json(['category' => $category], 200);
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
