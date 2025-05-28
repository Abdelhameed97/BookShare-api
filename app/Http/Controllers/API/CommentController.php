<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CommentController extends Controller
{
    public function index()
    {
        return Comment::with(['user', 'book', 'replies'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'comment' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create($data);

        return response()->json($comment, 201);
    }

    public function show(Comment $comment)
    {
        return $comment->load(['user', 'book', 'replies']);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'comment' => 'required|string',
        ]);

        
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
            

        $comment->update($data);

        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
 
       

        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['message' => 'Comment deleted']);
    }
}

