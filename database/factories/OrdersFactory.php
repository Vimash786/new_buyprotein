<?php

namespace Database\Factories;

use App\Models\products;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\orders>
 */
class OrdersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper($this->faker->bothify('??####')),
            'overall_status' => $this->faker->randomElement(['pending', 'processing', 'partially_shipped', 'completed', 'cancelled']),
            'total_order_amount' => $this->faker->randomFloat(2, 100.00, 5000.00),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']),
        ];
    }
}
