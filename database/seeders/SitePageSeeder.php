<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

class SitePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'page_type' => 'about-us',
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h1>About Us</h1>
<p>Welcome to our website. We are dedicated to providing high-quality products and exceptional service to our customers.</p>

<h2>Our Mission</h2>
<p>Our mission is to deliver the best possible products while maintaining the highest standards of quality and customer satisfaction.</p>

<h2>Our Values</h2>
<ul>
    <li>Quality Excellence</li>
    <li>Customer Satisfaction</li>
    <li>Innovation</li>
    <li>Integrity</li>
</ul>

<h2>Contact Information</h2>
<p>If you have any questions or would like to learn more about us, please don\'t hesitate to contact us.</p>',
                'meta_title' => 'About Us - Learn More About Our Company',
                'meta_description' => 'Learn more about our company, mission, values, and commitment to providing excellent products and services.',
                'meta_keywords' => 'about us, company, mission, values, quality',
                'status' => true,
                'created_by' => 1,
            ],
            [
                'page_type' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'content' => '<h1>Terms & Conditions</h1>
<p>Please read these Terms and Conditions ("Terms", "Terms and Conditions") carefully before using our website.</p>

<h2>Acceptance of Terms</h2>
<p>By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement.</p>

<h2>Use License</h2>
<p>Permission is granted to temporarily download one copy of the materials on our website for personal, non-commercial transitory viewing only.</p>

<h2>Disclaimer</h2>
<p>The materials on our website are provided on an \'as is\' basis. We make no warranties, expressed or implied, and hereby disclaim and negate all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

<h2>Limitations</h2>
<p>In no event shall our company or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on our website.</p>

<h2>Contact Information</h2>
<p>If you have any questions about these Terms & Conditions, please contact us.</p>',
                'meta_title' => 'Terms & Conditions - Website Usage Terms',
                'meta_description' => 'Read our terms and conditions for using our website and services. Important legal information for all users.',
                'meta_keywords' => 'terms, conditions, legal, usage, website terms',
                'status' => true,
                'created_by' => 1,
            ],
            [
                'page_type' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h1>Privacy Policy</h1>
<p>This Privacy Policy describes how we collect, use, and protect your personal information when you use our website.</p>

<h2>Information We Collect</h2>
<p>We may collect information you provide directly to us, such as when you create an account, make a purchase, or contact us.</p>

<h2>How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
    <li>Provide and maintain our services</li>
    <li>Process transactions</li>
    <li>Send you updates and promotional materials</li>
    <li>Respond to your comments and questions</li>
    <li>Improve our website and services</li>
</ul>

<h2>Information Sharing</h2>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h2>Data Security</h2>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h2>Contact Us</h2>
<p>If you have any questions about this Privacy Policy, please contact us.</p>',
                'meta_title' => 'Privacy Policy - How We Protect Your Information',
                'meta_description' => 'Learn about our privacy policy and how we collect, use, and protect your personal information on our website.',
                'meta_keywords' => 'privacy, policy, data protection, personal information, security',
                'status' => true,
                'created_by' => 1,
            ],
            [
                'page_type' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => '<h1>Shipping Policy</h1>
<p>This shipping policy outlines our shipping procedures, delivery times, and costs.</p>

<h2>Shipping Methods</h2>
<p>We offer the following shipping options:</p>
<ul>
    <li>Standard Shipping (5-7 business days)</li>
    <li>Express Shipping (2-3 business days)</li>
    <li>Overnight Shipping (1 business day)</li>
</ul>

<h2>Shipping Costs</h2>
<p>Shipping costs are calculated based on the weight and size of your order and your location. You can view shipping costs during checkout.</p>

<h2>Processing Time</h2>
<p>Orders are typically processed within 1-2 business days. You will receive a confirmation email once your order has shipped.</p>

<h2>International Shipping</h2>
<p>We currently ship to select international destinations. International shipping times and costs vary by location.</p>

<h2>Delivery Issues</h2>
<p>If you experience any issues with your delivery, please contact our customer service team for assistance.</p>',
                'meta_title' => 'Shipping Policy - Delivery Information',
                'meta_description' => 'Learn about our shipping methods, delivery times, costs, and policies for domestic and international orders.',
                'meta_keywords' => 'shipping, delivery, policy, costs, international shipping',
                'status' => true,
                'created_by' => 1,
            ],
            [
                'page_type' => 'return-policy',
                'title' => 'Return Policy',
                'slug' => 'return-policy',
                'content' => '<h1>Return Policy</h1>
<p>We want you to be completely satisfied with your purchase. If you are not satisfied, we offer a comprehensive return policy.</p>

<h2>Return Window</h2>
<p>You may return most items within 30 days of delivery for a full refund or exchange.</p>

<h2>Return Conditions</h2>
<p>To be eligible for a return, items must be:</p>
<ul>
    <li>In original condition</li>
    <li>Unworn or unused</li>
    <li>In original packaging</li>
    <li>Include all original tags and accessories</li>
</ul>

<h2>Return Process</h2>
<p>To initiate a return:</p>
<ol>
    <li>Contact our customer service team</li>
    <li>Receive return authorization</li>
    <li>Package items securely</li>
    <li>Ship using provided return label</li>
</ol>

<h2>Refunds</h2>
<p>Refunds will be processed within 5-10 business days after we receive your returned items. Refunds will be issued to the original payment method.</p>

<h2>Exchanges</h2>
<p>We offer free exchanges for different sizes or colors of the same item, subject to availability.</p>',
                'meta_title' => 'Return Policy - Easy Returns & Refunds',
                'meta_description' => 'Learn about our hassle-free return policy, including return conditions, process, and refund information.',
                'meta_keywords' => 'return, policy, refund, exchange, satisfaction guarantee',
                'status' => true,
                'created_by' => 1,
            ],
        ];

        foreach ($pages as $pageData) {
            SitePage::updateOrCreate(
                ['page_type' => $pageData['page_type']],
                $pageData
            );
        }
    }
}
