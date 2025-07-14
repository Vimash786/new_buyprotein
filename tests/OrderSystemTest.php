<?php

// Test the new order system functionality
// This file demonstrates how the new order system works

use App\Models\BillingDetail;
use App\Models\orders;
use App\Models\OrderSellerProduct;
use App\Models\products;
use App\Models\Sellers;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

class OrderSystemTest
{
    public function testCompleteOrderFlow()
    {
        // Create test data
        $user = User::factory()->create();
        $seller1 = Sellers::factory()->create();
        $seller2 = Sellers::factory()->create();
        $product1 = products::factory()->create(['seller_id' => $seller1->id]);
        $product2 = products::factory()->create(['seller_id' => $seller1->id]);
        $product3 = products::factory()->create(['seller_id' => $seller2->id]);

        // Create an order with multiple seller products
        $orderService = new OrderService();
        
        $cartItems = [
            [
                'seller_id' => $seller1->id,
                'product_id' => $product1->id,
                'quantity' => 2,
                'unit_price' => 500.00,
                'notes' => 'Handle with care'
            ],
            [
                'seller_id' => $seller1->id,
                'product_id' => $product2->id,
                'quantity' => 1,
                'unit_price' => 300.00
            ],
            [
                'seller_id' => $seller2->id,
                'product_id' => $product3->id,
                'quantity' => 3,
                'unit_price' => 200.00,
                'notes' => 'Express delivery'
            ]
        ];

        $billingData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+91 9876543210',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '123456',
            'country' => 'India',
            'tax_amount' => 234.00,
            'shipping_charge' => 50.00,
            'discount_amount' => 30.00,
            'payment_method' => 'razorpay'
        ];

        $order = $orderService->createOrder($user->id, $cartItems, $billingData);

        // Test order was created correctly
        assert($order->orderSellerProducts->count() === 3, 'Order should have 3 items');
        assert($order->total_order_amount == 1900.00, 'Total should be 1900.00'); // (2*500) + (1*300) + (3*200)
        assert($order->billingDetail !== null, 'Billing detail should exist');

        // Test seller relationships
        $seller1Orders = $seller1->orderSellerProducts()->get();
        $seller2Orders = $seller2->orderSellerProducts()->get();
        
        assert($seller1Orders->count() === 2, 'Seller 1 should have 2 items');
        assert($seller2Orders->count() === 1, 'Seller 2 should have 1 item');

        // Test order totals by seller
        $seller1Total = $order->getSellerTotal($seller1->id);
        $seller2Total = $order->getSellerTotal($seller2->id);
        
        assert($seller1Total == 1300.00, 'Seller 1 total should be 1300.00'); // (2*500) + (1*300)
        assert($seller2Total == 600.00, 'Seller 2 total should be 600.00');   // (3*200)

        // Test status updates
        $orderItem = $seller1Orders->first();
        $orderService->updateOrderItemStatus($orderItem->id, 'shipped', 'Shipped via courier');
        
        $updatedItem = OrderSellerProduct::find($orderItem->id);
        assert($updatedItem->status === 'shipped', 'Item status should be updated');

        // Test seller analytics
        $totalSales = $seller1->getTotalSales();
        $pendingOrders = $seller1->getPendingOrders();
        
        // Should be 0 since no orders are delivered yet
        assert($totalSales == 0, 'Total sales should be 0 for non-delivered orders');
        assert($pendingOrders->count() >= 1, 'Should have pending orders');

        echo "✅ All tests passed! The new order system is working correctly.\n";
        
        return [
            'order' => $order,
            'seller1_items' => $seller1Orders,
            'seller2_items' => $seller2Orders,
            'billing' => $order->billingDetail
        ];
    }

    public function testSellerOrderQueries()
    {
        $seller = Sellers::first();
        
        if (!$seller) {
            echo "⚠️ No sellers found. Create some test data first.\n";
            return;
        }

        // Test different query methods
        $queries = [
            'All orders' => $seller->orderSellerProducts()->count(),
            'Pending orders' => $seller->getOrdersByStatus('pending')->count(),
            'Total sales' => $seller->getTotalSales(),
            'All order relationships' => $seller->orders()->count()
        ];

        echo "📊 Seller Order Queries for Seller ID {$seller->id}:\n";
        foreach ($queries as $description => $result) {
            echo "   {$description}: {$result}\n";
        }

        return $queries;
    }

    public function testOrderRelationships()
    {
        $order = orders::with(['orderSellerProducts', 'sellers', 'products', 'billingDetail'])->first();
        
        if (!$order) {
            echo "⚠️ No orders found. Create some test data first.\n";
            return;
        }

        echo "🔗 Order Relationships Test for Order #{$order->order_number}:\n";
        echo "   Order Items: " . $order->orderSellerProducts->count() . "\n";
        echo "   Unique Sellers: " . $order->sellers->count() . "\n";
        echo "   Unique Products: " . $order->products->count() . "\n";
        echo "   Has Billing: " . ($order->billingDetail ? 'Yes' : 'No') . "\n";
        echo "   Total Amount: ₹" . number_format((float)$order->total_order_amount, 2) . "\n";

        return $order;
    }
}

// Usage example:
// $test = new OrderSystemTest();
// $test->testCompleteOrderFlow();
// $test->testSellerOrderQueries();
// $test->testOrderRelationships();
