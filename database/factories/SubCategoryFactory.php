<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubCategory>
 */
class SubCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subCategories = [
            'Whey Protein',
            'Casein Protein',
            'Plant Protein',
            'Isolate Protein',
            'Mass Gainers',
            'Caffeine Based',
            'Creatine',
            'BCAA',
            'Recovery Drinks',
            'Multivitamins',
            'Vitamin D',
            'Omega-3',
            'Fat Burners',
            'Meal Replacements',
            'Dumbbells',
            'Resistance Bands',
            'Yoga Mats',
            'Shaker Bottles',
            'Gym Gloves',
            'Workout Clothes'
        ];

        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->randomElement($subCategories),
            'description' => $this->faker->sentence(8),
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the sub-category should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the sub-category should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
