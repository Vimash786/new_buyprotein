<?php

namespace Database\Factories;

use App\Models\sellers;
use Illuminate\Database\Eloquent\Factories\Factory;

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

        return [
            'seller_id' => sellers::factory(),
            'name' => $this->faker->randomElement($proteinProducts),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->randomFloat(2, 15.99, 199.99),
            'stock_quantity' => $this->faker->numberBetween(10, 500),
            'category' => $this->faker->randomElement(['Whey Protein', 'Casein Protein', 'Plant Protein', 'Mass Gainers', 'Pre-Workout', 'Post-Workout', 'Vitamins', 'Creatine']),
            'brand' => $this->faker->randomElement(['MuscleTech', 'Optimum Nutrition', 'BSN', 'Dymatize', 'ProteinWorks', 'MyProtein', 'Universal', 'BioTech']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
