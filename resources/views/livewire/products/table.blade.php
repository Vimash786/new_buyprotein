<!-- Products Table -->
<div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ProductId</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                    @if(!$isSeller)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller & Brand Name</th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variants</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="viewDetails({{ $product->id }})"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    title="View Details"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="edit({{ $product->id }})"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button 
                                    wire:click="confirmDelete({{ $product->id }})"
                                    class="text-red-600 hover:text-red-900"
                                    title="Delete"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <button 
                                    wire:click="viewDetails({{ $product->id }})"
                                    class=" text-gray-900 dark:text-white"
                                    title="View Details"
                                >
                                    #P{{ $product->id}}
                                </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                @if($product->thumbnail_image)
                                    <img src="{{ asset('storage/' . $product->thumbnail_image) }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded mt-1">
                                @endif
                            </div>
                        </td>
                        @if(!$isSeller)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $product->seller->company_name ?? 'N/A' }}
                            @if($product->seller->brand)
                                <div class="text-xs text-gray-500">{{ $product->seller->brand }}</div>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                @if($product->has_variants)
                                    <button 
                                        wire:click="viewVariantPrices({{ $product->id }})"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200"
                                    >
                                        View Variant Prices
                                    </button>
                                @else
                                    <div class="space-y-1">
                                        <div class="text-xs text-gray-500">Gym Owner: ₹{{ number_format($product->gym_owner_price, 2) }}</div>
                                        <div class="text-xs text-gray-500">Regular User: ₹{{ number_format($product->regular_user_price, 2) }}</div>
                                        <div class="text-xs text-gray-500">Shop Owner: ₹{{ number_format($product->shop_owner_price, 2) }}</div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($product->has_variants)
                                <button 
                                    wire:click="viewVariantStock({{ $product->id }})"
                                    class="text-xs px-2 py-1 bg-green-100 text-green-600 rounded hover:bg-green-200"
                                >
                                    View Variant Stock
                                </button>
                            @else
                                <span class="text-sm font-medium 
                                    {{ $product->stock_quantity <= 10 ? 'text-red-600' : 'dark:text-white' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                                @if($product->stock_quantity <= 10)
                                    <span class="ml-1 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                                        Low Stock
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>{{ $product->category->name ?? 'N/A' }}</div>
                            @if($product->subCategory)
                                <div class="text-xs text-gray-500">{{ $product->subCategory->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $categories = is_array($product->section_category) ? $product->section_category : [$product->section_category];
                            @endphp
                            <div class="flex flex-wrap gap-1">
                                @foreach($categories as $category)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $category === 'popular_pick' ? 'bg-blue-100 text-blue-800' : 
                                           ($category === 'exclusive_deal' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ match($category) {
                                            'everyday_essential' => 'Everyday Essential',
                                            'popular_pick' => 'Popular Pick',
                                            'exclusive_deal' => 'Exclusive Deal & Offers',
                                            default => 'Everyday Essential'
                                        } }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($product->has_variants)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $product->variants->count() ?? 0 }} types
                                </span>
                            @else
                                <span class="text-gray-400 text-sm">No variants</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($isSeller)
                                <!-- Sellers can toggle their product status (active/inactive) -->
                                <button 
                                    wire:click="confirmStatusToggle({{ $product->id }})"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                           {{ $product->status === 'active' 
                                              ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                              : 'bg-red-100 text-red-800 hover:bg-red-200' }}"
                                >
                                    @if($product->status === 'active')
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Active
                                    @else
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Inactive
                                    @endif
                                </button>
                                
                                <!-- Show super_status as read-only for sellers -->
                                <div class="mt-1">
                                    <span class="text-xs px-2 py-1 rounded-full
                                           {{ $product->super_status === 'approved' 
                                              ? 'bg-blue-100 text-blue-800' 
                                              : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $product->super_status === 'approved' ? 'Admin Approved' : 'Pending Admin Approval' }}
                                    </span>
                                </div>
                            @else
                                <!-- Admins can toggle super_status (approved/not_approved) -->
                                <button 
                                    wire:click="confirmStatusToggle({{ $product->id }})"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                           {{ $product->super_status === 'approved' 
                                              ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                              : 'bg-red-100 text-red-800 hover:bg-red-200' }}"
                                >
                                    @if($product->super_status === 'approved')
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Approved
                                    @else
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Pending
                                    @endif
                                </button>
                                
                                <!-- Show seller status as read-only for admins -->
                                <div class="mt-1">
                                    <span class="text-xs px-2 py-1 rounded-full
                                           {{ $product->status === 'active' 
                                              ? 'bg-blue-100 text-blue-800' 
                                              : 'bg-gray-100 text-gray-800' }}">
                                        Seller: {{ ucfirst($product->status) }}
                                    </span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSeller ? '8' : '9' }}" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            @if($isSeller)
                                No products found. Start by adding your first product!
                            @else
                                No products found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
        {{ $products->links() }}
    </div>
</div>
