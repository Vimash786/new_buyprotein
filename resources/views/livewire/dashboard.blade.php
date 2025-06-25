<?php

use App\Models\Sellers;
use App\Models\products;
use App\Models\orders;
use App\Models\User;
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
        ];
    }
}; ?>

<div>
     <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-2 text-sm text-gray-600">Overview of your BuyProtein platform</p>
            </div>

            <!-- Quick Actions -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('sellers.manage') }}" wire:navigate 
                           class="bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-blue-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Manage Sellers</h3>
                                <p class="text-sm text-gray-600">Add, edit, and approve sellers</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('products.manage') }}" wire:navigate 
                           class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-purple-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Manage Products</h3>
                                <p class="text-sm text-gray-600">Add, edit, and manage products</p>
                            </div>
                        </a>

                        <a href="{{ route('orders.manage') }}" wire:navigate 
                           class="bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                            <div class="bg-indigo-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Manage Orders</h3>
                                <p class="text-sm text-gray-600">View, edit, and manage orders</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Sellers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Total Sellers</h3>
                            <p class="text-3xl font-bold text-blue-600">{{ $totalSellers }}</p>
                            <p class="text-sm text-gray-600 mt-1">Registered companies</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved Sellers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Approved Sellers</h3>
                            <p class="text-3xl font-bold text-green-600">{{ $approvedSellers }}</p>
                            <p class="text-sm text-gray-600 mt-1">Active and verified</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Sellers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Pending Approval</h3>
                            <p class="text-3xl font-bold text-yellow-600">{{ $pendingSellers }}</p>
                            <p class="text-sm text-gray-600 mt-1">Awaiting review</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Total Products</h3>
                            <p class="text-3xl font-bold text-purple-600">{{ $totalProducts }}</p>
                            <p class="text-sm text-gray-600 mt-1">Available in catalog</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Total Orders</h3>
                            <p class="text-3xl font-bold text-indigo-600">{{ $totalOrders }}</p>
                            <p class="text-sm text-gray-600 mt-1">All time orders</p>
                        </div>
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Total Users</h3>
                            <p class="text-3xl font-bold text-red-600">{{ $totalUsers }}</p>
                            <p class="text-sm text-gray-600 mt-1">Registered customers</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Seller Status Breakdown -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Seller Status</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-600">Approved</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $approvedSellers }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-600">Pending</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $pendingSellers }}</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('sellers.manage') }}" wire:navigate 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View all sellers →
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Overview</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Products per Seller</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $totalSellers > 0 ? number_format($totalProducts / $totalSellers, 1) : '0' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Orders per Product</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $totalProducts > 0 ? number_format($totalOrders / $totalProducts, 1) : '0' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Approval Rate</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $totalSellers > 0 ? number_format(($approvedSellers / $totalSellers) * 100, 1) : '0' }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>            </div>
     </div>
</div>
