<?php

namespace Database\Seeders;

use App\Models\Policy;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = [
            [
                'type' => 'about-us',
                'title' => 'About Us',
                'content' => '<h2>Welcome to BuyProtein</h2><p>We are a leading marketplace for premium protein supplements and fitness nutrition products...</p>',
                'is_active' => true,
                'meta_title' => 'About Us - BuyProtein',
                'meta_description' => 'Learn more about BuyProtein, your trusted partner for premium protein supplements and fitness nutrition.',
            ],
            [
                'type' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'content' => '<h2>Terms and Conditions</h2><p>By using our website, you agree to these terms and conditions...</p>',
                'is_active' => true,
                'meta_title' => 'Terms & Conditions - BuyProtein',
                'meta_description' => 'Read our terms and conditions for using BuyProtein marketplace.',
            ],
            [
                'type' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'content' => '<h2>Shipping Policy</h2><p>We offer fast and reliable shipping across India...</p>',
                'is_active' => true,
                'meta_title' => 'Shipping Policy - BuyProtein',
                'meta_description' => 'Learn about our shipping policies, delivery times, and shipping charges.',
            ],
            [
                'type' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how we collect and use your information...</p>',
                'is_active' => true,
                'meta_title' => 'Privacy Policy - BuyProtein',
                'meta_description' => 'Read our privacy policy to understand how we protect your personal information.',
            ],
            [
                'type' => 'return-policy',
                'title' => 'Return Policy',
                'content' => '<h2>Return Policy</h2><p>We accept returns within 30 days of purchase for unopened products...</p>',
                'is_active' => true,
                'meta_title' => 'Return Policy - BuyProtein',
                'meta_description' => 'Learn about our return and refund policy for products purchased on BuyProtein.',
            ],
        ];

        foreach ($policies as $policy) {
            Policy::updateOrCreate(
                ['type' => $policy['type']],
                $policy
            );
        }
    }
}
