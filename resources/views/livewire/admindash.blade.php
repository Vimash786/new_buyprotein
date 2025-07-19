<?php

use App\Models\Sellers;
use App\Models\products;
use App\Models\OrderSellerProduct;
use App\Models\orders;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Payout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component
{
    public function with()
    {
        $user = auth()->user();
        $isSeller = $user && $user->role === 'Seller';
        $seller = Sellers::where('user_id', $user->id)->first();
        
        if ($isSeller) {
            // Seller-specific data
            $sellerOrderItems = OrderSellerProduct::where('seller_id', $seller->id ?? 0)
                ->where('status', 'delivered')
                ->get();
            
            $totalSales = $sellerOrderItems->sum('total_amount');
            $commissionRate = $seller ? ($seller->commission ?? 0) : 0;
            $commissionAmount = ($totalSales * $commissionRate) / 100;
            $sellerPayout = $totalSales - $commissionAmount;
            
            // Get actual payout data from Payout model
            $paidPayouts = Payout::where('seller_id', $seller->id ?? 0)
                ->where('payment_status', 'paid')
                ->sum('payout_amount');
            
            $pendingPayouts = Payout::where('seller_id', $seller->id ?? 0)
                ->where('payment_status', 'unpaid')
                ->sum('payout_amount');
            
            return [
                'totalSellers' => 1, // Just the current seller
                'approvedSellers' => $seller && $seller->status === 'approved' ? 1 : 0,
                'pendingSellers' => $seller && $seller->status === 'not_approved' ? 1 : 0,
                'totalProducts' => products::where('seller_id', $seller->id ?? 0)->count(),
                'totalOrders' => OrderSellerProduct::where('seller_id', $seller->id ?? 0)->count(),
                'totalSales' => $totalSales,
                'commissionAmount' => $commissionAmount,
                'sellerPayout' => $sellerPayout,
                'paidPayouts' => $paidPayouts,
                'pendingPayouts' => $pendingPayouts,

                'totalUsers' => User::count(), // Keep global count for reference
                'totalCategories' => Category::count(),
                'totalSubCategories' => SubCategory::count(),
                'totalBanners' => Banner::count(),
                'activeBanners' => Banner::where('status', 'active')->count(),
                'totalBlogs' => Blog::count(),
                'publishedBlogs' => Blog::where('status', 'published')->count(),
                'isSeller' => true, 
                'sellerName' => $seller->company_name ?? 'N/A',
                'sellerStatus' => $seller->status ?? 'not_approved',
                'isApprovedSeller' => $seller && $seller->status === 'approved',
                'orderCount' => OrderSellerProduct::where('seller_id', $seller->id ?? 0)
                    ->with(['product', 'order.user'])->latest()->take(10)->get(),
            ];
        } else {
            // Admin/Super Admin data (global)
            // Calculate commission earned and payment due
            $deliveredOrderItems = OrderSellerProduct::whereIn('status', ['delivered'])
                ->with('seller')
                ->get();
            
            $totalSalesAmount = $deliveredOrderItems->sum('total_amount');
            $totalCommissionEarned = 0;
            $totalPaymentDue = 0;
            
            // Calculate commission for each order item based on seller's commission rate
            foreach ($deliveredOrderItems as $orderItem) {
                $seller = $orderItem->seller;
                $commissionRate = $seller ? ($seller->commission ?? 0) : 0;
                $itemCommission = ($orderItem->total_amount * $commissionRate) / 100;
                $totalCommissionEarned += $itemCommission;
                $totalPaymentDue += ($orderItem->total_amount - $itemCommission);
            }
            
            return [
                'totalSellers' => Sellers::count(),
                'approvedSellers' => Sellers::where('status', 'approved')->count(),
                'pendingSellers' => Sellers::where('status', 'not_approved')->count(),
                'totalProducts' => products::count(),
                'totalOrders' => orders::count(),
                'totalUsers' => User::count(),
                'totalSales' => $totalSalesAmount,
                'totalCommissionEarned' => $totalCommissionEarned,
                'totalPaymentDue' => $totalPaymentDue,
                'totalCategories' => Category::count(),
                'totalSubCategories' => SubCategory::count(),
                'totalBanners' => Banner::count(),
                'activeBanners' => Banner::where('status', 'active')->count(),
                'totalBlogs' => Blog::count(),
                'activeCoupons' => Coupon::where('status', 'active')->count(),
                'inactiveProducts'=> products::where('status', 'inactive')->count(),
                'publishedBlogs' => Blog::where('status', 'published')->count(),
                'isSeller' => false,
                'sellerName' => null,
                'sellerStatus' => null,
            ];
        }
    }
}; ?>

