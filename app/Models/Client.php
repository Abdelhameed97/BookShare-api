<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = ['user_id', 'profile_picture', 'preferences'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}