<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ShippingAddress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users except super admin
        $users = User::where('role', '!=', 'Super')->get();

        foreach ($users as $user) {
            // Create 1-3 shipping addresses per user
            $addressCount = rand(1, 3);
            
            for ($i = 0; $i < $addressCount; $i++) {
                $shippingAddress = ShippingAddress::factory()->create([
                    'user_id' => $user->id,
                    'is_default' => $i === 0, // First address is default
                ]);
            }
        }

        // Create some specific addresses for the super admin user
        $superAdmin = User::where('email', 'super@gmail.com')->first();
        if ($superAdmin) {
            ShippingAddress::factory()->default()->create([
                'user_id' => $superAdmin->id,
                'recipient_phone' => '+91 9876543210',
                'address_line_1' => '123 Admin Tower',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
            ]);

            ShippingAddress::factory()->create([
                'user_id' => $superAdmin->id,
                'recipient_phone' => '+91 9876543210',
                'address_line_1' => '456 Home Avenue',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400002',
            ]);
        }
    }
}
