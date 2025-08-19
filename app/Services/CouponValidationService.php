<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CouponAssignment;
use App\Models\Reference;
use App\Models\ReferenceUsage;
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
        // Clean and standardize the coupon code
        $couponCode = strtoupper(trim($couponCode));
        
        Log::info('Starting coupon validation', [
            'coupon_code' => $couponCode,
            'cart_items' => $cartData->count(),
            'is_guest' => !Auth::check(),
            'guest_identifier' => $guestOrderIdentifier ? substr($guestOrderIdentifier, 0, 16) . '...' : null
        ]);

        // First try to find coupon, then reference
        $couponData = Coupon::with('assignments')->where('code', $couponCode)->first();
        $couponType = 'coupon';

        if (!$couponData) {
            $couponData = Reference::with('assignments')->where('code', $couponCode)->first();
            $couponType = 'reference';
        }

        if (!$couponData) {
            Log::warning('Coupon/Reference code not found', ['coupon_code' => $couponCode]);
            return [
                'success' => false,
                'message' => 'Invalid coupon or reference code.'
            ];
        }

        Log::info('Coupon/Reference found', [
            'type' => $couponType,
            'id' => $couponData->id,
            'code' => $couponData->code,
            'status' => $couponData->status
        ]);

        // Basic coupon validation
        $basicValidation = $this->basicCouponValidation($couponData);
        if (!$basicValidation['success']) {
            Log::warning('Basic validation failed', [
                'coupon_code' => $couponCode,
                'reason' => $basicValidation['message']
            ]);
            return $basicValidation;
        }

        // User and assignment validation
        $userValidation = $this->validateUserAndAssignments($couponData, $couponType);
        if (!$userValidation['success']) {
            Log::warning('User validation failed', [
                'coupon_code' => $couponCode,
                'reason' => $userValidation['message']
            ]);
            return $userValidation;
        }

        // Usage limit validation
        $usageValidation = $this->validateUsageLimits($couponData, $guestOrderIdentifier);
        if (!$usageValidation['success']) {
            Log::warning('Usage limit validation failed', [
                'coupon_code' => $couponCode,
                'reason' => $usageValidation['message']
            ]);
            return $usageValidation;
        }

        // Calculate discount
        $discountCalculation = $this->calculateCouponDiscount($couponData, $cartData, $couponType);
        
        Log::info('Coupon validation completed', [
            'coupon_code' => $couponCode,
            'success' => $discountCalculation['success'] ?? false,
            'discount' => $discountCalculation['total_discount'] ?? 0
        ]);
        
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
        
        Log::info('Validating user and assignments', [
            'is_guest' => $isGuest,
            'coupon_type' => $couponType,
            'applicable_to' => $couponData->applicable_to,
            'user_id' => $isGuest ? null : Auth::id()
        ]);
        
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
                if (in_array('all_shop', $applicableTo) || 
                    in_array('all_gym', $applicableTo) || 
                    in_array('all_users', $applicableTo) ||
                    in_array('all', $applicableTo)) {
                    return ['success' => true];
                }
                
                return [
                    'success' => false,
                    'message' => 'This reference code is not available for guest users. Please login to use this code.'
                ];
            } else {
                // For authenticated users, check role-based eligibility
                $user = Auth::user();
                $applicableTo = is_array($couponData->applicable_to) ? $couponData->applicable_to : [$couponData->applicable_to];
                
                // Check if applicable to all
                if (in_array('all', $applicableTo) || in_array('all_users', $applicableTo)) {
                    return ['success' => true];
                }
                
                // Check role-specific eligibility
                if ($user->role === 'Gym Owner/Trainer/Influencer/Dietitian' && in_array('all_gym', $applicableTo)) {
                    return ['success' => true];
                }
                
                if ($user->role === 'Shop Owner' && in_array('all_shop', $applicableTo)) {
                    return ['success' => true];
                }
                
                // Check if user is specifically assigned
                $isSpecificallyAssigned = $couponData->assignments->where('assignable_type', 'user')
                    ->where('assignable_id', $user->id)
                    ->isNotEmpty();
                
                if ($isSpecificallyAssigned) {
                    return ['success' => true];
                }
                
                return [
                    'success' => false,
                    'message' => 'This reference code is not applicable to your account type.'
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
        Log::info('Getting matching cart items', [
            'coupon_type' => $couponType,
            'applicable_to' => $couponData->applicable_to,
            'created_by' => $couponData->created_by,
            'cart_items_count' => $cartData->count()
        ]);

        // For "all users" and "all products" coupons
        if ($couponData->applicable_to === 'all_users' || $couponData->applicable_to === 'all_products') {
            // Check if this is a system-wide coupon (created by admin/system)
            if ($couponData->created_by == 1 || !$couponData->created_by) {
                Log::info('System-wide coupon, applying to all cart items');
                return $cartData; // Apply to all cart items for system coupons
            }
            
            // If coupon has a specific creator (seller), filter by seller's products
            Log::info('Seller-specific coupon, filtering by seller products');
            return $cartData->filter(function ($item) use ($couponData) {
                return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
            });
        }

        // For reference codes
        if ($couponType === 'reference') {
            // Check if this is a system-wide reference code
            if ($couponData->created_by == 1 || !$couponData->created_by) {
                Log::info('System-wide reference code, applying to all cart items');
                return $cartData; // Apply to all cart items for system reference codes
            }
            
            // If reference code has a creator (seller), filter by seller's products
            Log::info('Seller-specific reference code, filtering by seller products');
            return $cartData->filter(function ($item) use ($couponData) {
                return isset($item->product->seller) && $item->product->seller->user_id == $couponData->created_by;
            });
        }

        // For product-specific coupons
        $productAssignments = $couponData->assignments->where('assignable_type', 'product');
        if ($productAssignments->isNotEmpty()) {
            $assignedProductIds = $productAssignments->pluck('assignable_id')->toArray();
            Log::info('Product-specific coupon', ['assigned_products' => $assignedProductIds]);
            
            $matchingItems = $cartData->whereIn('product_id', $assignedProductIds);
            
            // Additionally filter by seller if coupon has a creator
            if ($couponData->created_by && $couponData->created_by != 1) {
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
            Log::info('Seller-specific coupon', ['assigned_sellers' => $assignedSellerIds]);
            
            return $cartData->filter(function ($item) use ($assignedSellerIds) {
                return isset($item->product->seller) && in_array($item->product->seller->id, $assignedSellerIds);
            });
        }

        // Default: no matching items
        Log::warning('No matching criteria found for coupon');
        return collect();
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
                'is_authenticated' => Auth::check(),
                'table_name' => $couponData->getTable()
            ]);

            // Check if this is a reference or regular coupon
            if ($couponData instanceof Reference) {
                $this->recordReferenceUsage($couponData, $orderId, $discountAmount, $orderTotal, $guestIdentifier);
            } else {
                $this->recordRegularCouponUsage($couponData, $orderId, $discountAmount, $orderTotal, $guestIdentifier);
            }

        } catch (\Exception $e) {
            Log::error('Error recording coupon usage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Record reference usage with giver and applyer benefits
     */
    private function recordReferenceUsage($reference, $orderId, $discountAmount, $orderTotal, $guestIdentifier = null)
    {
        // Calculate both giver and applyer discounts
        $discounts = $reference->calculateDiscount($orderTotal);
        
        $usageData = [
            'reference_id' => $reference->id,
            'order_id' => $orderId,
            'total_discount_amount' => $discounts['total_discount'],
            'giver_earning_amount' => $discounts['giver_discount'],
            'applyer_discount_amount' => $discounts['applyer_discount'],
            'order_total' => $orderTotal,
            'used_at' => now(),
        ];

        if (Auth::check()) {
            $usageData['user_id'] = Auth::id();
            
            // Try to find the giver user by parsing reference codes or assignments
            $giverUserId = $this->findReferenceGiver($reference, Auth::id());
            if ($giverUserId) {
                $usageData['giver_user_id'] = $giverUserId;
            }
        } else {
            // For guest users, we can't easily track the giver
            // We could implement a session-based tracking if needed
        }

        Log::info('Reference usage data prepared', $usageData);

        $referenceUsage = ReferenceUsage::create($usageData);
        Log::info('Reference usage created', ['usage_id' => $referenceUsage->id]);

        // Increment reference used count
        $reference->increment('used_count');
        Log::info('Reference used count incremented', ['new_count' => $reference->fresh()->used_count]);
    }

    /**
     * Record regular coupon usage
     */
    private function recordRegularCouponUsage($couponData, $orderId, $discountAmount, $orderTotal, $guestIdentifier = null)
    {
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
    }

    /**
     * Find the giver user ID for a reference code
     */
    private function findReferenceGiver($reference, $currentUserId)
    {
        // Strategy 1: Check if reference is specifically assigned to a user (the giver)
        $assignment = \App\Models\ReferenceAssign::where('reference_id', $reference->id)
            ->where('assignable_type', 'user')
            ->where('assignable_id', '!=', $currentUserId)
            ->first();
        
        if ($assignment) {
            return $assignment->assignable_id;
        }

        // Strategy 2: Parse reference code if it contains user information
        // This assumes reference codes follow patterns like "GYMJOH123" or "SHOPJOH123" where 123 is user ID
        if (preg_match('/(GYM|SHOP)[A-Z]{0,3}(\d+)$/', $reference->code, $matches)) {
            $possibleUserId = (int) $matches[2];
            if (\App\Models\User::where('id', $possibleUserId)->exists() && $possibleUserId != $currentUserId) {
                return $possibleUserId;
            }
        }

        // Strategy 3: Check who created this reference (if it's a personal reference)
        if ($reference->created_by && $reference->created_by != 1 && $reference->created_by != $currentUserId) { // Not system created
            return $reference->created_by;
        }

        // If we can't determine the giver, return null
        return null;
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
