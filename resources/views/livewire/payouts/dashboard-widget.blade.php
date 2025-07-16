<?php

use App\Models\Payout;
use App\Services\PayoutService;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component
{
    public function with()
    {
        $payoutService = new PayoutService();
        $stats = $payoutService->getPayoutStats();
        
        $recentPayouts = Payout::with('seller')
            ->latest()
            ->limit(5)
            ->get();
            
        $overduePayouts = Payout::overdue()
            ->with('seller')
            ->limit(5)
            ->get();
            
        $dueSoonPayouts = Payout::dueSoon()
            ->with('seller')
            ->limit(5)
            ->get();
        
        return [
            'stats' => $stats,
            'recentPayouts' => $recentPayouts,
            'overduePayouts' => $overduePayouts,
            'dueSoonPayouts' => $dueSoonPayouts,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Payout Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Payouts</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_payouts']) }}</p>
                </div>
                <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['unpaid_payouts']) }}</p>
                </div>
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/50 rounded-full">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['overdue_payouts']) }}</p>
                </div>
                <div class="p-2 bg-red-100 dark:bg-red-900/50 rounded-full">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Amount Pending</p>
                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400">₹{{ number_format($stats['total_amount_pending'], 0) }}</p>
                </div>
                <div class="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-full">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if($overduePayouts->count() > 0)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <h3 class="text-lg font-medium text-red-800 dark:text-red-300">{{ $overduePayouts->count() }} Overdue Payouts</h3>
            </div>
            <p class="text-red-700 dark:text-red-400 mt-1">Some payouts are overdue and need immediate attention.</p>
            <a href="{{ route('payouts.sellers') }}?dateFilter=overdue" class="text-red-600 dark:text-red-400 underline hover:text-red-800 dark:hover:text-red-200">
                View overdue payouts →
            </a>
        </div>
    @endif

    @if($dueSoonPayouts->count() > 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-300">{{ $dueSoonPayouts->count() }} Payouts Due Soon</h3>
            </div>
            <p class="text-yellow-700 dark:text-yellow-400 mt-1">These payouts are due within the next 5 days.</p>
            <a href="{{ route('payouts.sellers') }}?dateFilter=due_soon" class="text-yellow-600 dark:text-yellow-400 underline hover:text-yellow-800 dark:hover:text-yellow-200">
                View due soon payouts →
            </a>
        </div>
    @endif

    <!-- Recent Payouts -->
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Payouts</h3>
                <a href="{{ route('payouts.sellers') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 text-sm">
                    View all →
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Payout ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Seller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Due Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentPayouts as $payout)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $payout->payout_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $payout->seller_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                ₹{{ number_format($payout->payout_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       bg-{{ $payout->status_color }}-100 dark:bg-{{ $payout->status_color }}-900/50 
                                       text-{{ $payout->status_color }}-800 dark:text-{{ $payout->status_color }}-300">
                                    {{ $payout->status_text }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $payout->due_date->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No recent payouts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
