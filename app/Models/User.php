<?php

namespace App\Models;

// Import related models
use App\Models\Owner;
use App\Models\Client;
use App\Models\Book;
use App\Models\Wishlist;
use App\Models\Rating;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use App\Mail\VerifyEmailCustom;
use App\Models\Cart;


class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    // Add core traits
    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable (for create/update).
     *
     * @var array<int, string>
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
        'provider', // For social login (e.g., Google, Facebook)
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden from arrays (like API responses).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel will hash the password automatically
        ];
    }

    // ========================
    // 🔗 RELATIONSHIPS
    // ========================

    /**
     * One-to-One relationship: User → Owner
     */
    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    /**
     * One-to-One relationship: User → Client
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is client.
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * One-to-Many relationship: User → Books
     */
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    /**
     * One-to-Many relationship: User → Wishlists
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Many-to-Many relationship: User → Wishlist Books (via pivot table 'wishlists')
     */
    public function wishlistBooks()
    {
        return $this->belongsToMany(Book::class, 'wishlists');
    }

    /**
     * One-to-Many relationship: Ratings this user has given to others
     */
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'reviewer_id');
    }

    /**
     * One-to-Many relationship: Ratings this user has received
     */
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'reviewed_user_id');
    }

    // ========================
    // 📧 EMAIL VERIFICATION
    // ========================

    /**
     * Send the email verification link using a custom Mailable.
     */
    // public function sendEmailVerificationNotification()
    // {
    //     $verifyUrl = URL::temporarySignedRoute(
    //         'verification.verify',
    //         Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
    //         [
    //             'id' => $this->id,
    //             'hash' => sha1($this->email),
    //         ]
    //     );

    //     Mail::to($this->email)->send(new VerifyEmailCustom($this, $verifyUrl));
    // }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }
}
