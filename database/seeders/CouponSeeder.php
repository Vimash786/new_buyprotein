<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $coupons = [
            [
                'name' => 'New Year Special',
                'code' => 'NEWYEAR2025',
                'type' => 'percentage',
                'value' => 20.00,
                'min_amount' => 50.00,
                'max_discount' => 100.00,
                'usage_limit' => 1000,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(30),
                'status' => 'active',
                'description' => 'Get 20% off on your first purchase this year!',
            ],
            [
                'name' => 'Summer Sale',
                'code' => 'SUMMER50',
                'type' => 'fixed',
                'value' => 50.00,
                'min_amount' => 200.00,
                'max_discount' => null,
                'usage_limit' => 500,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(60),
                'status' => 'active',
                'description' => 'Fixed $50 discount on orders above $200',
            ],
            [
                'name' => 'First Time Buyer',
                'code' => 'WELCOME15',
                'type' => 'percentage',
                'value' => 15.00,
                'min_amount' => 30.00,
                'max_discount' => 50.00,
                'usage_limit' => null,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(90),
                'status' => 'active',
                'description' => '15% off for first time buyers',
            ],
            [
                'name' => 'VIP Member Discount',
                'code' => 'VIP25',
                'type' => 'percentage',
                'value' => 25.00,
                'min_amount' => 100.00,
                'max_discount' => 200.00,
                'usage_limit' => 100,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(180),
                'status' => 'active',
                'description' => 'Exclusive 25% discount for VIP members',
            ],
            [
                'name' => 'Weekend Flash Sale',
                'code' => 'WEEKEND10',
                'type' => 'fixed',
                'value' => 10.00,
                'min_amount' => 50.00,
                'max_discount' => null,
                'usage_limit' => 2000,
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(7),
                'status' => 'active',
                'description' => '$10 off weekend special',
            ],
            [
                'name' => 'Expired Sample',
                'code' => 'EXPIRED',
                'type' => 'percentage',
                'value' => 30.00,
                'min_amount' => 25.00,
                'max_discount' => 75.00,
                'usage_limit' => 50,
                'valid_from' => Carbon::now()->subDays(30),
                'valid_to' => Carbon::now()->subDays(1),
                'status' => 'inactive',
                'description' => 'This is an expired coupon for testing',
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
