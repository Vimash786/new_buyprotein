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

        // Create customer object for invoice (handle both registered and guest users)
        $customer = $order->user;
        if (!$customer && $order->billingDetail) {
            // For guest users, create a customer object from billing details
            $customer = (object) [
                'name' => trim(($order->billingDetail->billing_first_name ?? '') . ' ' . ($order->billingDetail->billing_last_name ?? '')),
                'email' => $order->guest_email ?? 'N/A'
            ];
            // If no name in billing details, use generic guest name
            if (empty(trim($customer->name))) {
                $customer->name = 'Guest Customer';
            }
        }

        // Generate PDF
        $pdf = PDF::loadView('invoices.standard-invoice', [
            'type' => 'seller',
            'orderItem' => $orderItem,
            'order' => $order,
            'seller' => $seller,
            'customer' => $customer,
            'billingDetail' => $order->billingDetail,
            'shippingAddress' => $order->billingDetail->shippingAddress ?? null
        ]);
        // return view('invoices.standard-invoice', [
        //     'type' => 'seller',
        //     'orderItem' => $orderItem,
        //     'order' => $order,
        //     'seller' => $seller,
        //     'customer' => $customer,
        //     'billingDetail' => $order->billingDetail,
        //     'shippingAddress' => $order->billingDetail->shippingAddress ?? null
        // ]);
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

        // Create customer object for invoice (handle both registered and guest users)
        $customer = $order->user;
        if (!$customer && $order->billingDetail) {
            // For guest users, create a customer object from billing details
            $customer = (object) [
                'name' => trim(($order->billingDetail->billing_first_name ?? '') . ' ' . ($order->billingDetail->billing_last_name ?? '')),
                'email' => $order->guest_email ?? 'N/A'
            ];
            // If no name in billing details, use generic guest name
            if (empty(trim($customer->name))) {
                $customer->name = 'Guest Customer';
            }
        }

        // Generate PDF
        $pdf = PDF::loadView('invoices.standard-invoice', [
            'type' => 'admin',
            'order' => $order,
            'customer' => $customer,
            'billingDetail' => $order->billingDetail,
            'shippingAddress' => $order->billingDetail->shippingAddress ?? null
        ]);

        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }
}
