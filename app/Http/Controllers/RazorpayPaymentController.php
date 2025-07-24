<?php

namespace App\Http\Controllers;

use App\Mail\SellerOrder;
use App\Mail\UserOrder;
use App\Mail\WelcomeMail;
use App\Models\BillingDetail;
use App\Models\Cart;
use App\Models\orders;
use App\Models\OrderSellerProduct;
use App\Models\products;
use App\Models\Sellers;
use App\Models\ShippingAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api;

class RazorpayPaymentController extends Controller
{
    public function payment(Request $request)
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
        $billing = $request->input('billing');
        $shipping = $request->input('shipping');
        $cartProducts = $request->input('products');
        $amount = $request->input('amount');

        $randomPart = 'BG' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $orderNumber = 'ORD-' . Carbon::now()->format('Ymd') . '-' . $randomPart;

        $order = orders::create([
            'order_number' => $orderNumber,
            'user_id' => Auth::user()->id,
            'total_order_amount' => $amount,
        ]);

        $orderId = $order->id;
        $shippingAddressid = null;

        if (isset($billing['existing_billing_id'])) {
            $existing = BillingDetail::find($billing['existing_billing_id']);

            $shippingAddress = ShippingAddress::create([
                'user_id' => Auth::user()->id,
                'recipient_phone' => $existing->billing_phone,
                'address_line_1' => $existing->billing_address,
                'city' => $existing->billing_city,
                'state' => $existing->billing_state,
                'postal_code' => $existing->billing_postal_code,
                'country' => 'India'
            ]);
            $existingShippingAddress = $shippingAddress->id;

            $billingDetails = BillingDetail::create([
                'order_id' => $orderId,
                'billing_phone' => $existing->billing_phone,
                'billing_address' => $existing->billing_address,
                'billing_city' => $existing->billing_city,
                'billing_state' => $existing->billing_state,
                'billing_postal_code' => $existing->billing_postal_code,
                'billing_country' => $existing->billing_country,
                'shipping_address' => $existingShippingAddress,
                'subtotal' => $amount - 100,
                'tax_amount' => 0,
                'shipping_charge' => 100,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'payment_method' => 'razorpay',
                'payment_status' => 'complete',
            ]);
        } else {

            $shippingAddress = ShippingAddress::create([
                'user_id' => Auth::user()->id,
                'recipient_phone' => $shipping['phone'],
                'address_line_1' => $shipping['street'],
                'city' => $shipping['city'],
                'state' => $shipping['state'],
                'postal_code' => $shipping['zip'],
                'country' => 'India'
            ]);

            $billingDetails = BillingDetail::create([
                'order_id' => $orderId,
                'billing_phone' => $billing['phone'],
                'billing_address' => $billing['street'],
                'billing_city' => $billing['city'],
                'billing_state' => $billing['state'],
                'billing_postal_code' => $billing['zip'],
                'billing_country' => 'India',
                'shipping_address' => $shippingAddress->id,
                'subtotal' => $amount - 100,
                'tax_amount' => 0,
                'shipping_charge' => 100,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'payment_method' => 'razorpay',
                'payment_status' => 'complete',
            ]);
        }

        foreach ($cartProducts as $product) {
            $cart = Cart::find($product);
            $productData = products::find($cart->product_id);

            $sellerOrder = OrderSellerProduct::create([
                'order_id' => $orderId,
                'seller_id' => $productData->seller_id,
                'product_id' => $productData->id,
                'variant' => $cart->variant_option_ids,
                'quantity' => $cart->quantity,
                'unit_price' => $cart->price,
                'total_amount' => $cart->quantity * $cart->price,
            ]);

            $sellerData = User::find($productData->seller_id);

            Mail::to($sellerData->email)->send(new SellerOrder(Auth::user(), $sellerData));

            $cart->delete();
        }

        $allOrderItems = OrderSellerProduct::with(['product', 'order'])->where('order_id', $orderId)->get();

        Mail::to(Auth::user()->email)->send(new UserOrder(Auth::user(), $allOrderItems, $amount));

        $payment = $api->payment->fetch($request->razorpay_payment_id);

        if ($payment->capture(['amount' => $payment['amount']])) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
