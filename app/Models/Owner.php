<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Owner extends Model
{
    protected $fillable = ['user_id', 'library_name', 'library_logo', 'library_description', 'location'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

