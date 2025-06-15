<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Policy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Category;


class CategoryController extends Controller
{
    public function index()
    {
        // dd(auth()->user());
        // dd(auth()->user()->role);
        $this->authorize('viewAny', Category::class);
        return response()->json(Category::all(), 200);
    }

    public function store(Request $request)
    {
        
        // $this->authorize('create', Category::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        $this->authorize('view', $category);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $this->authorize('update', $category);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return response()->json($category);
    }

    public function destroy($id)
    {

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        // $this->authorize('delete',  $category);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

}
