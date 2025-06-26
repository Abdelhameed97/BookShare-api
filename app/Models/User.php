<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable
{
    use Billable;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'national_id',
        'id_image',
        'role',
        'location',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Check if the user has the 'admin' role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // User can have many books
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    // Wishlist: User can have many wishlisted books
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistBooks()
    {
        return $this->belongsToMany(Book::class, 'wishlists');
    }

    // Ratings given by this user
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'reviewer_id');
    }

    // Ratings received by this user
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'reviewed_user_id');
    }
}
