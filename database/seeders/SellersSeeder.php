<?php

namespace Database\Seeders;

use App\Models\Sellers;
use App\Models\products;
use App\Models\orders;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SellersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 sellers
        Sellers::factory(10)->create()->each(function ($seller) {
            // Each seller has exactly 5 products
            $products = products::factory(5)->create([
                'seller_id' => $seller->id,
            ]);

            // For each product, create at least 2 orders
            $products->each(function ($product) {
                $orderCount = rand(2, 5); // At least 2, max 5 orders per product
                
                orders::factory($orderCount)->create([
                    'product_id' => $product->id,
                    'user_id' => User::factory()->create()->id,
                    'unit_price' => $product->price,
                    'total_amount' => function (array $attributes) use ($product) {
                        return $attributes['quantity'] * $product->price;
                    }
                ]);
            });
        });
    }
}
