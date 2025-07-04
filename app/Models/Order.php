<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'owner_id',
        'quantity',
        'total_price',
        'status',
        'payment_method',
        'is_paid',
        'subtotal',
        'tax',
        'shipping_fee',
        'discount',
        'coupon_code',
        'notes'
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

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }


    public function calculateTotal()
    {
        $subtotal = 0;
        $tax = 0;

        foreach ($this->orderItems as $item) {
            $book = $item->book;
            $price = $item->type === 'rent' ? $book->rental_price : $book->price;
            $subtotal += $price * $item->quantity;
            $tax += $price * 0.10 * $item->quantity;
        }

        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total_price = $subtotal + $tax + $this->shipping_fee - $this->discount;
        $this->save();
    }
}
