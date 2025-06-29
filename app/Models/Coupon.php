<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
        'max_uses',
        'used_count'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isMaxedOut()
    {
        return $this->max_uses && $this->used_count >= $this->max_uses;
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->isMaxedOut();
    }

    public function calculateDiscount($subtotal)
    {
        if ($this->type === 'fixed') {
            return min($this->value, $subtotal);
        }

        $discount = $subtotal * ($this->value / 100);

        if ($this->max_discount && $discount > $this->max_discount) {
            return $this->max_discount;
        }

        return $discount;
    }
}
