<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;
use App\Models\User;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'owner_id',
        'quantity',
        'total_price',
        'status',
        'payment_method'
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
