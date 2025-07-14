<?php

use App\Models\products;
use App\Models\ProductVariantCombination;
use Illuminate\Support\Facades\Auth;

if (!function_exists('format_price')) {
    function format_price($productId)
    {
        $product = products::find($productId);
        if (Auth::user()) {
            $userRole = Auth::user()->role;

            $variant = ProductVariantCombination::where('product_id', $product->id)->first();

            if ($variant) {
                if ($userRole == 'User') {
                    return '₹' . number_format($variant->regular_user_price, 2);
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer') {
                    return '₹' . number_format($variant->gym_owner_price, 2);
                } elseif ($userRole == 'Shop Owner') {
                    return '₹' . number_format($variant->shop_owner_price, 2);
                }
            } else {
                if ($userRole == 'User') {
                    return '₹' . number_format($product->regular_user_price, 2);
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer') {
                    return '₹' . number_format($product->gym_owner_price, 2);
                } elseif ($userRole == 'Shop Owner') {
                    return '₹' . number_format($product->shop_owner_price, 2);
                }
            }
        } else {
            return '₹' . number_format($product->regular_user_price, 2);
        }
    }
}
