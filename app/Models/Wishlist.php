<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    /** @use HasFactory<\Database\Factories\WishlistFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
    ];

    /**
     * A wishlist belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A wishlist belongs to a book.
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

}
