<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\sellers>
 */
class SellersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Whey Protein', 'Casein Protein', 'Plant Protein', 'Mass Gainers', 'Pre-Workout', 'Post-Workout', 'Vitamins', 'Creatine'];
        
        return [
            'user_id' => \App\Models\User::factory()->seller(),
            'company_name' => $this->faker->company(),
            'gst_number' => $this->faker->unique()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
            'product_category' => $this->faker->randomElements($categories, $this->faker->numberBetween(1, 3)),
            'contact_person' => $this->faker->name(),
            'brand_certificate' => $this->faker->optional()->word() . '_certificate.pdf',
            'status' => $this->faker->randomElement(['approved', 'not_approved']),
        ];
    }
}
