<?php

namespace Database\Factories;

use App\Models\Sellers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payout>
 */
class PayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalSales = $this->faker->randomFloat(2, 1000, 10000);
        $commissionRate = $this->faker->randomFloat(2, 5, 25); // 5% to 25%
        $commissionAmount = $totalSales * ($commissionRate / 100);
        $payoutAmount = $totalSales - $commissionAmount;
        
        $periodStart = $this->faker->dateTimeBetween('-60 days', '-15 days');
        $periodEnd = Carbon::instance($periodStart)->addDays(15);
        
        return [
            'seller_id' => Sellers::factory(),
            'seller_name' => $this->faker->company(),
            'total_orders' => $this->faker->numberBetween(5, 50),
            'total_sales' => $totalSales,
            'commission_amount' => $commissionAmount,
            'payout_amount' => $payoutAmount,
            'due_date' => $this->faker->dateTimeBetween('now', '+10 days'),
            'payout_date' => $this->faker->dateTimeBetween('+10 days', '+25 days'),
            'payment_status' => $this->faker->randomElement(['paid', 'unpaid', 'processing', 'cancelled']),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
    
    /**
     * Indicate that the payout is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }
    
    /**
     * Indicate that the payout is unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
        ]);
    }
    
    /**
     * Indicate that the payout is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
            'due_date' => $this->faker->dateTimeBetween('-10 days', '-1 day'),
        ]);
    }
    
    /**
     * Indicate that the payout is due soon.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'unpaid',
            'due_date' => $this->faker->dateTimeBetween('now', '+5 days'),
        ]);
    }
}
