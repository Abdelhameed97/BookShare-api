<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;
    protected $fillable = [
        'client_id', 'owner_id',
        'quantity',
        'total_price',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }
    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class);
    }
    public function orderNotification()
    {
        return $this->belongsTo(OrderNotification::class);
    }

}
