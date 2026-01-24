<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RazorpayPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/dash', function () {
    return view('welcome');
})->name('welcome');

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/shop/{type?}/{id?}', [DashboardController::class, 'shop'])->name('shop');
Route::get('/product-details/{id}', [DashboardController::class, 'productDetails'])->name('product.details');
Route::get('/about-us', [DashboardController::class, 'aboutUs'])->name('about.us');
Route::get('/term-condition', [DashboardController::class, 'termCondition'])->name('term.condition');
Route::get('/shipping-policy', [DashboardController::class, 'shippingPolicy'])->name('shipping.policy');
Route::get('/privacy-policy', [DashboardController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/return-policy', [DashboardController::class, 'returnPolicy'])->name('return.policy');
Route::get('/contact', [DashboardController::class, 'contact'])->name('contact');
Route::get('/contact-submit', [DashboardController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/our-blogs', [DashboardController::class, 'blogs'])->name('user.blogs');
Route::get('/our-blogs/{id}', [DashboardController::class, 'blogDetails'])->name('blog.details');
Route::post('/submit-commet/{id}', [DashboardController::class, 'blogComment'])->name('blog.comment');

// Add to cart
Route::post('/add-to-cart', [DashboardController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [DashboardController::class, 'cart'])->name('user.cart');
Route::get('/checkout', [DashboardController::class, 'checkout'])->name('user.checkout');
Route::get('/thank-you', [DashboardController::class, 'thankYou'])->name('thank.you');
Route::get('/thank-you-test', function() {
    session()->flash('order_details', [
        'order_number' => 'ORD-20250813-BG1234',
        'payment_method' => 'online',
        'total_amount' => 1299.99,
        'email' => 'test@example.com',
        'payment_id' => 'pay_test123'
    ]);
    return redirect()->route('thank.you');
})->name('thank.you.test');

// Payment routes (accessible to both guest and authenticated users)
Route::get('razorpay', [RazorpayPaymentController::class, 'index'])->name('razorpay.index');
Route::post('/razorpay-payment', [RazorpayPaymentController::class, 'payment'])->name('razorpay.payment');
Route::post('/cod-payment', [RazorpayPaymentController::class, 'codPayment'])->name('cod.payment');
Route::post('/test-payment', function(Request $request) {
    Log::info('Test payment route hit', ['method' => $request->method(), 'data' => $request->all()]);
    return response()->json(['success' => true, 'message' => 'Test route working']);
})->name('test.payment');
    Route::post('/apply-coupon', [DashboardController::class, 'applyCoupon'])->name('apply.coupon');
    Route::post('/apply-reference', [DashboardController::class, 'applyReference'])->name('apply.reference');
    Route::post('/get-shareable-reference', [DashboardController::class, 'getShareableReferenceLink'])->name('get.shareable.reference');// Test route for debugging coupon validation
// Route::get('/test-coupon', [\App\Http\Controllers\CouponTestController::class, 'testGuestCoupon'])->name('test.coupon');

// Wishlist routes (accessible to both guest and authenticated users)
Route::get('/wishlist', [DashboardController::class, 'wishList'])->name('user.wishlist');
Route::post('/add-to-wishlist', [DashboardController::class, 'addToWishList'])->name('wishlist.add');
Route::post('/wishlist/update-quantity', [DashboardController::class, 'updateQuantity'])->name('wishlist.updateQuantity');
Route::delete('/wishlist/remove', [DashboardController::class, 'removeWishlist'])->name('wishlist.remove');
Route::post('/wish-to-cart', [DashboardController::class, 'wishToCart'])->name('wishlist.to.cart');

// Review routes (accessible to both guest and authenticated users)
Route::post('/review-store', [DashboardController::class, 'reviewStore'])->name('review.store');


// Route::get('/', function () {
//     return view('dashboard');
// })->name('home');

Volt::route('dashboard', 'admindash')
    ->middleware(['auth', 'verified', 'super.seller.only'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    
    // Management Pages
    Volt::route('sellers', 'sellers.manage')->name('sellers.manage');
    Volt::route('sellers/requests', 'sellers.requests')->name('sellers.requests');
    Volt::route('products', 'products.manage')->middleware('seller.approved')->name('products.manage');
	Volt::route('products/import', 'products.import')->middleware('seller.approved')->name('products.import');
    Volt::route('products/requests', 'products.requests')->name('products.requests');
    Volt::route('orders', 'orders.manage')->middleware('seller.approved')->name('orders.manage');
    Volt::route('bulk-orders-seller', 'bulk-orders.manage')->middleware('seller.approved')->name('bulk-orders.seller');
    Volt::route('users', 'users.manage')->name('users.manage');
    Volt::route('categories', 'categories.manage')->name('categories.manage');
    Volt::route('banners', 'banners.manage')->name('banners.manage');
    Volt::route('blogs', 'blogs.manage')->name('blogs.manage');
    Route::get('coupons', \App\Livewire\Coupons\ManageCoupons::class)->name('coupons.manage');
    Route::get('commission', \App\Livewire\Settings\GlobalCommissionSettings::class)->name('settings.commission');
    Volt::route('payouts', 'payouts.manage')->name('payouts.sellers');
    Volt::route('transactions', 'transactions.manage')->name('transactions.manage');
    Route::get('reference', \App\Livewire\Reference\ManageReference::class)->name('reference.manage');

    // Policy Management Routes
    Volt::route('policies', 'policies.manage')->name('policies.manage');

    Route::get('/user-account', [DashboardController::class, 'userAccount'])->name('user.account');
    Route::get('/my-orders', [DashboardController::class, 'userOrders'])->name('user.orders');
    Route::post('/update-user-details', [DashboardController::class, 'updateUserDetails'])->name('update.user.details');
    
    
    Route::delete('/remove-cart/{id}', [DashboardController::class, 'removeCart'])->name('cart.remove');
    Route::post('/bulk-order', [DashboardController::class, 'bulkOrder'])->name('bulk.order');

    // Invoice routes
    Route::get('/invoice/seller/{orderItemId}', [App\Http\Controllers\InvoiceController::class, 'downloadSellerInvoice'])->name('invoice.seller.download');
    Route::get('/invoice/order/{orderId}', [App\Http\Controllers\InvoiceController::class, 'downloadOrderInvoice'])->name('invoice.order.download');
    Route::get('/invoice/standard/{orderId}', [App\Http\Controllers\InvoiceController::class, 'downloadStandardInvoice'])->name('invoice.standard.download');
});

require __DIR__ . '/auth.php';