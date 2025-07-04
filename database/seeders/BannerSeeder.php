<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create banners directory if it doesn't exist
        if (!Storage::disk('public')->exists('banners')) {
            Storage::disk('public')->makeDirectory('banners');
        }

        // Create sample banners
        $banners = [
            [
                'name' => 'Summer Sale Banner',
                'banner_image' => 'banners/summer-sale.jpg',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'New Product Launch',
                'banner_image' => 'banners/new-product.jpg',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Protein Powder Special',
                'banner_image' => 'banners/protein-special.jpg',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Fitness Equipment Sale',
                'banner_image' => 'banners/fitness-equipment.jpg',
                'status' => 'inactive',
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'name' => 'Bulk Purchase Discount',
                'banner_image' => 'banners/bulk-discount.jpg',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }

        // Create additional random banners using factory
        if (User::count() > 0) {
            Banner::factory(5)->create();
        }
    }
}
