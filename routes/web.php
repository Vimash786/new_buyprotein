<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

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
});

require __DIR__.'/auth.php';
