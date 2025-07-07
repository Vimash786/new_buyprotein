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
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 15.99, 199.99);
        $totalAmount = $quantity * $unitPrice;

        return [
            'product_id' => products::factory(),
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => function () use ($quantity, $unitPrice) {
                $product = products::find($this->faker->randomElement(products::pluck('id')->toArray()));
                $finalPrice = $product->gym_owner_final_price ?? $product->regular_user_final_price ?? $product->shop_owner_final_price;

                if (is_null($finalPrice)) {
                    $variantPrice = $product->variants()->first()->price ?? $unitPrice;
                    return $quantity * $variantPrice;
                }

                return $quantity * $finalPrice;
            },
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
