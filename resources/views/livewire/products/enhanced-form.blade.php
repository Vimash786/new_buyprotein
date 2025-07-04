<form wire:submit="save" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Seller -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seller</label>
            <select 
                wire:model="seller_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
                <option value="">Select a seller</option>
                @foreach($sellers as $seller)
                    <option value="{{ $seller->id }}">{{ $seller->company_name }}</option>
                @endforeach
            </select>
            @error('seller_id') <span class="text-red-500 text-sm">{{ $errors->first('seller_id') }}</span> @enderror
        </div>

        <!-- Product Name -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product Name</label>
            <input 
                type="text" 
                wire:model="name"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                placeholder="Enter product name"
            >
            @error('name') <span class="text-red-500 text-sm">{{ $errors->first('name') }}</span> @enderror
        </div>

        <!-- Description -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
            <textarea 
                wire:model="description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                placeholder="Enter product description"
            ></textarea>
            @error('description') <span class="text-red-500 text-sm">{{ $errors->first('description') }}</span> @enderror
        </div>

        <!-- Price -->
        @if(!$has_variants)
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Base Price (₹)</label>
                <input 
                    type="number" 
                    step="0.01"
                    wire:model="price"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0.00"
                >
                @error('price') <span class="text-red-500 text-sm">{{ $errors->first('price') }}</span> @enderror
            </div>

            <!-- User Type Pricing -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gym Owner/Trainer/Influencer Price (₹)</label>
                <input 
                    type="number" 
                    step="0.01"
                    wire:model="gym_owner_price"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0.00"
                >
                @error('gym_owner_price') <span class="text-red-500 text-sm">{{ $errors->first('gym_owner_price') }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Regular User Price (₹)</label>
                <input 
                    type="number" 
                    step="0.01"
                    wire:model="regular_user_price"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0.00"
                >
                @error('regular_user_price') <span class="text-red-500 text-sm">{{ $errors->first('regular_user_price') }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shop Owner Price (₹)</label>
                <input 
                    type="number" 
                    step="0.01"
                    wire:model="shop_owner_price"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0.00"
                >
                @error('shop_owner_price') <span class="text-red-500 text-sm">{{ $errors->first('shop_owner_price') }}</span> @enderror
            </div>
        @endif

        <!-- Stock Quantity -->
        @if(!$has_variants)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock Quantity</label>
                <input 
                    type="number" 
                    wire:model="stock_quantity"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0"
                >
                @error('stock_quantity') <span class="text-red-500 text-sm">{{ $errors->first('stock_quantity') }}</span> @enderror
            </div>
        @endif

        <!-- Weight -->
        @if(!$has_variants)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Weight</label>
                <input 
                    type="text" 
                    wire:model="weight"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="e.g., 1kg, 500g, 2.5lbs"
                >
                @error('weight') <span class="text-red-500 text-sm">{{ $errors->first('weight') }}</span> @enderror
            </div>
        @endif

        <!-- Discount Percentage -->
        @if(!$has_variants)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Discount (%)</label>
                <input 
                    type="number" 
                    step="0.01"
                    max="100"
                    wire:model="discount_percentage"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="0.00"
                >
                @error('discount_percentage') <span class="text-red-500 text-sm">{{ $errors->first('discount_percentage') }}</span> @enderror
            </div>
        @endif

        <!-- Discounted Price -->
        @if(!$has_variants)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Final Price (₹)</label>
                <input 
                    type="number" 
                    step="0.01"
                    wire:model="discounted_price"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                    placeholder="Leave empty to calculate from discount %"
                >
                @error('discounted_price') <span class="text-red-500 text-sm">{{ $errors->first('discounted_price') }}</span> @enderror
            </div>
        @endif

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
            <select 
                wire:model.live="category_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
                <option value="">Select a category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <span class="text-red-500 text-sm">{{ $errors->first('category_id') }}</span> @enderror
        </div>

        <!-- SubCategory -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subcategory</label>
            <select 
                wire:model="sub_category_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                {{ empty($availableSubCategories) ? 'disabled' : '' }}
            >
                <option value="">Select a subcategory (optional)</option>
                @if(is_array($availableSubCategories))
                    @foreach($availableSubCategories as $subCategory)
                        <option value="{{ $subCategory['id'] }}">{{ $subCategory['name'] }}</option>
                    @endforeach
                @else
                    @foreach($availableSubCategories as $subCategory)
                        <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                    @endforeach
                @endif
            </select>
            @if(empty($availableSubCategories))
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a category first to see subcategories</p>
            @endif
            @error('sub_category_id') <span class="text-red-500 text-sm">{{ $errors->first('sub_category_id') }}</span> @enderror
        </div>

        <!-- Section Category -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Section Category</label>
            <select 
                wire:model="section_category"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
                <option value="everyday_essential">Everyday Essential</option>
                <option value="popular_pick">Popular Pick</option>
                <option value="exclusive_deal">Exclusive Deal & Offers</option>
            </select>
            @error('section_category') <span class="text-red-500 text-sm">{{ $errors->first('section_category') }}</span> @enderror
        </div>

        <!-- Thumbnail Image -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thumbnail Image</label>
            <input 
                type="file" 
                wire:model="thumbnail_image"
                accept="image/*"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
            @error('thumbnail_image') <span class="text-red-500 text-sm">{{ $errors->first('thumbnail_image') }}</span> @enderror
            @if($thumbnail_image)
                <div class="mt-2">
                    <img src="{{ $thumbnail_image->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded">
                </div>
            @endif
        </div>

        <!-- Product Images -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Images (minimum 3 required)</label>
            <input 
                type="file" 
                wire:model="product_images"
                accept="image/*"
                multiple
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
            @error('product_images.*') <span class="text-red-500 text-sm">{{ $errors->first('product_images.*') }}</span> @enderror
            
            <!-- Show existing images -->
            @if(!empty($existing_images))
                <div class="mt-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Existing Images:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($existing_images as $index => $image)
                            <div class="relative">
                                <img src="{{ $image['image_url'] }}" alt="Product image" class="w-20 h-20 object-cover rounded">
                                <button 
                                    type="button"
                                    wire:click="removeImage({{ $index }})"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs"
                                >×</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Show new image previews -->
            @if($product_images)
                <div class="mt-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">New Images Preview:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product_images as $image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Has Variants Toggle -->
        <div class="md:col-span-2">
            <label class="flex items-center space-x-2">
                <input 
                    type="checkbox" 
                    wire:model.live="has_variants"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                >
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This product has variants (e.g., different weights, flavors, sizes)</span>
            </label>
        </div>

        <!-- Variants Section -->
        @if($has_variants)
            <div class="md:col-span-2 border-t pt-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Variants</h3>
                    <button 
                        type="button"
                        wire:click="addVariant"
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
                    >
                        Add Variant Type
                    </button>
                </div>

                @foreach($variants as $variantIndex => $variant)
                    <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-lg mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-900 dark:text-white">Variant Type {{ $variantIndex + 1 }}</h4>
                            @if(count($variants) > 1)
                                <button 
                                    type="button"
                                    wire:click="removeVariant({{ $variantIndex }})"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    Remove
                                </button>
                            @endif
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Variant Name</label>
                                <input 
                                    type="text" 
                                    wire:model="variants.{{ $variantIndex }}.name"
                                    wire:blur="generateVariantCombinations"
                                    placeholder="e.g., Weight, Flavor, Size"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name</label>
                                <input 
                                    type="text" 
                                    wire:model="variants.{{ $variantIndex }}.display_name"
                                    placeholder="e.g., Choose Weight, Select Flavor"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Options</label>
                                <button 
                                    type="button"
                                    wire:click="addVariantOption({{ $variantIndex }})"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs"
                                >
                                    Add Option
                                </button>
                            </div>
                            
                            @foreach($variant['options'] as $optionIndex => $option)
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <input 
                                        type="text" 
                                        wire:model="variants.{{ $variantIndex }}.options.{{ $optionIndex }}.value"
                                        wire:blur="generateVariantCombinations"
                                        placeholder="Value (e.g., 1kg, Chocolate)"
                                        class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                    >
                                    <div class="flex items-center gap-1">
                                        <input 
                                            type="text" 
                                            wire:model="variants.{{ $variantIndex }}.options.{{ $optionIndex }}.display_value"
                                            placeholder="Display (optional)"
                                            class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                        >
                                        @if(count($variant['options']) > 1)
                                            <button 
                                                type="button"
                                                wire:click="removeVariantOption({{ $variantIndex }}, {{ $optionIndex }})"
                                                class="text-red-600 hover:text-red-800 text-xs px-1"
                                            >×</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if(!empty($variant_combinations))
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-900 dark:text-white">Generated Combinations ({{ count($variant_combinations) }})</h4>
                            <button 
                                type="button"
                                wire:click="generateVariantCombinations"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm"
                            >
                                Regenerate
                            </button>
                        </div>
                        <div class="max-h-60 overflow-y-auto">
                            @foreach($variant_combinations as $combIndex => $combination)
                                <div class="bg-white dark:bg-zinc-900 p-3 rounded border mb-2">
                                    <div class="grid grid-cols-1 gap-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">
                                                @foreach($combination['options'] as $option)
                                                    {{ $option['value'] }}{{ !$loop->last ? ' × ' : '' }}
                                                @endforeach
                                            </span>
                                        </div>
                                        
                                        <!-- Base Pricing Row -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Base Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Gym Owner (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.gym_owner_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Regular User (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.regular_user_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Shop Owner (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.shop_owner_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                        </div>
                                        
                                        <!-- Discount and Stock Row -->
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Discount (%)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    max="100"
                                                    wire:model.live="variant_combinations.{{ $combIndex }}.discount_percentage"
                                                    placeholder="0"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Final Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.discounted_price"
                                                    placeholder="Auto calculated"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-gray-100 dark:bg-zinc-600 text-gray-700 dark:text-gray-300"
                                                    readonly
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Stock</label>
                                                <input 
                                                    type="number" 
                                                    wire:model="variant_combinations.{{ $combIndex }}.stock_quantity"
                                                    placeholder="0"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Status -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select 
                wire:model="status"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
            >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            @error('status') <span class="text-red-500 text-sm">{{ $errors->first('status') }}</span> @enderror
        </div>
    </div>

    <!-- Buttons -->
    <div class="flex gap-3 pt-4">
        <button 
            type="submit"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>{{ $editMode ? 'Update Product' : 'Create Product' }}</span>
            <span wire:loading>Processing...</span>
        </button>
        <button 
            type="button"
            wire:click="closeModal"
            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium"
        >
            Cancel
        </button>
    </div>
</form>
