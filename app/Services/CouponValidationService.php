<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CouponAssignment;
use App\Models\Reference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CouponValidationService
{
    /**
     * Validate coupon for both authenticated and guest users
     */
    public function validateCoupon($couponCode, $cartData, $guestOrderIdentifier = null)
    {
        // First try to find coupon, then reference
        $couponData = Coupon::with('assignments')->where('code', $couponCode)->first();
        $couponType = 'coupon';

        if (!$couponData) {
            $couponData = Reference::with('assignments')->where('code', $couponCode)->first();
            $couponType = 'reference';
        }

        if (!$couponData) {
            return [
                'success' => false,
                'message' => 'Invalid coupon code.'
            ];
        }

        // Basic coupon validation
        $basicValidation = $this->basicCouponValidation($couponData);
        if (!$basicValidation['success']) {
            return $basicValidation;
        }

        // User and assignment validation
        $userValidation = $this->validateUserAndAssignments($couponData, $couponType);
        if (!$userValidation['success']) {
            return $userValidation;
        }

        // Usage limit validation
        $usageValidation = $this->validateUsageLimits($couponData, $guestOrderIdentifier);
        if (!$usageValidation['success']) {
            return $usageValidation;
        }

        // Calculate discount
        $discountCalculation = $this->calculateCouponDiscount($couponData, $cartData, $couponType);
        
        return $discountCalculation;
    }

    /**
     * Basic coupon validation (status, dates, overall usage limit)
     */
    private function basicCouponValidation($couponData)
    {
        if ($couponData->status !== 'active') {
            return [
                'success' => false,
                'message' => 'Coupon is not active.'
            ];
        }

        if (!Carbon::now()->between($couponData->starts_at, $couponData->expires_at)) {
            return [
                'success' => false,
                'message' => 'Coupon is expired or not yet valid.'
            ];
        }

        // Check overall usage limit
        if ($couponData->usage_limit && $couponData->used_count >= $couponData->usage_limit) {
            return [
                'success' => false,
                'message' => 'Coupon usage limit has been reached.'
            ];
        }

        return ['success' => true];
    }

    /**
     * Validate user eligibility and coupon assignments
     */
    private function validateUserAndAssignments($couponData, $couponType)
    {
        $isGuest = !Auth::check();
        
        // For specific user assignments - only for registered users
        $userAssignments = $couponData->assignments->where('assignable_type', 'user');
        
        if ($userAssignments->isNotEmpty()) {
            // Specific user coupon - only for registered users
            if ($isGuest) {
                return [
                    'success' => false,
                    'message' => 'This coupon is only available for registered users. Please login to use this coupon.'
                ];
            }
            
            // Check if current user is in the assignment list
            $assignedUserIds = $userAssignments->pluck('assignable_id')->toArray();
            if (!in_array(Auth::id(), $assignedUserIds)) {
                return [
                    'success' => false,
                    'message' => 'This coupon is not available for your account.'
                ];
            }
        }

        // For "all users" and "all products" coupons - both registered and guest users can use
        if ($couponData->applicable_to === 'all_users' || $couponData->applicable_to === 'all_products') {
            return ['success' => true];
        }

        // For product-specific coupons - both registered and guest users can use
        $productAssignments = $couponData->assignments->where('assignable_type', 'product');
        if ($productAssignments->isNotEmpty()) {
            return ['success' => true]; // Product-specific coupons are available for everyone
        }

        // For seller-specific coupons - both registered and guest users can use
        $sellerAssignments = $couponData->assignments->where('assignable_type', 'seller');
        if ($sellerAssignments->isNotEmpty()) {
            return ['success' => true]; // Seller-specific coupons are available for everyone
        }

        // For reference codes with specific assignments
        if ($couponType === 'reference') {
            if ($isGuest) {
                // Guest users can use reference codes if they are for "all_shop" or "all_gym"
                $applicableTo = is_array($couponData->applicable_to) ? $couponData->applicable_to : [$couponData->applicable_to];
                if (in_array('all_shop', $applicableTo) || in_array('all_gym', $applicableTo)) {
                    return ['success' => true];
                }
                
                return [
                    'success' => false,
                    'message' => 'This reference code is not available for guest users.'
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Validate user-specific usage limits
     */
    private function validateUsageLimits($couponData, $guestOrderIdentifier = null)
    {
        if (!$couponData->user_usage_limit) {
            return ['success' => true]; // No user limit set
        }

        $isGuest = !Auth::check();

        if ($isGuest) {
            // For guest users, check usage by guest identifier (IP, session, etc.)
            if ($guestOrderIdentifier) {
                $guestUsageCount = CouponUsage::where('coupon_id', $couponData->id)
                    ->where('guest_identifier', $guestOrderIdentifier)
                    ->count();

                if ($guestUsageCount >= $couponData->user_usage_limit) {
                    return [
                        'success' => false,
                        'message' => 'You have already used this coupon the maximum number of times allowed.'
                    ];
                }
            }
        } else {
            // For authenticated users, check usage by user_id
            $userUsageCount = CouponUsage::where('coupon_id', $couponData->id)
                ->where('user_id', Auth::id())
                ->count();

            if ($userUsageCount >= $couponData->user_usage_limit) {
                return [
                    'success' => false,
                    'message' => 'You have already used this coupon the maximum number of times allowed.'
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Calculate discount for the coupon
     */
    private function calculateCouponDiscount($couponData, $cartData, $couponType)
    {
        $matchingCartItems = $this->getMatchingCartItems($couponData, $cartData, $couponType);
        
        if ($matchingCartItems->isEmpty()) {
            return [
                'success' => false,
                'message' => 'This coupon is not applicable to any items in your cart.'
            ];
        }

        $matchedSubtotal = $matchingCartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Check minimum amount requirement
        if ($couponData->minimum_amount && $matchedSubtotal < $couponData->minimum_amount) {
            return [
                'success' => false,
                'message' => "Minimum order amount of ₹{$couponData->minimum_amount} required for this coupon."
            ];
        }

        $perItemDiscounts = [];
        $totalDiscount = 0;

        if ($couponData->type === 'percentage') {
            foreach ($matchingCartItems as $item) {
                $itemSubtotal = $item->price * $item->quantity;
                $discountValue = $couponType === 'reference' ? 
                    ($couponData->applyer_discount ?? $couponData->value) : 
                    $couponData->value;
                
                $itemDiscount = $itemSubtotal * ($discountValue / 100);

                $perItemDiscounts[] = [
                    'cart_item_id' => $item->id ?? ($item->product_id . '_' . implode('_', $item->variant_option_ids ?? [])),
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'item_subtotal' => $itemSubtotal,
                    'discount' => round($itemDiscount, 2),
                ];

                $totalDiscount += $itemDiscount;
            }

            // Apply maximum discount limit
            if ($couponData->maximum_discount && $totalDiscount > $couponData->maximum_discount) {
                $totalDiscount = $couponData->maximum_discount;
                
                // Redistribute discount proportionally
                $perItemDiscounts = array_map(function ($item) use ($matchedSubtotal, $totalDiscount) {
                    $proportional = ($item['item_subtotal'] / $matchedSubtotal) * $totalDiscount;
                    $item['discount'] = round($proportional, 2);
                    return $item;
                }, $perItemDiscounts);
            }
        } else {
            // Fixed amount discount
            $flatDiscount = min($couponData->value, $matchedSubtotal);
            
            foreach ($matchingCartItems as $item) {
                $itemSubtotal = $item->price * $item->quantity;
                $itemDiscount = ($itemSubtotal / $matchedSubtotal) * $flatDiscount;

                $perItemDiscounts[] = [
                    'cart_item_id' => $item->id ?? ($item->product_id . '_' . implode('_', $item->variant_option_ids ?? [])),
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'item_subtotal' => $itemSubtotal,
                    'discount' => round($itemDiscount, 2),
                ];

                $totalDiscount += $itemDiscount;
            }

            $totalDiscount = $flatDiscount;
        }

        return [
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'total_discount' => round($totalDiscount, 2),
            'matched_subtotal' => round($matchedSubtotal, 2),
            'per_item_discounts' => $perItemDiscounts,
            'coupon_data' => $couponData,
            'coupon_type' => $couponType
        ];
    }

    /**
     * Get cart items that match the coupon criteria
     */
    private function getMatchingCartItems($couponData, $cartData, $couponType)
    {
        // For "all users" and "all products" coupons
        if ($couponData->applicable_to === 'all_users' || $couponData->applicable_to === 'all_products') {
            // If coupon has a creator (seller), filter by seller's products
            if ($couponData->created_by) {
                return $cartData->filter(function ($item) use ($couponData) {
                    return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
                });
            }
            return $cartData;
        }

        // For reference codes
        if ($couponType === 'reference') {
            // If reference code has a creator (seller), filter by seller's products
            if ($couponData->created_by) {
                return $cartData->filter(function ($item) use ($couponData) {
                    return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
                });
            }
            return $cartData; // Reference codes typically apply to all cart items
        }

        // For product-specific coupons
        $productAssignments = $couponData->assignments->where('assignable_type', 'product');
        if ($productAssignments->isNotEmpty()) {
            $assignedProductIds = $productAssignments->pluck('assignable_id')->toArray();
            $matchingItems = $cartData->whereIn('product_id', $assignedProductIds);
            
            // Additionally filter by seller if coupon has a creator
            if ($couponData->created_by) {
                $matchingItems = $matchingItems->filter(function ($item) use ($couponData) {
                    return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
                });
            }
            
            return $matchingItems;
        }

        // For seller-specific coupons
        $sellerAssignments = $couponData->assignments->where('assignable_type', 'seller');
        if ($sellerAssignments->isNotEmpty()) {
            $assignedSellerIds = $sellerAssignments->pluck('assignable_id')->toArray();
            return $cartData->filter(function ($item) use ($assignedSellerIds) {
                return isset($item->product->seller) && in_array($item->product->seller->id, $assignedSellerIds);
            });
        }

        // For coupons without specific assignments, filter by creator (seller)
        if ($couponData->created_by) {
            return $cartData->filter(function ($item) use ($couponData) {
                return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
            });
        }

        return $cartData;
    }

    /**
     * Record coupon usage after successful order
     */
    public function recordCouponUsage($couponData, $orderId, $discountAmount, $orderTotal, $guestIdentifier = null)
    {
        try {
            Log::info('Recording coupon usage', [
                'coupon_id' => $couponData->id,
                'order_id' => $orderId,
                'discount_amount' => $discountAmount,
                'order_total' => $orderTotal,
                'guest_identifier' => $guestIdentifier,
                'is_authenticated' => Auth::check()
            ]);

            $usageData = [
                'coupon_id' => $couponData->id,
                'order_id' => $orderId,
                'discount_amount' => $discountAmount,
                'order_total' => $orderTotal,
                'used_at' => now(),
            ];

            if (Auth::check()) {
                $usageData['user_id'] = Auth::id();
            } else {
                $usageData['guest_identifier'] = $guestIdentifier;
            }

            Log::info('Coupon usage data prepared', $usageData);

            $couponUsage = CouponUsage::create($usageData);
            Log::info('Coupon usage created', ['usage_id' => $couponUsage->id]);

            // Increment coupon used count
            $couponData->increment('used_count');
            Log::info('Coupon used count incremented', ['new_count' => $couponData->fresh()->used_count]);

        } catch (\Exception $e) {
            Log::error('Error recording coupon usage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate guest identifier for coupon usage tracking
     */
    public function generateGuestIdentifier()
    {
        // Use combination of IP and session ID for guest identification
        return hash('sha256', request()->ip() . session()->getId());
    }
}
