<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/dash', function () {
    return view('welcome');
})->name('welcome');

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/shop/{type?}/{id?}', [DashboardController::class , 'shop'])->name('shop');

// Route::get('/', function () {
//     return view('dashboard');
// })->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    
    // Management Pages
    Volt::route('sellers', 'sellers.manage')->name('sellers.manage');
    Volt::route('products', 'products.manage')->name('products.manage');
    Volt::route('orders', 'orders.manage')->name('orders.manage');
    Volt::route('users', 'users.manage')->name('users.manage');
    Volt::route('categories', 'categories.manage')->name('categories.manage');
    Volt::route('banners', 'banners.manage')->name('banners.manage');
    Route::get('coupons', \App\Livewire\Coupons\ManageCoupons::class)->name('coupons.manage');
});

require __DIR__.'/auth.php';
