<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;


class Owner extends Model
{
    use Notifiable;

    protected $fillable = ['user_id', 'library_name', 'library_logo', 'library_description', 'location'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // owner has many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}

