<?php

use App\Models\Sellers;
use App\Models\products;
use App\Models\orders;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Banner;
use App\Models\Blog;
use Livewire\Volt\Component;

new class extends Component
{
    public function with()
    {
        return [
            'totalSellers' => Sellers::count(),
            'approvedSellers' => Sellers::where('status', 'approved')->count(),
            'pendingSellers' => Sellers::where('status', 'not_approved')->count(),
            'totalProducts' => products::count(),
            'totalOrders' => orders::count(),
            'totalUsers' => User::count(),
            'totalCategories' => Category::count(),
            'totalSubCategories' => SubCategory::count(),
            'totalBanners' => Banner::count(),
            'activeBanners' => Banner::where('status', 'active')->count(),
            'totalBlogs' => Blog::count(),
            'publishedBlogs' => Blog::where('status', 'published')->count(),
        ];
    }
}; ?>

<div>
     <div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Overview of your BuyProtein platform</p>
            </div>

            <!-- Quick Actions -->
            <div class="mb-8">
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('sellers.manage') }}" wire:navigate 
                           class="bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-blue-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Sellers</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Add, edit, and approve sellers</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('products.manage') }}" wire:navigate 
                           class="bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 border border-purple-200 dark:border-purple-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-purple-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Products</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Add, edit, and manage products</p>
                            </div>
                        </a>

                        <a href="{{ route('categories.manage') }}" wire:navigate 
                           class="bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-green-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Categories</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Add, edit categories & sub-categories</p>
                            </div>
                        </a>

                        <a href="{{ route('banners.manage') }}" wire:navigate 
                           class="bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/30 border border-cyan-200 dark:border-cyan-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-cyan-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Banners</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Add, edit, and manage banners</p>
                            </div>
                        </a>

                        @if(auth()->user()->role === 'Super')
                        <a href="{{ route('coupons.manage') }}" wire:navigate 
                           class="bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-amber-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Coupons</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Create, assign, and manage coupons</p>
                            </div>
                        </a>

                        <a href="{{ route('blogs.manage') }}" wire:navigate 
                           class="bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-indigo-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Manage Blogs</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Create and manage blog posts</p>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <!-- Total Sellers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Sellers</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ $totalSellers }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Registered companies</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Products</h3>
                            <p class="text-3xl font-bold text-purple-600">{{ $totalProducts }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Available in catalog</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Categories -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Categories</h3>
                            <p class="text-3xl font-bold text-green-600">{{ $totalCategories }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Product categories</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Sub Categories -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sub-Categories</h3>
                            <p class="text-3xl font-bold text-orange-600">{{ $totalSubCategories }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Product sub-categories</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Banners -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Banners</h3>
                            <p class="text-3xl font-bold text-cyan-600">{{ $totalBanners }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $activeBanners }} active banners</p>
                        </div>
                        <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Blogs -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Blogs</h3>
                            <p class="text-3xl font-bold text-indigo-600">{{ $totalBlogs }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $publishedBlogs }} published blogs</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Orders</h3>
                            <p class="text-3xl font-bold text-indigo-600">{{ $totalOrders }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">All time orders</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Users</h3>
                            <p class="text-3xl font-bold text-red-600">{{ $totalUsers }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Registered customers</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved Sellers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Approved Sellers</h3>
                            <p class="text-3xl font-bold text-teal-600">{{ $approvedSellers }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Active and verified</p>
                        </div>
                        <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Sellers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending Approval</h3>
                            <p class="text-3xl font-bold text-yellow-600">{{ $pendingSellers }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Awaiting review</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Seller Status Breakdown -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Seller Status</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">Approved</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $approvedSellers }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">Pending</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $pendingSellers }}</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('sellers.manage') }}" wire:navigate 
                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            View all sellers →
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Platform Overview</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Products per Seller</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $totalSellers > 0 ? number_format($totalProducts / $totalSellers, 1) : '0' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Orders per Product</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $totalProducts > 0 ? number_format($totalOrders / $totalProducts, 1) : '0' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Approval Rate</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $totalSellers > 0 ? number_format(($approvedSellers / $totalSellers) * 100, 1) : '0' }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>            </div>
     </div>
</div>
