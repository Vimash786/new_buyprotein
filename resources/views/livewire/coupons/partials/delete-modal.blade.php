<!-- Delete Confirmation Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            
            <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">Delete Coupon</h3>
            <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                Are you sure you want to delete the coupon "<strong>{{ $couponToDelete ? $couponToDelete->name : '' }}</strong>"? 
                This action cannot be undone and will remove all associated assignments and usage history.
            </p>
            
            <div class="flex justify-end gap-3">
                <button 
                    wire:click="closeDeleteModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                >
                    Cancel
                </button>
                <button 
                    wire:click="delete"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                >
                    Delete Coupon
                </button>
            </div>
        </div>
    </div>
</div>
