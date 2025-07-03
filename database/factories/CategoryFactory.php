<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Protein Supplements',
            'Pre-Workout',
            'Post-Workout',
            'Vitamins & Minerals',
            'Weight Management',
            'Health & Wellness',
            'Sports Nutrition',
            'Fitness Equipment',
            'Gym Accessories',
            'Apparel & Clothing'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'description' => $this->faker->sentence(10),
            'image' => $this->getRandomCategoryImage(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Get a random category image from storage
     */
    private function getRandomCategoryImage(): ?string
    {
        $storagePath = public_path('storage/categories');
        
        if (!File::exists($storagePath)) {
            return null;
        }

        $images = File::files($storagePath);
        if (empty($images)) {
            return null;
        }

        $randomImage = $this->faker->randomElement($images);
        return 'categories/' . $randomImage->getFilename();
    }

    /**
     * Indicate that the category should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
