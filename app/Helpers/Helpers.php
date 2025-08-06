<?php

use App\Models\products;
use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\ProductVariantCombination;
use Illuminate\Support\Facades\Auth;


if (!function_exists('format_price')) {
    function format_price($productId, $type = '')
    {
        $product = products::find($productId);
        $variant = ProductVariantCombination::where('product_id', $product->id)->first();
        if (Auth::user()) {
            $userRole = Auth::user()->role;
            
            if ($variant) {
                if ($userRole == 'User') {
                    if ($type == 'actual' && $variant->regular_user_discount > 0) {
                        return '₹' . number_format((float)$variant->regular_user_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->regular_user_final_price, 2);
                    }
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer/Dietitian') {
                    if ($type == 'actual' && $variant->gym_owner_discount > 0) {
                        return '₹' . number_format((float)$variant->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->gym_owner_final_price, 2);
                    }
                } elseif ($userRole == 'Shop Owner') {
                    if ($type == 'actual' && $variant->shop_owner_discount) {
                        return '₹' . number_format((float)$variant->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->shop_owner_final_price, 2);
                    }
                }
            } else {
                if ($userRole == 'User') {
                    if ($type == 'actual' && $product->regular_user_discount > 0) {
                        return '₹' . number_format((float)$product->regular_user_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->regular_user_final_price, 2);
                    }
                } elseif ($userRole == 'Gym Owner/Trainer/Influencer/Dietitian') {
                    if ($type == 'actual' && $product->gym_owner_discount > 0) {
                        return '₹' . number_format((float)$product->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->gym_owner_final_price, 2);
                    }
                } elseif ($userRole == 'Shop Owner') {
                    if ($type == 'actual' && $product->shop_owner_discount > 0) {
                        return '₹' . number_format((float)$product->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->shop_owner_final_price, 2);
                    }
                }
            }
        } else {
            if ($variant) {
                if ($type == 'actual' && $variant->regular_user_discount > 0) {
                    return '₹' . number_format((float)$variant->regular_user_price, 2);
                } else {
                    return '₹' . number_format((float)$variant->regular_user_final_price, 2);
                }
            } else {
                if ($type == 'actual' && $product->regular_user_price > 0) {
                    return '₹' . number_format((float)$product->regular_user_price, 2);
                } else {
                    return '₹' . number_format((float)$product->regular_user_final_price, 2);
                }
            }
        }
    }
}

function couponsApply($productId, $price)
{
    $product = products::find($productId);
    if ($product) {
        // Find coupon assigned to this specific product
        $coupon = Coupon::whereHas('productAssignments', function($query) use ($productId) {
            $query->where('assignable_id', $productId);
        })->active()->first();

        // If no product-specific coupon found, check for "all products" coupon
        if (!$coupon) {
            $coupon = Coupon::whereHas('assignments', function($query) {
                $query->where('assignable_type', 'all_products');
            })->active()->first();
        }

        if ($coupon && $coupon->type && $coupon->value) {
            $discount = $coupon->calculateDiscount((float)$price);
            return number_format((float)$price - $discount, 2);
        }
    }
    return number_format((float)$price, 2);
}
