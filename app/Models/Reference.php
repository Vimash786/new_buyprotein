<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Reference extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'reference'; // Explicitly set the table name
    protected $fillable = [
        'code',
        'name',
        'description',
        'type', // 'percentage', 'fixed'
        'giver_discount',
        'applyer_discount',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'user_usage_limit',
        'starts_at',
        'expires_at',
        'status', // 'active', 'inactive'
        'applicable_to', // 'all', 'users', 'products', 'sellers'
        'user_types', // JSON array of user types that can use this coupon
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'giver_discount' => 'decimal:2',
        'applyer_discount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'user_usage_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'user_types' => 'array',
        'status' => 'string',
        'applicable_to' => 'array', // Changed from 'string' to 'array' for JSON support
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Check if reference is applicable to a specific type
     */
    public function isApplicableTo($type)
    {
        $applicableTypes = is_array($this->applicable_to) ? $this->applicable_to : [$this->applicable_to];
        return in_array($type, $applicableTypes) || in_array('all', $applicableTypes);
    }

    /**
     * Check if reference is applicable to gym users
     */
    public function isApplicableToGym()
    {
        return $this->isApplicableTo('all_gym');
    }

    /**
     * Check if reference is applicable to shop users
     */
    public function isApplicableToShop()
    {
        return $this->isApplicableTo('all_shop');
    }

    /**
     * Check if reference is applicable to all users
     */
    public function isApplicableToAllUsers()
    {
        return $this->isApplicableTo('all_users');
    }

    /**
     * Get human readable applicable types
     */
    public function getApplicableTypesDisplayAttribute()
    {
        $applicableTypes = is_array($this->applicable_to) ? $this->applicable_to : [$this->applicable_to];
        $display = [];
        
        foreach ($applicableTypes as $type) {
            switch ($type) {
                case 'all':
                    $display[] = 'All Users';
                    break;
                case 'all_users':
                    $display[] = 'All Users';
                    break;
                case 'all_gym':
                    $display[] = 'All Gym Owner/Trainer/Influencer/Dietitian';
                    break;
                case 'all_shop':
                    $display[] = 'All Shop Owner';
                    break;
                default:
                    $display[] = ucfirst(str_replace('_', ' ', $type));
            }
        }
        
        return implode(', ', $display);
    }

    /**
     * Scope to get only active references
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now());
    }

    /**
     * Scope to get expired references
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to get upcoming references
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Get the user who created this coupon
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this coupon
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get coupon assignments to users
     */
    public function userAssignments()
    {
        return $this->hasMany(ReferenceAssign::class)->where('assignable_type', 'user');
    }

    /**
     * Get coupon assignments to products
     */
    public function productAssignments()
    {
        return $this->hasMany(ReferenceAssign::class)->where('assignable_type', 'product');
    }

    /**
     * Get coupon assignments to sellers
     */
    public function sellerAssignments()
    {
        return $this->hasMany(ReferenceAssign::class)->where('assignable_type', 'seller');
    }

    /**
     * Check if coupon is assigned to all products
     */
    public function isAssignedToAllProducts()
    {
        return $this->hasMany(ReferenceAssign::class)->where('assignable_type', 'all_products')->exists();
    }

    /**
     * Get all assignments for this coupon
     */
    public function assignments()
    {
        return $this->hasMany(ReferenceAssign::class);
    }

    /**
     * Get coupon usage records
     */
    public function usages()
    {
        return $this->hasMany(ReferenceUsage::class);
    }

    /**
     * Check if coupon is currently valid
     */
    public function isValid()
    {
        return $this->status === 'active' &&
               $this->starts_at <= now() &&
               $this->expires_at >= now() &&
               ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    /**
     * Check if coupon is expired
     */
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    /**
     * Check if coupon is upcoming
     */
    public function isUpcoming()
    {
        return $this->starts_at > now();
    }

    /**
     * Check if coupon has reached usage limit
     */
    public function hasReachedLimit()
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Calculate discount amount for given total
     * @param float $total The total amount
     * @param string $discountType Either 'giver' or 'applyer'
     */
    public function calculateDiscount($total, $discountType = 'giver')
    {
        if ($this->minimum_amount && $total < $this->minimum_amount) {
            return [
                'giver_discount' => 0,
                'applyer_discount' => 0,
                'total_discount' => 0
            ];
        }

        $giverDiscount = 0;
        $applyerDiscount = 0;

        if ($this->type === 'percentage') {
            $giverDiscount = ($total * $this->giver_discount) / 100;
            $applyerDiscount = ($total * $this->applyer_discount) / 100;
            
            if ($this->maximum_discount) {
                $totalCalculatedDiscount = $giverDiscount + $applyerDiscount;
                if ($totalCalculatedDiscount > $this->maximum_discount) {
                    // Proportionally reduce both discounts
                    $ratio = $this->maximum_discount / $totalCalculatedDiscount;
                    $giverDiscount *= $ratio;
                    $applyerDiscount *= $ratio;
                }
            }
        } elseif ($this->type === 'fixed') {
            // For fixed amount, split between giver and applyer based on their percentage ratio
            $totalPercentage = $this->giver_discount + $this->applyer_discount;
            if ($totalPercentage > 0) {
                $totalDiscount = min($this->giver_discount + $this->applyer_discount, $total);
                $giverDiscount = ($this->giver_discount / $totalPercentage) * $totalDiscount;
                $applyerDiscount = ($this->applyer_discount / $totalPercentage) * $totalDiscount;
            }
        }

        return [
            'giver_discount' => round($giverDiscount, 2),
            'applyer_discount' => round($applyerDiscount, 2),
            'total_discount' => round($giverDiscount + $applyerDiscount, 2)
        ];
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        if ($this->isExpired()) {
            return 'red';
        }
        
        if ($this->isUpcoming()) {
            return 'yellow';
        }
        
        if ($this->status === 'active') {
            return 'green';
        }
        
        return 'gray';
    }

    /**
     * Get human readable status
     */
    public function getHumanStatusAttribute()
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->isUpcoming()) {
            return 'Upcoming';
        }
        
        return ucfirst($this->status);
    }

    /**
     * Assign coupon to a model (User, Product, Seller)
     */
    public function assignTo($model, $assignedBy = null)
    {
        $assignableType = $this->getAssignableType($model);
        
        return ReferenceAssign::create([
            'reference_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Remove assignment from a model
     */
    public function removeAssignmentFrom($model)
    {
        $assignableType = $this->getAssignableType($model);
        
        return ReferenceAssign::where([
            'reference_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
        ])->delete();
    }

    /**
     * Check if coupon is assigned to a model
     */
    public function isAssignedTo($model)
    {
        $assignableType = $this->getAssignableType($model);
        
        return ReferenceAssign::where([
            'reference_id' => $this->id,
            'assignable_type' => $assignableType,
            'assignable_id' => $model->id,
        ])->exists();
    }

    /**
     * Get the correct assignable type enum value for a model
     */
    private function getAssignableType($model)
    {
        $className = get_class($model);
        
        switch ($className) {
            case 'App\Models\User':
                return 'user';
            case 'App\Models\products':
                return 'product';
            case 'App\Models\Sellers':
                return 'seller';
            case 'all_products':
                return 'all_products';
            default:
                throw new \InvalidArgumentException("Unsupported model type: {$className}");
        }
    }
}
