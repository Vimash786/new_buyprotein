<?php

namespace Database\Seeders;

use App\Models\Reference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $references = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount',
                'description' => 'Welcome discount for new users - 10% off first purchase',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 50.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 1000,
                'user_usage_limit' => 1,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'status' => 'active',
                'applicable_to' => 'users',
                'user_types' => ['User'],
            ],
            [
                'code' => 'BULK25',
                'name' => 'Bulk Order Discount',
                'description' => 'Fixed $25 off for bulk orders over $200',
                'type' => 'fixed',
                'value' => 25.00,
                'minimum_amount' => 200.00,
                'maximum_discount' => null,
                'usage_limit' => null,
                'user_usage_limit' => 5,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(3),
                'status' => 'active',
                'applicable_to' => 'all',
                'user_types' => null,
            ],
            [
                'code' => 'GYM15',
                'name' => 'Gym Owner Special',
                'description' => '15% discount for gym owners and trainers',
                'type' => 'percentage',
                'value' => 15.00,
                'minimum_amount' => 100.00,
                'maximum_discount' => 150.00,
                'usage_limit' => 500,
                'user_usage_limit' => 10,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(12),
                'status' => 'active',
                'applicable_to' => 'users',
                'user_types' => ['Gym Owner/Trainer/Influencer/Dietitian'],
            ],
            [
                'code' => 'SELLER20',
                'name' => 'Seller Appreciation',
                'description' => '20% discount for sellers on their own products',
                'type' => 'percentage',
                'value' => 20.00,
                'minimum_amount' => 75.00,
                'maximum_discount' => 200.00,
                'usage_limit' => null,
                'user_usage_limit' => null,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'status' => 'active',
                'applicable_to' => 'sellers',
                'user_types' => ['Seller'],
            ],
            [
                'code' => 'SUMMER30',
                'name' => 'Summer Sale',
                'description' => 'Summer special - 30% off selected products',
                'type' => 'percentage',
                'value' => 30.00,
                'minimum_amount' => 150.00,
                'maximum_discount' => 300.00,
                'usage_limit' => 200,
                'user_usage_limit' => 2,
                'starts_at' => Carbon::now()->addMonth(),
                'expires_at' => Carbon::now()->addMonths(4),
                'status' => 'active',
                'applicable_to' => 'products',
                'user_types' => null,
            ],
            [
                'code' => 'EXPIRED5',
                'name' => 'Expired Test Reference',
                'description' => 'This reference has expired (for testing)',
                'type' => 'percentage',
                'value' => 5.00,
                'minimum_amount' => 25.00,
                'maximum_discount' => 50.00,
                'usage_limit' => 100,
                'user_usage_limit' => 1,
                'starts_at' => Carbon::now()->subMonths(2),
                'expires_at' => Carbon::now()->subDays(10),
                'status' => 'active',
                'applicable_to' => 'all',
                'user_types' => null,
            ],
            [
                'code' => 'INACTIVE12',
                'name' => 'Inactive Reference',
                'description' => 'This reference is inactive (for testing)',
                'type' => 'percentage',
                'value' => 12.00,
                'minimum_amount' => 60.00,
                'maximum_discount' => 120.00,
                'usage_limit' => 50,
                'user_usage_limit' => 3,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(2),
                'status' => 'inactive',
                'applicable_to' => 'all',
                'user_types' => null,
            ],
        ];

        foreach ($references as $referenceData) {
            Reference::create($referenceData);
        }

        $this->command->info('Reference seeder completed successfully!');
        $this->command->info('Created ' . count($references) . ' reference records.');
    }
}
