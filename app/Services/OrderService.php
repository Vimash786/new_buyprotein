<?php

namespace App\Services;

use App\Models\BillingDetail;
use App\Models\orders;
use App\Models\OrderSellerProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new order with multiple seller products.
     */
    public function createOrder($userId, $cartItems, $billingData)
    {
        return DB::transaction(function () use ($userId, $cartItems, $billingData) {
            // Create the main order
            $order = orders::create([
                'user_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'overall_status' => 'pending',
                'total_order_amount' => 0, // Will be calculated below
            ]);

            $totalAmount = 0;

            // Create order items for each seller-product combination
            foreach ($cartItems as $item) {
                $orderSellerProduct = OrderSellerProduct::create([
                    'order_id' => $order->id,
                    'seller_id' => $item['seller_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['quantity'] * $item['unit_price'],
                    'status' => 'pending',
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalAmount += $orderSellerProduct->total_amount;
            }

            // Update the total order amount
            $order->update(['total_order_amount' => $totalAmount]);

            // Create billing details
            $billingDetail = BillingDetail::create([
                'order_id' => $order->id,
                'billing_name' => $billingData['name'],
                'billing_email' => $billingData['email'],
                'billing_phone' => $billingData['phone'],
                'billing_address' => $billingData['address'],
                'billing_city' => $billingData['city'],
                'billing_state' => $billingData['state'],
                'billing_postal_code' => $billingData['postal_code'],
                'billing_country' => $billingData['country'] ?? 'India',
                'gst_number' => $billingData['gst_number'] ?? null,
                'subtotal' => $totalAmount,
                'tax_amount' => $billingData['tax_amount'] ?? 0,
                'shipping_charge' => $billingData['shipping_charge'] ?? 0,
                'discount_amount' => $billingData['discount_amount'] ?? 0,
                'total_amount' => $totalAmount + ($billingData['tax_amount'] ?? 0) + ($billingData['shipping_charge'] ?? 0) - ($billingData['discount_amount'] ?? 0),
                'payment_method' => $billingData['payment_method'] ?? null,
                'payment_status' => 'pending',
            ]);

            return $order->load(['orderSellerProducts.seller', 'orderSellerProducts.product', 'billingDetail']);
        });
    }

    /**
     * Get orders for a specific seller.
     */
    public function getSellerOrders($sellerId, $status = null)
    {
        $query = OrderSellerProduct::where('seller_id', $sellerId)
            ->with(['order.user', 'product', 'order.billingDetail']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get order details with all seller products.
     */
    public function getOrderWithDetails($orderId)
    {
        return orders::with([
            'orderSellerProducts.seller',
            'orderSellerProducts.product',
            'billingDetail',
            'user'
        ])->findOrFail($orderId);
    }

    /**
     * Update order item status and recalculate overall order status.
     */
    public function updateOrderItemStatus($orderSellerProductId, $status, $notes = null)
    {
        $orderItem = OrderSellerProduct::findOrFail($orderSellerProductId);
        
        $orderItem->update([
            'status' => $status,
            'notes' => $notes
        ]);

        // Update overall order status
        $this->updateOverallOrderStatus($orderItem->order);

        return $orderItem->fresh(['order', 'seller', 'product']);
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (orders::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Update overall order status based on item statuses.
     */
    private function updateOverallOrderStatus($order)
    {
        $orderItems = $order->orderSellerProducts;
        $statuses = $orderItems->pluck('status')->unique();

        if ($statuses->count() === 1) {
            $newStatus = $statuses->first();
            if ($newStatus === 'delivered') {
                $order->update(['overall_status' => 'completed']);
            } elseif ($newStatus === 'shipped') {
                $order->update(['overall_status' => 'partially_shipped']);
            } else {
                $order->update(['overall_status' => $newStatus]);
            }
        } else {
            if ($statuses->contains('shipped') || $statuses->contains('delivered')) {
                $order->update(['overall_status' => 'partially_shipped']);
            } else {
                $order->update(['overall_status' => 'processing']);
            }
        }
    }
}