<div>
     <div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session()->has('error'))
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if (session()->has('message'))
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        {{ session('message') }}
                    </div>
                </div>
            @endif

            <!-- Header -->
            <div class="mb-8">
                @if($isSeller)
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Seller Dashboard</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Welcome back, {{ $sellerName }}! 
                        @if($sellerStatus === 'approved')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Approved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Pending Approval
                            </span>
                        @endif
                    </p>
                @else
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Overview of your BuyProtein platform</p>
                @endif
            </div>            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <!-- Total Sellers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                @if($isSeller)
                                    Total Sales
                                @else
                                    Total Sellers
                                @endif
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">{{ $isSeller ? '₹'.$totalSales : $totalSellers }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                @if($isSeller)
                                    My total sales
                                @else
                                    Registered companies
                                @endif
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                            @if($isSeller)
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                            @else       
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Total Products -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Total Products
                            </h3>
                            <p class="text-3xl font-bold text-purple-600">{{ $totalProducts }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                @if($isSeller)
                                    Products in my catalog
                                @else
                                    Available in catalog
                                @endif
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                </div>

               
                
                @if($isSeller)
                <!-- Total Payout Received -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Payout Received</h3>
                            <p class="text-3xl font-bold text-green-600">₹{{ number_format($paidPayouts, 2) }}</p>   
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Already paid by platform</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Payout Pending -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Payout Pending</h3>
                            <p class="text-3xl font-bold text-orange-600">₹{{ number_format($pendingPayouts, 2) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Awaiting payment from platform</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                @endif

            

                @if(!$isSeller)
                <!-- Total Orders-->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Total Orders
                            </h3>
                            <p class="text-3xl font-bold text-indigo-600">{{ $totalOrders }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                              
                                All time orders
                            </p>
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
                            <p class="text-3xl font-bold text-red-600">{{ User::where('role', 'User')->count() }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Regular customers</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Gym Owners/Trainers/Influencers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gym/Trainers/Influencers</h3>
                            <p class="text-3xl font-bold text-green-600">{{ User::where('role', 'Gym Owner/Trainer/Influencer')->count() }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Fitness professionals</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Shop Owners -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Shop Owners</h3>
                            <p class="text-3xl font-bold text-purple-600">{{ User::where('role', 'Shop Owner')->count() }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Physical store owners</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Total Sales -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Sales</h3>
                            <p class="text-3xl font-bold text-emerald-600">₹{{ number_format($totalSales, 2) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Delivered orders revenue</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Total Commission Earned -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Commission Earned</h3>
                            <p class="text-3xl font-bold text-orange-600">₹{{ number_format($totalCommissionEarned, 2) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Platform commission earned</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Seller Commission -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Payment Due</h3>
                            <p class="text-3xl font-bold text-pink-600">₹{{ number_format($totalPaymentDue, 2) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Total due to sellers</p>
                        </div>
                        <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <!-- Approved Sellers -->
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Coupons</h3>
                            <p class="text-3xl font-bold text-teal-600">{{ $activeCoupons }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Active Coupons</p>
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
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending Sellers Approval</h3>
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
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending Products Approval</h3>
                            <p class="text-3xl font-bold text-red-600">{{ $inactiveProducts }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                @endif
            </div>
             <!-- Quick Actions -->
            <div class="mb-8">
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if($isSeller)
                            @if($isApprovedSeller)
                                <!-- Approved Seller Actions -->
                                <a href="{{ route('products.manage') }}" wire:navigate 
                                   class="bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 border border-purple-200 dark:border-purple-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                                    <div class="bg-purple-500 p-2 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">My Products</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">Manage your products</p>
                                    </div>
                                </a>

                                <a href="{{ route('orders.manage') }}" wire:navigate 
                                   class="bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                                    <div class="bg-green-500 p-2 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">My Orders</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">View product orders</p>
                                    </div>
                                </a>
                            @else
                                <!-- Pending Approval Message -->
                                <div class="col-span-full bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                                    <div class="flex items-center justify-center mb-4">
                                        <div class="bg-yellow-500 p-3 rounded-full">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">Account Pending Approval</h3>
                                    <p class="text-yellow-700 dark:text-yellow-400 mb-4">
                                        Your seller account is currently under review. Once approved by our admin team, you will be able to access product and order management features.
                                    </p>
                                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                        Status: Pending Approval
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Profile Link - Available for all sellers -->
                            <a href="{{ route('settings.profile') }}" wire:navigate 
                               class="bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 flex items-center space-x-3 transition-colors">
                                <div class="bg-blue-500 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Profile</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Update seller profile</p>
                                </div>
                            </a>
                        @else
                            <!-- Admin Actions -->
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
                        @endif
                    </div>
                </div>
            </div>
            @if($isSeller)
            <!-- Latest Orders Section -->
            <div class="mb-8">
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                        <a href="{{ route('orders.manage') }}" wire:navigate 
                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            View All Orders →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        @if($orderCount->count() > 0)
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead class="bg-gray-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($orderCount as $order)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                #{{ $order->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $order->product->name ?? 'Product not found' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $order->order->user->name ?? 'User not found' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                ₹{{ number_format($order->total_amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($order->status === 'delivered')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                        Delivered
                                                    </span>
                                                @elseif($order->status === 'pending')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                                        Pending
                                                    </span>
                                                @elseif($order->status === 'cancelled')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                                        Cancelled
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                        {{ ucfirst($order->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $order->created_at->format('M d, Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No orders yet</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start adding products to receive orders.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
     </div>
</div>
