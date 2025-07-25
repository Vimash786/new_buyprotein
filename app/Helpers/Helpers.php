<?php

use App\Models\products;
use App\Models\ProductVariantCombination;
use Illuminate\Support\Facades\Auth;

if (!function_exists('format_price')) {
    function format_price($productId, $type = '')
    {
        $product = products::find($productId);

        if (Auth::user()) {
            $userRole = Auth::user()->role;

            $variant = ProductVariantCombination::where('product_id', $product->id)->first();
            if ($variant) {
                if ($userRole == 'User') {
                    if ($type == 'actual' && $variant->regular_user_discount > 0) {
                        return '₹' . number_format($variant->regular_user_price, 2);
                    } else {
                        return '₹' . number_format($variant->regular_user_final_price, 2);
                    }
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer/Dietitian') {
                    if ($type == 'actual' && $variant->gym_owner_discount > 0) {
                        return '₹' . number_format($variant->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format($variant->gym_owner_final_price, 2);
                    }
                } elseif ($userRole == 'Shop Owner') {
                    if ($type == 'actual' && $variant->shop_owner_discount) {
                        return '₹' . number_format($variant->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format($variant->shop_owner_final_price, 2);
                    }
                }
            } else {
                if ($userRole == 'User') {
                    if ($type == 'actual' && $product->regular_user_discount > 0) {
                        return '₹' . number_format($product->regular_user_price, 2);
                    } else {
                        return '₹' . number_format($product->regular_user_final_price);
                    }
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer/Dietitian') {
                    if ($type == 'actual' && $product->gym_owner_discount > 0) {
                        return '₹' . number_format($product->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format($product->gym_owner_final_price, 2);
                    }
                } elseif ($userRole == 'Shop Owner') {
                    if ($type == 'actual' && $product->shop_owner_discount > 0) {
                        return '₹' . number_format($product->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format($product->shop_owner_final_price, 2);
                    }
                }
            }
        } else {
            $variant = ProductVariantCombination::where('product_id', $product->id)->first();
            if ($variant) {
                if ($type == 'actual' && $variant->regular_user_discount > 0) {
                    return '₹' . number_format($variant->regular_user_price, 2);
                } else {
                    return '₹' . number_format($variant->regular_user_final_price, 2);
                }
            } else {
                if ($type == 'actual' && $product->regular_user_price > 0) {
                    return '₹' . number_format($product->regular_user_price, 2);
                } else {
                    return '₹' . number_format($product->regular_user_final_price, 2);
                }
            }
        }
    }
}
