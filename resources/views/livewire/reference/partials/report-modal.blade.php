<!-- Report Modal -->
<div x-data="{ 
        init() {
            // Ensure modal state is properly synced
            this.$watch('$wire.showReportModal', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            });
        }
     }"
     x-show="$wire.showReportModal" 
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     x-cloak
     style="display: none;"
     @keydown.escape="$wire.closeReportModal()">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="$wire.showReportModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50" 
             @click="$wire.closeReportModal()"></div>
        
        <div x-show="$wire.showReportModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full max-w-6xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg dark:bg-gray-800">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Coupon Reports
                </h3>
                <button @click="$wire.closeReportModal()" 
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <!-- Report Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        From Date
                    </label>
                    <input wire:model="reportDateFrom" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        To Date
                    </label>
                    <input wire:model="reportDateTo" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Coupon
                    </label>
                    <select wire:model="reportCouponId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Coupons</option>
                        @foreach($coupons as $coupon)
                            <option value="{{ $coupon->id }}">{{ $coupon->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="generateReport" 
                            class="w-full px-4 py-2 text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Generate Report
                    </button>
                </div>
            </div>

            <!-- Report Stats -->
            @if($reportData)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $reportData['total_coupons'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Coupons</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $reportData['total_usage'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Usage</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            ${{ number_format($reportData['total_discount'] ?? 0, 2) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Discount</div>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $reportData['total_assignments'] ?? 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Assignments</div>
                    </div>
                </div>
            @endif

            <!-- Report Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Coupon</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Value</th>
                            <th class="px-6 py-3">Usage</th>
                            <th class="px-6 py-3">Assignments</th>
                            <th class="px-6 py-3">Total Discount</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($reportData && isset($reportData['coupons']) && is_array($reportData['coupons']) && count($reportData['coupons']) > 0)
                            @foreach($reportData['coupons'] as $coupon)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $coupon->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $coupon->code }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $coupon->type === 'percentage' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                                            {{ ucfirst($coupon->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $coupon->type === 'percentage' ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $coupon->used_count ?? 0 }} / {{ $coupon->usage_limit ?? '∞' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 rounded-full">
                                            {{ $coupon->assignments_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        ${{ number_format($coupon->total_discount_amount ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $coupon->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                            {{ ucfirst($coupon->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    @if($reportData === null)
                                        Click "Generate Report" to view coupon data
                                    @else
                                        No coupons found for the selected criteria
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Export Options -->
            <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    @if($reportData && isset($reportData['coupons']) && is_array($reportData['coupons']))
                        Showing {{ count($reportData['coupons']) }} coupon(s)
                    @endif
                </div>
                <div class="flex space-x-2">
                    <button wire:click="exportReport('csv')" 
                            class="px-4 py-2 text-sm text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Export CSV
                    </button>
                    <button wire:click="exportReport('excel')" 
                            class="px-4 py-2 text-sm text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
