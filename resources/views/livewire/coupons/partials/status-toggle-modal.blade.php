<!-- Status Toggle Confirmation Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto 
                        {{ $couponToToggle && $couponToToggle->status === 'active' 
                           ? 'bg-yellow-100 dark:bg-yellow-900/50' 
                           : 'bg-green-100 dark:bg-green-900/50' }} 
                        rounded-full mb-4">
                @if($couponToToggle && $couponToToggle->status === 'active')
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                    </svg>
                @else
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif
            </div>
            
            <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                {{ $couponToToggle && $couponToToggle->status === 'active' ? 'Deactivate' : 'Activate' }} Coupon
            </h3>
            <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                Are you sure you want to {{ $couponToToggle && $couponToToggle->status === 'active' ? 'deactivate' : 'activate' }} 
                the coupon "<strong>{{ $couponToToggle ? $couponToToggle->name : '' }}</strong>"?
                @if($couponToToggle && $couponToToggle->status === 'active')
                    This will prevent users from using this coupon until reactivated.
                @else
                    This will allow users to use this coupon if it meets other criteria.
                @endif
            </p>
            
            <div class="flex justify-end gap-3">
                <button 
                    wire:click="closeStatusToggleModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                >
                    Cancel
                </button>
                <button 
                    wire:click="toggleStatus"
                    class="px-4 py-2 text-sm font-medium text-white 
                           {{ $couponToToggle && $couponToToggle->status === 'active' 
                              ? 'bg-yellow-600 hover:bg-yellow-700' 
                              : 'bg-green-600 hover:bg-green-700' }} 
                           rounded-lg"
                >
                    {{ $couponToToggle && $couponToToggle->status === 'active' ? 'Deactivate' : 'Activate' }} Coupon
                </button>
            </div>
        </div>
    </div>
</div>
