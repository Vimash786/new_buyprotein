<!-- Product Details Modal -->
@if($showDetailsModal && $selectedProduct)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Product Details: {{ $selectedProduct->name }}
                    </h2>
                    <button wire:click="closeDetailsModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Product Images -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Images</h3>
                        <div class="mb-4">
                            @if($selectedProduct->thumbnail_image)
                                <div class="mb-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Thumbnail Image</h4>
                                    <img src="{{ asset('storage/' . $selectedProduct->thumbnail_image) }}" 
                                        alt="{{ $selectedProduct->name }}" 
                                        class="w-full h-auto max-h-48 object-contain rounded-lg border border-gray-200 dark:border-gray-700">
                                </div>
                            @endif

                            @if(count($selectedProduct->images) > 0)
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Additional Images</h4>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach($selectedProduct->images as $image)
                                        <img src="{{ asset('storage/' . $image->image_path) }}" 
                                            alt="{{ $selectedProduct->name }}" 
                                            class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Information</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Seller</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->seller->company_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                                <p class="text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $selectedProduct->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($selectedProduct->status) }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->category->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subcategory</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->subCategory->name ?? 'None' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Section</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->section_category_display }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Has Variants</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->has_variants ? 'Yes' : 'No' }}</p>
                            </div>
                            @if($selectedProduct->has_variants != 1)
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Weight</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ $selectedProduct->weight ?: 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock</p>
                                <p class="text-sm font-medium {{ $selectedProduct->stock_quantity <= 10 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $selectedProduct->stock_quantity }}
                                </p>
                            </div>
                            @endif
                        </div>
                        @if($selectedProduct->has_variants != 1)
                            <div class="mt-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">User Type Pricing</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gym Owner Price</p>
                                        <p class="text-sm text-gray-900 dark:text-white">₹{{ number_format($selectedProduct->gym_owner_price, 2) }}</p>
                                        @if($selectedProduct->gym_owner_discount > 0)
                                            <p class="text-xs text-red-600">{{ $selectedProduct->gym_owner_discount }}% off</p>
                                            <p class="text-xs text-green-600 font-medium">Final: ₹{{ number_format($selectedProduct->gym_owner_final_price, 2) }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Regular User Price</p>
                                        <p class="text-sm text-gray-900 dark:text-white">₹{{ number_format($selectedProduct->regular_user_price, 2) }}</p>
                                        @if($selectedProduct->regular_user_discount > 0)
                                            <p class="text-xs text-red-600">{{ $selectedProduct->regular_user_discount }}% off</p>
                                            <p class="text-xs text-green-600 font-medium">Final: ₹{{ number_format($selectedProduct->regular_user_final_price, 2) }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Shop Owner Price</p>
                                        <p class="text-sm text-gray-900 dark:text-white">₹{{ number_format($selectedProduct->shop_owner_price, 2) }}</p>
                                        @if($selectedProduct->shop_owner_discount > 0)
                                            <p class="text-xs text-red-600">{{ $selectedProduct->shop_owner_discount }}% off</p>
                                            <p class="text-xs text-green-600 font-medium">Final: ₹{{ number_format($selectedProduct->shop_owner_final_price, 2) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Description</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 prose dark:prose-invert max-w-none">
                        {!! $selectedProduct->description ?: 'No description available.' !!}
                    </div>
                </div>

                @if($selectedProduct->has_variants && count($selectedProduct->variants) > 0)
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Variants</h3>
                        <div class="space-y-4">
                            @foreach($selectedProduct->variants as $variant)
                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">{{ $variant->display_name }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($variant->options as $option)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $option->display_value }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6">
                        <button 
                            wire:click="viewVariantPrices({{ $selectedProduct->id }})"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition mr-2"
                        >
                            View Variant Prices
                        </button>
                        <button 
                            wire:click="viewVariantStock({{ $selectedProduct->id }})"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition"
                        >
                            View Variant Stock
                        </button>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button 
                        wire:click="edit({{ $selectedProduct->id }})"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Product
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
