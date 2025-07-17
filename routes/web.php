<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\SitePageController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/dash', function () {
    return view('welcome');
})->name('welcome');

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/shop/{type?}/{id?}', [DashboardController::class, 'shop'])->name('shop');
Route::get('/product-details/{id}', [DashboardController::class, 'productDetails'])->name('product.details');
Route::get('/about-us', [SitePageController::class, 'showByType'])->defaults('type', 'about-us')->name('about.us');
Route::get('/term-condition', [SitePageController::class, 'showByType'])->defaults('type', 'terms-conditions')->name('term.condition');
Route::get('/shipping-policy', [SitePageController::class, 'showByType'])->defaults('type', 'shipping-policy')->name('shipping.policy');
Route::get('/privacy-policy', [SitePageController::class, 'showByType'])->defaults('type', 'privacy-policy')->name('privacy.policy');
Route::get('/return-policy', [SitePageController::class, 'showByType'])->defaults('type', 'return-policy')->name('return.policy');
Route::get('/contact-submit', [DashboardController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/our-blogs', [DashboardController::class, 'blogs'])->name('user.blogs');

// Generic site page route (should be after specific routes)
Route::get('/page/{slug}', [SitePageController::class, 'show'])->name('site-page.show');


// Route::get('/', function () {
//     return view('dashboard');
// })->name('home');

Volt::route('dashboard', 'admindash')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    
    // Management Pages
    Volt::route('sellers', 'sellers.manage')->name('sellers.manage');
    Volt::route('sellers/requests', 'sellers.requests')->name('sellers.requests');
    Volt::route('products', 'products.manage')->name('products.manage');
     Volt::route('products/requests', 'products.requests')->name('products.requests');
    Volt::route('orders', 'orders.manage')->name('orders.manage');
    Volt::route('users', 'users.manage')->name('users.manage');
    Volt::route('categories', 'categories.manage')->name('categories.manage');
    Volt::route('banners', 'banners.manage')->name('banners.manage');
    Volt::route('blogs', 'blogs.manage')->name('blogs.manage');
    Volt::route('site-pages', 'site-settings.pages.manage')->name('site-pages.manage');
    Volt::route('site-pages/create', 'site-settings.pages.form')->name('site-pages.create');
    Volt::route('site-pages/create/{type}', 'site-settings.pages.form')->name('site-pages.create.type');
    Volt::route('site-pages/{id}/edit', 'site-settings.pages.form')->name('site-pages.edit');
    Route::get('coupons', \App\Livewire\Coupons\ManageCoupons::class)->name('coupons.manage');
    Volt::route('payouts', 'payouts.manage')->name('payouts.sellers');
    Volt::route('transactions', 'transactions.manage')->name('transactions.manage');


    Route::get('/user-account', [DashboardController::class, 'userAccount'])->name('user.account');
    Route::get('/cart', [DashboardController::class, 'cart'])->name('user.cart');
    Route::post('/add-to-cart', [DashboardController::class, 'addToCart'])->name('cart.add');
    Route::delete('/remove-cart/{id}', [DashboardController::class, 'removeCart'])->name('cart.remove');
    Route::get('/wishlist', [DashboardController::class, 'wishList'])->name('user.wishlist');
    Route::post('/add-to-wishlist', [DashboardController::class, 'addToWishList'])->name('wishlist.add');
    Route::post('/wishlist/update-quantity', [DashboardController::class, 'updateQuantity'])->name('wishlist.updateQuantity');
    Route::delete('/wishlist/remove', [DashboardController::class, 'removeWishlist'])->name('wishlist.remove');
    Route::post('/wish-to-cart', [DashboardController::class, 'wishToCart'])->name('wishlist.to.cart');
    Route::post('/bulk-order', [DashboardController::class, 'bulkOrder'])->name('bulk.order');
    Route::post('/review-store', [DashboardController::class, 'reviewStore'])->name('review.store');

    Route::get('/checkout', [DashboardController::class, 'checkout'])->name('user.checkout');

    Route::get('razorpay', [RazorpayPaymentController::class, 'index'])->name('razorpay.index');
    Route::post('/razorpay-payment', [RazorpayPaymentController::class, 'payment'])->name('razorpay.payment');
});

require __DIR__ . '/auth.php';
