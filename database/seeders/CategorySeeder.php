<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get available category images
        $categoryImages = $this->getCategoryImages();
        // Create main categories with their subcategories
        $categories = [
            'Protein Supplements' => [
                'Whey Protein',
                'Casein Protein',
                'Plant Protein',
                'Isolate Protein',
                'Mass Gainers'
            ],
            'Pre-Workout' => [
                'Caffeine Based',
                'Stimulant Free',
                'Creatine',
                'BCAA',
                'Energy Boosters'
            ],
            'Post-Workout' => [
                'Recovery Drinks',
                'Protein Recovery',
                'Glutamine',
                'Electrolytes',
                'Anti-Inflammatory'
            ],
            'Vitamins & Minerals' => [
                'Multivitamins',
                'Vitamin D',
                'Vitamin C',
                'B-Complex',
                'Omega-3',
                'Calcium',
                'Magnesium'
            ],
            'Weight Management' => [
                'Fat Burners',
                'Meal Replacements',
                'Appetite Suppressants',
                'Metabolism Boosters',
                'Carb Blockers'
            ],
            'Health & Wellness' => [
                'Probiotics',
                'Digestive Health',
                'Immune Support',
                'Joint Health',
                'Heart Health'
            ],
            'Fitness Equipment' => [
                'Dumbbells',
                'Resistance Bands',
                'Kettlebells',
                'Foam Rollers',
                'Exercise Mats'
            ],
            'Gym Accessories' => [
                'Shaker Bottles',
                'Gym Bags',
                'Water Bottles',
                'Towels',
                'Gym Gloves',
                'Lifting Straps'
            ],
            'Apparel & Clothing' => [
                'Workout Clothes',
                'Athletic Shoes',
                'Compression Wear',
                'Activewear',
                'Sports Bras'
            ]
        ];

        $sortOrder = 1;
        $imageIndex = 0;
        foreach ($categories as $categoryName => $subCategories) {
            // Get random image from available category images
            $imagePath = null;
            if (!empty($categoryImages) && isset($categoryImages[$imageIndex % count($categoryImages)])) {
                $imagePath = 'categories/' . $categoryImages[$imageIndex % count($categoryImages)];
            }
            
            $category = Category::create([
                'name' => $categoryName,
                'description' => "High-quality {$categoryName} for fitness enthusiasts",
                'image' => $imagePath,
                'is_active' => true,
                'sort_order' => $sortOrder++,
            ]);

            $subSortOrder = 1;
            foreach ($subCategories as $subCategoryName) {
                SubCategory::create([
                    'category_id' => $category->id,
                    'name' => $subCategoryName,
                    'description' => "Premium {$subCategoryName} products",
                    'is_active' => true,
                    'sort_order' => $subSortOrder++,
                ]);
            }
            $imageIndex++;
        }
    }

    /**
     * Get available category images from storage
     */
    private function getCategoryImages(): array
    {
        $storagePath = public_path('storage/categories');
        
        if (!File::exists($storagePath)) {
            return [];
        }

        $images = File::files($storagePath);
        return array_map(function ($file) {
            return $file->getFilename();
        }, $images);
    }
}
