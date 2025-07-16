<?php

// Example usage of the new order system

use App\Services\OrderService;
use App\Models\Sellers;
use App\Models\products;
use App\Models\orders;

class OrderExampleUsage
{
    private $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Example: Create an order with products from multiple sellers
     */
    public function createSampleOrder()
    {
        $userId = 1; // Customer ID

        // Cart items with products from different sellers
        $cartItems = [
            [
                'seller_id' => 1,
                'product_id' => 10,
                'quantity' => 2,
                'unit_price' => 500.00,
                'notes' => 'Handle with care'
            ],
            [
                'seller_id' => 1,
                'product_id' => 15,
                'quantity' => 1,
                'unit_price' => 300.00,
                'notes' => null
            ],
            [
                'seller_id' => 2, // Different seller
                'product_id' => 25,
                'quantity' => 3,
                'unit_price' => 200.00,
                'notes' => 'Express delivery preferred'
            ]
        ];

        $billingData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 9876543210',
            'address' => '123 Main Street, Sector 15',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
            'country' => 'India',
            'gst_number' => '07AAACH7409R1ZZ',
            'tax_amount' => 120.00,
            'shipping_charge' => 50.00,
            'discount_amount' => 30.00,
            'payment_method' => 'razorpay'
        ];

        $order = $this->orderService->createOrder($userId, $cartItems, $billingData);

        return $order;
    }

    /**
     * Example: How a seller can view their orders
     */
    public function getSellerOrdersExample($sellerId)
    {
        // Get all orders for seller
        $allOrders = $this->orderService->getSellerOrders($sellerId);

        // Get only pending orders for seller
        $pendingOrders = $this->orderService->getSellerOrders($sellerId, 'pending');

        return [
            'all_orders' => $allOrders,
            'pending_orders' => $pendingOrders
        ];
    }

    /**
     * Example: How to get complete order details
     */
    public function getCompleteOrderExample($orderId)
    {
        $order = $this->orderService->getOrderWithDetails($orderId);

        // This will include:
        // - Order basic info (order_number, total_amount, etc.)
        // - All seller products in this order
        // - Seller details for each product
        // - Product details
        // - Billing information
        // - Customer information

        return $order;
    }

    /**
     * Example: Update order status by seller
     */
    public function updateOrderStatusExample($orderSellerProductId)
    {
        // Seller updates their product status in the order
        $updatedItem = $this->orderService->updateOrderItemStatus(
            $orderSellerProductId,
            'shipped',
            'Shipped via BlueDart tracking: BD123456789'
        );

        return $updatedItem;
    }

    /**
     * Example: How to query orders using model relationships
     */
    public function queryExamples()
    {
        // Get a seller's total sales
        $seller = Sellers::find(1);
        $totalSales = $seller->getTotalSales();

        // Get pending orders for a seller
        $pendingOrders = $seller->getPendingOrders();

        // Get orders by status
        $shippedOrders = $seller->getOrdersByStatus('shipped');

        // Get all sellers involved in a particular order
        $order = orders::find(1);
        $sellersInOrder = $order->sellers;

        // Get all products in an order
        $productsInOrder = $order->products;

        // Get order items for a specific seller in an order
        $sellerOrderItems = $order->getSellerOrderItems(1);

        // Get total amount for a specific seller in an order
        $sellerTotal = $order->getSellerTotal(1);

        return [
            'total_sales' => $totalSales,
            'pending_orders' => $pendingOrders,
            'shipped_orders' => $shippedOrders,
            'sellers_in_order' => $sellersInOrder,
            'products_in_order' => $productsInOrder,
            'seller_order_items' => $sellerOrderItems,
            'seller_total' => $sellerTotal
        ];
    }
}
