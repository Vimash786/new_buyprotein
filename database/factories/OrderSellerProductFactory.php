<?php

namespace Database\Factories;

use App\Models\orders;
use App\Models\products;
use App\Models\Sellers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderSellerProduct>
 */
class OrderSellerProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 15.99, 199.99);
        $totalAmount = $quantity * $unitPrice;

        return [
            'order_id' => orders::factory(),
            'seller_id' => Sellers::factory(),
            'product_id' => products::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
