<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    /** @use HasFactory<\Database\Factories\RatingFactory> */
    use HasFactory;

    protected $fillable = [
        'book_id',
        'reviewer_id',
        'rewiewed_user_id',
        'rating',
        'comment'
    ];

    public function book() {
        return $this->belongsTo(Book::class);
    }
    public function reviewer() {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function rewiewedUser() {
        return $this->belongsTo(User::class, 'rewiewed_user_id');
    }

}