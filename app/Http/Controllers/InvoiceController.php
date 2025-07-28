<?php

namespace App\Http\Controllers;

use App\Models\OrderSellerProduct;
use App\Models\orders;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Download invoice PDF for a seller's order item
     */
    public function downloadSellerInvoice($orderItemId)
    {
        $user = Auth::user();
        $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
        
        if (!$seller) {
            abort(403, 'Access denied. Seller account required.');
        }

        // Get the order item and verify it belongs to this seller
        $orderItem = OrderSellerProduct::with([
            'order.user',
            'order.billingDetail.shippingAddress',
            'product.images',
            'variantCombination',
            'seller'
        ])->where('seller_id', $seller->id)->findOrFail($orderItemId);

        $order = $orderItem->order;
        
        // Generate PDF
        $pdf = PDF::loadView('invoices.seller-invoice', [
            'orderItem' => $orderItem,
            'order' => $order,
            'seller' => $seller,
            'customer' => $order->user,
            'billingDetail' => $order->billingDetail,
            'shippingAddress' => $order->billingDetail->shippingAddress ?? null
        ]);

        return $pdf->download('invoice-' . $order->order_number . '-' . $orderItem->id . '.pdf');
    }

    /**
     * Download invoice PDF for admin (full order)
     */
    public function downloadOrderInvoice($orderId)
    {
        $user = Auth::user();
        
        // Check if user is admin/super admin
        if (!in_array($user->role, ['Admin', 'Super'])) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $order = orders::with([
            'user',
            'orderSellerProducts.product.images',
            'orderSellerProducts.seller',
            'orderSellerProducts.variantCombination',
            'billingDetail.shippingAddress'
        ])->findOrFail($orderId);

        // Generate PDF
        $pdf = PDF::loadView('invoices.order-invoice', [
            'order' => $order,
            'customer' => $order->user,
            'billingDetail' => $order->billingDetail,
            'shippingAddress' => $order->billingDetail->shippingAddress ?? null
        ]);

        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }
}
