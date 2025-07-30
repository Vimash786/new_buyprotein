<!-- Coupons Table -->
<div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Discounts</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Validity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($references as $reference)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $reference->code }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reference->name }}</div>
                            @if($reference->description)
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($reference->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                   {{ $reference->type === 'percentage' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($reference->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div class="space-y-1">
                                <div class="text-green-600 dark:text-green-400">
                                    <span class="text-xs font-medium">Giver:</span>
                                    {{ $reference->type === 'percentage' ? $reference->giver_discount . '%' : '₹' . number_format($reference->giver_discount, 2) }}
                                </div>
                                <div class="text-blue-600 dark:text-blue-400">
                                    <span class="text-xs font-medium">Applyer:</span>
                                    {{ $reference->type === 'percentage' ? $reference->applyer_discount . '%' : '₹' . number_format($reference->applyer_discount, 2) }}
                                </div>
                            </div>
                            @if($reference->minimum_amount)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Min: ₹{{ number_format($reference->minimum_amount, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $reference->used_count }}{{ $reference->usage_limit ? '/' . $reference->usage_limit : '' }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $reference->assignments->count() }} assignments
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button 
                                wire:click="confirmStatusToggle({{ $reference->id }})"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                       {{ $reference->status_color === 'green' 
                                          ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/70'
                                          : ($reference->status_color === 'red' 
                                             ? 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/70'
                                             : 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/70') }}"
                            >
                                {{ $reference->human_status }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>{{ $reference->starts_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">to {{ $reference->expires_at->format('M d, Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="openReportModal({{ $reference->id }})"
                                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                    title="View Report"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="openAssignModal({{ $reference->id }})"
                                    class="text-purple-600 dark:text-purple-400 hover:text-purple-900 dark:hover:text-purple-300"
                                    title="Assign Coupon"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="edit({{ $reference->id }})"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="confirmDelete({{ $reference->id }})"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                    title="Delete"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            No references found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
        {{ $references->links() }}
    </div>
</div>
