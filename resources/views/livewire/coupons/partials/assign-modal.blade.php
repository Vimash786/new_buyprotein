<!-- Assign Modal -->
@if($showAssignModal)
<div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50" wire:click="closeAssignModal"></div>
        
        <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg dark:bg-gray-800">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Assign Coupon: {{ $selectedCoupon ? $selectedCoupon->name : '' }}
                </h3>
                <button wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Assignment Form -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Assign Coupon To
                        </label>
                        <select wire:model.live="assignmentType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select Assignment Type</option>
                            <option value="all_products">All Products</option>
                            <option value="users">Specific Users</option>
                            <option value="products">Specific Products</option>
                        </select>
                    </div>

                    @if($assignmentType === 'all_products')
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Assign to All Products</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">This coupon will be available for all products in the system.</p>
                                </div>
                            </div>
                        </div>
                    @elseif($assignmentType && $assignmentType !== 'all_products')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search {{ ucfirst($assignmentType) }}
                            </label>
                            <input 
                                wire:model.live="searchItems" 
                                type="text" 
                                placeholder="Search {{ $assignmentType }}..." 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select {{ ucfirst($assignmentType) }}
                            </label>
                            <div class="border border-gray-300 dark:border-gray-600 rounded-md max-h-48 overflow-y-auto">
                                @foreach($this->getAssignableItems() as $item)
                                    <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedItems" 
                                            value="{{ $item->id }}" 
                                            class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                @if($assignmentType === 'users')
                                                    {{ $item->name }}
                                                @elseif($assignmentType === 'products')
                                                    {{ $item->name }}
                                                @elseif($assignmentType === 'sellers')
                                                    {{ $item->company_name ?? $item->name }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                @if($assignmentType === 'users')
                                                    {{ $item->email }}
                                                @elseif($assignmentType === 'products')
                                                    ID: {{ $item->id }}
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3 pt-4">
                        <button wire:click="closeAssignModal" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        @if($assignmentType === 'all_products' || (!empty($selectedItems) && $assignmentType && $assignmentType !== 'all_products'))
                            <button wire:click="assignCoupon" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                @if($assignmentType === 'all_products')
                                    Assign to All Products
                                @else
                                    Assign Coupon
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Current Assignments -->
                <div class="border-l border-gray-200 dark:border-gray-600 pl-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Current Assignments</h4>
                    @if($selectedCoupon && $selectedCoupon->assignments && $selectedCoupon->assignments->count() > 0)
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach($selectedCoupon->assignments as $assignment)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ ucfirst($assignment->assignable_type) }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($assignment->assignable_type === 'user_type')
                                                User Type: {{ ucfirst($assignment->user_type) }}
                                            @elseif($assignment->assignable_type === 'all_products')
                                                All Products
                                            @elseif($assignment->assignable)
                                                @if($assignment->assignable_type === 'user')
                                                    {{ $assignment->assignable->name ?? 'N/A' }} ({{ $assignment->assignable->email ?? 'N/A' }})
                                                @elseif($assignment->assignable_type === 'product')
                                                    {{ $assignment->assignable->name ?? 'N/A' }}
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="removeAssignment({{ $assignment->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p>No assignments yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
