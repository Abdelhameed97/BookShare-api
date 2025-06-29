<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

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
        
        // Load the user relationship
        $comment->load('user');

        return response()->json($comment, 201);
    }

    public function show(Comment $comment)
    {
        return $comment->load(['user', 'book', 'replies']);
    }

    public function update(Request $request, Comment $comment)
    {
        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        $user = $request->user();
        if ($comment->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'comment' => 'required|string',
        ]);

        $comment->update($data);
        return response()->json($comment);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        $user = $request->user();
        if ($comment->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
