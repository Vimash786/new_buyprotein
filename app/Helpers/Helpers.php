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
        if (!$product) {
            return '₹0.00';
        }
        
        $variant = ProductVariantCombination::where('product_id', $product->id)->first();
        $user = Auth::user();
        $userRole = $user ? $user->role : 'User';
        
        if ($variant) {
            // Product has variants
            switch ($userRole) {
                case 'User':
                    if ($type == 'actual' && $variant->regular_user_discount > 0) {
                        return '₹' . number_format((float)$variant->regular_user_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->regular_user_final_price, 2);
                    }
                    
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    if ($type == 'actual' && $variant->gym_owner_discount > 0) {
                        return '₹' . number_format((float)$variant->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->gym_owner_final_price, 2);
                    }
                    
                case 'Shop Owner':
                    if ($type == 'actual' && $variant->shop_owner_discount > 0) {
                        return '₹' . number_format((float)$variant->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->shop_owner_final_price, 2);
                    }
                    
                default:
                    if ($type == 'actual' && $variant->regular_user_discount > 0) {
                        return '₹' . number_format((float)$variant->regular_user_price, 2);
                    } else {
                        return '₹' . number_format((float)$variant->regular_user_final_price, 2);
                    }
            }
        } else {
            // Product without variants
            switch ($userRole) {
                case 'User':
                    if ($type == 'actual' && $product->regular_user_discount > 0) {
                        return '₹' . number_format((float)$product->regular_user_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->regular_user_final_price, 2);
                    }
                    
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    if ($type == 'actual' && $product->gym_owner_discount > 0) {
                        return '₹' . number_format((float)$product->gym_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->gym_owner_final_price, 2);
                    }
                    
                case 'Shop Owner':
                    if ($type == 'actual' && $product->shop_owner_discount > 0) {
                        return '₹' . number_format((float)$product->shop_owner_price, 2);
                    } else {
                        return '₹' . number_format((float)$product->shop_owner_final_price, 2);
                    }
                    
                default:
                    if ($type == 'actual' && $product->regular_user_discount > 0) {
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
    }else{}
    return number_format((float)$price, 2);
}

if (!function_exists('get_cart_count')) {
    function get_cart_count()
    {
        if (Auth::check()) {
            // For logged-in users, sum the quantities from database
            return \App\Models\Cart::where('user_id', Auth::id())->sum('quantity');
        } else {
            // For guest users, sum the quantities from session
            $cart = session('cart', []);
            return array_sum(array_column($cart, 'quantity'));
        }
    }
}

if (!function_exists('has_discount')) {
    function has_discount($productId)
    {
        $product = products::find($productId);
        if (!$product) {
            return false;
        }
        
        $variant = ProductVariantCombination::where('product_id', $product->id)->first();
        $user = Auth::user();
        $userRole = $user ? $user->role : 'User';
        
        if ($variant) {
            // Product has variants
            switch ($userRole) {
                case 'User':
                    return $variant->regular_user_discount > 0;
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    return $variant->gym_owner_discount > 0;
                case 'Shop Owner':
                    return $variant->shop_owner_discount > 0;
                default:
                    return $variant->regular_user_discount > 0;
            }
        } else {
            // Product without variants
            switch ($userRole) {
                case 'User':
                    return $product->regular_user_discount > 0;
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    return $product->gym_owner_discount > 0;
                case 'Shop Owner':
                    return $product->shop_owner_discount > 0;
                default:
                    return $product->regular_user_discount > 0;
            }
        }
    }
}

if (!function_exists('get_discount_percentage')) {
    function get_discount_percentage($productId)
    {
        $product = products::find($productId);
        if (!$product) {
            return 0;
        }
        
        $variant = ProductVariantCombination::where('product_id', $product->id)->first();
        $user = Auth::user();
        $userRole = $user ? $user->role : 'User';
        
        if ($variant) {
            // Product has variants
            switch ($userRole) {
                case 'User':
                    return $variant->regular_user_discount ?? 0;
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    return $variant->gym_owner_discount ?? 0;
                case 'Shop Owner':
                    return $variant->shop_owner_discount ?? 0;
                default:
                    return $variant->regular_user_discount ?? 0;
            }
        } else {
            // Product without variants
            switch ($userRole) {
                case 'User':
                    return $product->regular_user_discount ?? 0;
                case 'Gym Owner/Trainer/Influencer/Dietitian':
                    return $product->gym_owner_discount ?? 0;
                case 'Shop Owner':
                    return $product->shop_owner_discount ?? 0;
                default:
                    return $product->regular_user_discount ?? 0;
            }
        }
    }
}
