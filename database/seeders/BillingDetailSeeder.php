<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('billing_details')->insert([
            [
                'order_id' => 1,
                'billing_phone' => '+91 9876543210',
                'billing_address' => '123, MG Road, Near City Mall, Bangalore',
                'billing_city' => 'Bangalore',
                'billing_state' => 'Karnataka',
                'billing_postal_code' => '560001',
                'billing_country' => 'India',
                'gst_number' => '29ABCDE1234F1Z5',
                'shipping_address' => 1,
                'subtotal' => 2500.00,
                'tax_amount' => 450.00,
                'shipping_charge' => 100.00,
                'discount_amount' => 200.00,
                'total_amount' => 2850.00,
                'payment_method' => 'razorpay',
                'payment_status' => 'completed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'order_id' => 2,
                'billing_phone' => '+91 8765432109',
                'billing_address' => '456, Park Street, Sector 18, Noida',
                'billing_city' => 'Noida',
                'billing_state' => 'Uttar Pradesh',
                'billing_postal_code' => '201301',
                'billing_country' => 'India',
                'gst_number' => null,
                'shipping_address' => 0,
                'subtotal' => 1800.00,
                'tax_amount' => 324.00,
                'shipping_charge' => 75.00,
                'discount_amount' => 100.00,
                'total_amount' => 2099.00,
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
