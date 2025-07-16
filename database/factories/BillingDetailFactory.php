<?php

namespace Database\Factories;

use App\Models\orders;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingDetail>
 */
class BillingDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100.00, 5000.00);
        $taxAmount = $subtotal * 0.18; // 18% GST
        $shippingCharge = $this->faker->randomFloat(2, 0.00, 100.00);
        $discountAmount = $this->faker->randomFloat(2, 0.00, 200.00);
        $totalAmount = $subtotal + $taxAmount + $shippingCharge - $discountAmount;

        return [
            'order_id' => orders::factory(),
            'billing_name' => $this->faker->name(),
            'billing_email' => $this->faker->email(),
            'billing_phone' => $this->faker->phoneNumber(),
            'billing_address' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->state(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => 'India',
            'gst_number' => $this->faker->optional()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}[Z]{1}[A-Z0-9]{1}'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_charge' => $shippingCharge,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $this->faker->randomElement(['razorpay', 'paytm', 'card', 'upi', 'cod']),
            'payment_status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
        ];
    }
}
