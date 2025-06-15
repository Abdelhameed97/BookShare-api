<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
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

        return response()->json($comment, 201);
    }

    public function show(Comment $comment)
    {
        return $comment->load(['user', 'book', 'replies']);
    }

    public function update(Request $request, Comment $comment)
    {
        // Ensure the comment exists
        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }
        //if comment exist 
        // Validate the request data
        $data = $request->validate([
            'comment' => 'required|string',

        ]);
       

      
        $comment->update($data);
        // Return the updated comment
        return response()->json($comment);




        // $comment = Comment::find($comment->id);
        // $data = $request->validate([
        //     'comment' => 'required|string',
        // ]);
        // if($comment->user_id !== auth()->comment->id()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }


        // $comment->update($data);

        // return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
 
       

        // if ($comment->user_id !== auth()->id()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        return response()->json(['message' => 'Comment deleted']);
    }
}

