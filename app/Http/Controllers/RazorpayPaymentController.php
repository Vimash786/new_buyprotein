<?php

namespace App\Http\Controllers;

use App\Mail\SellerOrder;
use App\Mail\UserOrder;
use App\Mail\WelcomeMail;
use App\Models\BillingDetail;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
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
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayPaymentController extends Controller
{
    public function payment(Request $request)
    {
        try {
            Log::info('Payment request received', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'data' => $request->all()
            ]);
            
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
        $billing = $request->input('billing');
        $shipping = $request->input('shipping');
        $cartProducts = $request->input('products');
        $amount = $request->input('amount');
        $discount = $request->input('discount');
        $coupon = $request->input('coupon');
        $isGuest = $request->input('is_guest', false);

        $randomPart = 'BG' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $orderNumber = 'ORD-' . Carbon::now()->format('Ymd') . '-' . $randomPart;

        // Handle both authenticated and guest users
        $userId = null;
        $guestEmail = null;
        $userEmail = null; // Email to use for sending confirmation
        
        if (Auth::check()) {
            $userId = Auth::user()->id;
            // Use email from billing data or fall back to user's stored email
            $userEmail = $billing['email'] ?? Auth::user()->email;
        } else {
            // For guest users, we'll store the email from billing data
            $guestEmail = $billing['email'] ?? null;
            $userEmail = $guestEmail;
        }

        $order = orders::create([
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'guest_email' => $guestEmail,
            'total_order_amount' => $amount,
        ]);

        $orderId = $order->id;
        $shippingAddressid = null;

        if ($coupon) {
            $couponData = Coupon::where('code', $coupon)->first();
            if ($couponData) {
                $couponData->increment('used_count');
                $couponData->save();
            }

            $couponUsage = CouponUsage::create([
                'coupon_id' => $couponData->id,
                'user_id' => $userId,
                'order_id' => $orderId,
                'discount_amount' => $discount,
            ]);
        }

        if (isset($billing['existing_billing_id'])) {
            $existing = BillingDetail::find($billing['existing_billing_id']);

            $shippingAddress = ShippingAddress::create([
                'user_id' => $userId,
                'order_id' => $orderId,
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
                'subtotal' => $amount,
                'tax_amount' => 0,
                'shipping_charge' => 0,
                'discount_amount' => $discount,
                'total_amount' => $amount - $discount,
                'payment_method' => 'razorpay',
                'payment_status' => 'complete',
            ]);
        } else {

            $shippingAddress = ShippingAddress::create([
                'user_id' => $userId,
                'order_id' => $orderId,
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
                'subtotal' => $amount,
                'tax_amount' => 0,
                'shipping_charge' => 0,
                'discount_amount' => $discount,
                'total_amount' => $amount - $discount,
                'payment_method' => 'razorpay',
                'payment_status' => 'complete',
            ]);
        }

        // Process cart items - handle both authenticated users and guests
        if (!$isGuest && Auth::check()) {
            // For authenticated users, use database cart
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

                $sellerData = Sellers::find($productData->seller_id);
                $seller = User::where('id', $sellerData->user_id)->first();

                if ($seller && !empty($seller->email)) {
                    Mail::to($seller->email)->send(new SellerOrder(Auth::user(), $sellerData));
                } else {
                    Log::error('Seller email is empty or seller not found', [
                        'seller_id' => $productData->seller_id,
                        'seller_user_id' => $sellerData->user_id ?? 'N/A',
                        'seller_email' => $seller->email ?? 'N/A'
                    ]);
                }

                $cart->delete();
            }
        } else {
            // For guest users, use session cart
            $sessionCart = session('cart', []);
            foreach ($cartProducts as $productKey) {
                if (isset($sessionCart[$productKey])) {
                    $item = $sessionCart[$productKey];
                    $productData = products::find($item['product_id']);

                    $sellerOrder = OrderSellerProduct::create([
                        'order_id' => $orderId,
                        'seller_id' => $productData->seller_id,
                        'product_id' => $productData->id,
                        'variant' => $item['variant_option_ids'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'total_amount' => $item['quantity'] * $item['price'],
                    ]);

                    $sellerData = Sellers::find($productData->seller_id);
                    $seller = User::where('id', $sellerData->user_id)->first();

                    // Create a guest user object for email
                    $guestUser = (object) [
                        'name' => 'Guest Customer',
                        'email' => $userEmail
                    ];

                    if ($seller && !empty($seller->email)) {
                        Mail::to($seller->email)->send(new SellerOrder($guestUser, $sellerData));
                    } else {
                        Log::error('Seller email is empty or seller not found for guest order', [
                            'seller_id' => $productData->seller_id,
                            'seller_user_id' => $sellerData->user_id ?? 'N/A',
                            'seller_email' => $seller->email ?? 'N/A'
                        ]);
                    }

                    // Remove item from session cart
                    unset($sessionCart[$productKey]);
                }
            }
            // Update session cart
            session(['cart' => $sessionCart]);
        }

        $allOrderItems = OrderSellerProduct::with(['product', 'order'])->where('order_id', $orderId)->get();

        // Send confirmation email to customer
        if (!$isGuest && Auth::check()) {
            $user = Auth::user();
            
            Log::info('Attempting to send user order email', [
                'user_id' => $user->id,
                'user_email' => $userEmail,
                'email_length' => strlen($userEmail ?? ''),
                'is_empty' => empty($userEmail)
            ]);
            
            if (!empty($userEmail)) {
                Mail::to($userEmail)->send(new UserOrder($user, $allOrderItems, $amount));
            } else {
                Log::error('User email is empty for authenticated user', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);
                // Continue without sending email rather than failing the whole transaction
                Log::warning('Skipping user order email due to missing email address');
            }
        } else {
            // For guest users
            if (empty($userEmail)) {
                Log::error('Guest email is empty');
                throw new \Exception('Guest email is required but not provided');
            }
            
            $guestUser = (object) [
                'name' => 'Guest Customer',
                'email' => $userEmail
            ];
            Mail::to($userEmail)->send(new UserOrder($guestUser, $allOrderItems, $amount));
        }

        $payment = $api->payment->fetch($request->razorpay_payment_id);

        if ($payment->capture(['amount' => $payment['amount']])) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    } catch (\Exception $e) {
        Log::error('Payment processing error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Payment processing failed: ' . $e->getMessage()
        ], 500);
    }
}
}
