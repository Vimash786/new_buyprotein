<?php

namespace Database\Seeders;

use App\Models\Sellers;
use App\Models\products;
use App\Models\ProductImage;
use App\Models\orders;
use App\Models\OrderSellerProduct;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SellersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available product images
        $productImages = $this->getProductImages();
        
        // Create 10 sellers
        Sellers::factory(10)->create()->each(function ($seller) use ($productImages) {
            // Each seller has exactly 5 products
            $products = products::factory(5)->create([
                'seller_id' => $seller->id,
            ]);

            // For each product, create product images and orders
            $products->each(function ($product) use ($productImages, $seller) {
                // Create 2-4 product images for each product
                $imageCount = rand(2, 4);
                $usedImages = [];
                
                for ($i = 0; $i < $imageCount; $i++) {
                    if (!empty($productImages)) {
                        do {
                            $randomImage = $productImages[array_rand($productImages)];
                        } while (in_array($randomImage, $usedImages) && count($usedImages) < count($productImages));
                        
                        $usedImages[] = $randomImage;
                        
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => 'products/images/' . $randomImage,
                            'alt_text' => $product->name . ' - Image ' . ($i + 1),
                            'sort_order' => $i + 1,
                            'is_primary' => $i === 0, // First image is primary
                        ]);
                    }
                }
                
                // Create orders for each product
                $orderCount = rand(2, 5); // At least 2, max 5 orders per product
                
                for ($j = 0; $j < $orderCount; $j++) {
                    // Create an order first
                    $order = orders::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ]);
                    
                    // Create order seller product (the actual order item)
                    $quantity = rand(1, 5);
                    OrderSellerProduct::factory()->create([
                        'order_id' => $order->id,
                        'seller_id' => $seller->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->gym_owner_price,
                        'total_amount' => $quantity * $product->gym_owner_price,
                    ]);
                }
            });
        });
    }

    /**
     * Get available product images from storage
     */
    private function getProductImages(): array
    {
        $storagePath = public_path('storage/products/images');
        
        if (!File::exists($storagePath)) {
            return [];
        }

        $images = File::files($storagePath);
        return array_map(function ($file) {
            return $file->getFilename();
        }, $images);
    }
}
