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
        $categories = ['Protein Supplements', 'Pre-Workout', 'Post-Workout', 'Vitamins & Minerals', 'Weight Management', 'Health & Wellness', 'Fitness Equipment', 'Gym Accessories'];
        
        return [
            'user_id' => \App\Models\User::factory()->seller(),
            'company_name' => $this->faker->company(),
            'gst_number' => $this->faker->unique()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
           'product_category' => implode(',', $this->faker->randomElements(
                ['Protein Supplements', 'Pre-Workout', 'Post-Workout', 'Vitamins', 'Mass Gainers', 'Creatine'],
                $this->faker->numberBetween(1, 3)
            )),
            'contact_no' => '+91' . $this->faker->numerify('##########'),
            'brand' => $this->faker->randomElement(['MuscleTech', 'Optimum Nutrition', 'BSN', 'Dymatize', 'ProteinWorks', 'MyProtein', 'Universal', 'BioTech']),
            
            'brand_certificate' => $this->faker->optional()->word() . '_certificate.pdf',
            'status' => $this->faker->randomElement(['approved', 'not_approved']),
        ];
    }
}
