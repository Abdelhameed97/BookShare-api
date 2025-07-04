<?php

namespace App\Http\Controllers\API;

use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is invalid'
            ], 404);
        }

        if ($coupon->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has expired'
            ], 400);
        }

        if ($coupon->isMaxedOut()) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has reached its maximum usage limit'
            ], 400);
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        return response()->json([
            'success' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'new_subtotal' => $request->subtotal - $discount
        ]);
    }
}