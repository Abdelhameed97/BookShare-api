<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'isbn',
        'pages',
        'author',
        'description',
        'condition',
        'price',
        'rental_price',
        'educational_level',
        'genre',
        'status',
        'author', // تمت إضافته
        'content', // تمت إضافته
        'images',
        'quantity',
        'tax',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'images' => 'array', // Laravel will handle the JSON <-> array conversion automatically
        'price'    => 'decimal:2',
        'tax'      => 'decimal:2',
    ];

    public function setPriceAttribute($value)
    {
        $price = floatval($value);
        $this->attributes['price'] = $price;
        $this->attributes['tax']   = round($price * 0.10, 2);
    }

    //  Relationship: Book belongs to a User (owner)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //  Relationship: Book belongs to a Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    //  Optional: Book can have many comments
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    //  Optional: Book can be in many users' wishlists
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    //  Optional: Book can have many ratings/reviews
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }


}
