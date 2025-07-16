<?php

namespace App\Http\Controllers;

use App\Models\OrderSellerProduct;
use App\Models\Sellers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerOrderController extends Controller
{
    /**
     * Display all orders for the authenticated seller.
     */
    public function index()
    {
        $seller = Sellers::where('user_id', Auth::id())->first();
        
        if (!$seller) {
            return redirect()->back()->with('error', 'Seller profile not found.');
        }

        $orders = $seller->orderSellerProducts()
            ->with(['order.user', 'product', 'order.billingDetail'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('seller.orders.index', compact('orders', 'seller'));
    }

    /**
     * Display orders with specific status.
     */
    public function byStatus($status)
    {
        $seller = Sellers::where('user_id', Auth::id())->first();
        
        if (!$seller) {
            return redirect()->back()->with('error', 'Seller profile not found.');
        }

        $orders = $seller->getOrdersByStatus($status);

        return view('seller.orders.by-status', compact('orders', 'seller', 'status'));
    }

    /**
     * Update order status for seller's product.
     */
    public function updateStatus(Request $request, OrderSellerProduct $orderSellerProduct)
    {
        $seller = Sellers::where('user_id', Auth::id())->first();
        
        // Ensure the order item belongs to the authenticated seller
        if ($orderSellerProduct->seller_id !== $seller->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled'
        ]);

        $orderSellerProduct->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        // Update overall order status if needed
        $this->updateOverallOrderStatus($orderSellerProduct->order);

        return response()->json(['success' => 'Order status updated successfully']);
    }

    /**
     * Get seller's sales analytics.
     */
    public function analytics()
    {
        $seller = Sellers::where('user_id', Auth::id())->first();
        
        if (!$seller) {
            return redirect()->back()->with('error', 'Seller profile not found.');
        }

        $analytics = [
            'total_sales' => $seller->getTotalSales(),
            'pending_orders' => $seller->getPendingOrders()->count(),
            'total_orders' => $seller->orderSellerProducts()->count(),
            'monthly_sales' => $seller->orderSellerProducts()
                ->whereMonth('created_at', now()->month)
                ->whereIn('status', ['delivered', 'completed'])
                ->sum('total_amount')
        ];

        return view('seller.analytics', compact('analytics', 'seller'));
    }

    /**
     * Update overall order status based on seller product statuses.
     */
    private function updateOverallOrderStatus($order)
    {
        $orderItems = $order->orderSellerProducts;
        $statuses = $orderItems->pluck('status')->unique();

        if ($statuses->count() === 1) {
            // All items have the same status
            $newStatus = $statuses->first();
            if ($newStatus === 'delivered') {
                $order->update(['overall_status' => 'completed']);
            } elseif ($newStatus === 'shipped') {
                $order->update(['overall_status' => 'partially_shipped']);
            } else {
                $order->update(['overall_status' => $newStatus]);
            }
        } else {
            // Mixed statuses
            if ($statuses->contains('shipped') || $statuses->contains('delivered')) {
                $order->update(['overall_status' => 'partially_shipped']);
            } else {
                $order->update(['overall_status' => 'processing']);
            }
        }
    }
}
