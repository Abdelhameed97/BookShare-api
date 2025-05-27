<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'book_id',
        'comment',
        'parent_id',
    ];

    // Each comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each comment belongs to a book
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // A comment may have replies (child comments)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // A comment may belong to a parent comment
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
}
