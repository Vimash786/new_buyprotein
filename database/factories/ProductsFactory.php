<?php

namespace Database\Factories;

use App\Models\sellers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\products>
 */
class ProductsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $proteinProducts = [
            'Whey Protein Isolate',
            'Casein Protein Powder',
            'Plant-Based Protein',
            'Mass Gainer XXL',
            'Pre-Workout Boost',
            'Post-Workout Recovery',
            'Creatine Monohydrate',
            'BCAA Complex',
            'Vitamin D3',
            'Omega-3 Fish Oil'
        ];

        // Get random thumbnail image
        $thumbnailImage = $this->getRandomThumbnailImage();

        return [
            'seller_id' => sellers::factory(),
            'name' => $this->faker->randomElement($proteinProducts),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->randomFloat(2, 15.99, 199.99),
            'discount_percentage' => $this->faker->randomFloat(2, 0, 30), // 0-30% discount
            'discounted_price' => function (array $attributes) {
                if ($attributes['discount_percentage'] > 0) {
                    return $attributes['price'] * (1 - $attributes['discount_percentage'] / 100);
                }
                return null;
            },
            'stock_quantity' => $this->faker->numberBetween(10, 500),
            'category_id' => $this->faker->numberBetween(1, 9), // 9 main categories will be created by CategorySeeder
            'sub_category_id' => $this->faker->optional(0.7)->numberBetween(1, 45), // About 45 total subcategories
            'section_category' => $this->faker->randomElement(['everyday_essential', 'popular_pick', 'exclusive_deal']),
            'thumbnail_image' => $thumbnailImage,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'has_variants' => $this->faker->boolean(30), // 30% chance of having variants
        ];
    }

    /**
     * Get a random thumbnail image from storage
     */
    private function getRandomThumbnailImage(): ?string
    {
        $storagePath = public_path('storage/products/thumbnails');
        
        if (!File::exists($storagePath)) {
            return null;
        }

        $images = File::files($storagePath);
        if (empty($images)) {
            return null;
        }

        $randomImage = $this->faker->randomElement($images);
        return 'products/thumbnails/' . $randomImage->getFilename();
    }
}
