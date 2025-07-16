<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create some base users first
        User::factory(20)->create();

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'super@gmail.com',
            'password' => bcrypt('Super@123'), // Ensure to set a password
            'role' => 'Super', // Assuming you have a role field
        ]);

        // Run the seeders to create the complete data hierarchy
        $this->call([
            CategorySeeder::class,
            SellersSeeder::class,
            BannerSeeder::class,
            BlogSeeder::class,
            ShippingAddressSeeder::class,
        ]);
    }
}
