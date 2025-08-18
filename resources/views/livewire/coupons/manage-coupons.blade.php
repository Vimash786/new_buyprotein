<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7x2 mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Coupons Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage discount coupons and promotional codes (Super Role Access)</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total Coupons</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalCoupons }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/20 p-3 rounded-full">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Active Coupons</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $activeCoupons }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/20 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Expired Coupons</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $expiredCoupons }}</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/20 p-3 rounded-full">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Upcoming Coupons</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $upcomingCoupons }}</p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/20 p-3 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('message'))
            <div class="mb-6 bg-green-100 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-100 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Actions Bar -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Search coupons..."
                            class="w-full sm:w-64 px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-800 dark:text-white"
                            wire:model.live="search"
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <select 
                        class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-800 dark:text-white"
                        wire:model.live="statusFilter"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                    </select>

                    <!-- Type Filter -->
                    <select 
                        class="px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-zinc-800 dark:text-white"
                        wire:model.live="typeFilter"
                    >
                        <option value="">All Types</option>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button 
                        wire:click="openReportModal"
                        class="px-4 py-2 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Reports
                    </button>
                    <button 
                        wire:click="create"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Coupon
                    </button>
                </div>
            </div>
        </div>

        <!-- Coupons Table -->
        @include('livewire.coupons.partials.table')
    </div>

    <!-- Modals -->
    @if($showModal)
        @include('livewire.coupons.partials.create-edit-modal')
    @endif
    
    @if($showReportModal)
        @include('livewire.coupons.partials.report-modal')
    @endif
    
    @if($showAssignModal)
        @include('livewire.coupons.partials.assign-modal')
    @endif

    @if($showDeleteModal)
        @include('livewire.coupons.partials.delete-modal')
    @endif

    @if($showStatusToggleModal)
        @include('livewire.coupons.partials.status-toggle-modal')
    @endif
</div>
