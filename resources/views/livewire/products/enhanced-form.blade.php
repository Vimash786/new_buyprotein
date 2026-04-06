<form wire:submit="save" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Seller -->
        <div class="md:col-span-2">
            @if(!$isSeller)
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
            @else
            <input 
                type="hidden" 
                wire:model="seller_id"
                value="{{ $seller->id }}"
            >
            @endif
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
            
            <div class="mb-4">
                <div id="product-description-editor" class="bg-white dark:bg-zinc-800 rounded-lg shadow border border-gray-300 dark:border-gray-600" style="min-height: 150px;">
                    
                </div>
            </div>
            
            <!-- Hidden input for Livewire -->
            <input type="hidden" wire:model="description" id="description-input" value="{{ $description }}">
            
            @error('description') <span class="text-red-500 text-sm">{{ $errors->first('description') }}</span> @enderror
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use the toolbar above to format your product description</p>
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

        <!-- User Type Pricing -->
        @if(!$has_variants)
            <div class="md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">User Type Pricing</h3>
            </div>

            <!-- Base Prices Row -->
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gym Owner Price (₹)</label>
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
                </div>
            </div>

            <!-- Discount Percentages Row -->
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gym Owner Discount (%)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            max="100"
                            wire:model.live="gym_owner_discount"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0"
                        >
                        @error('gym_owner_discount') <span class="text-red-500 text-sm">{{ $errors->first('gym_owner_discount') }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Regular User Discount (%)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            max="100"
                            wire:model.live="regular_user_discount"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0"
                        >
                        @error('regular_user_discount') <span class="text-red-500 text-sm">{{ $errors->first('regular_user_discount') }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shop Owner Discount (%)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            max="100"
                            wire:model.live="shop_owner_discount"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0"
                        >
                        @error('shop_owner_discount') <span class="text-red-500 text-sm">{{ $errors->first('shop_owner_discount') }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Final Prices Row (Auto-calculated) -->
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gym Owner Final Price (₹)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            wire:model="gym_owner_final_price"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0.00"
                        >
                        @error('gym_owner_final_price') <span class="text-red-500 text-sm">{{ $errors->first('gym_owner_final_price') }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Regular User Final Price (₹)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            wire:model="regular_user_final_price"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0.00"
                        >
                        @error('regular_user_final_price') <span class="text-red-500 text-sm">{{ $errors->first('regular_user_final_price') }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shop Owner Final Price (₹)</label>
                        <input 
                            type="number" 
                            step="0.01"
                            wire:model="shop_owner_final_price"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            placeholder="0.00"
                        >
                        @error('shop_owner_final_price') <span class="text-red-500 text-sm">{{ $errors->first('shop_owner_final_price') }}</span> @enderror
                    </div>
                </div>
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
        @if(!$isSeller)
        <div class="md:col-span-2">
            <x-multiselect
                label="Section Category"
                wire-model="section_category"
                :options="[
                    ['value' => 'everyday_essential', 'label' => 'Everyday Essential', 'description' => 'Products for daily nutrition needs'],
                    ['value' => 'popular_pick', 'label' => 'Popular Pick', 'description' => 'Customer favorite and trending products'],
                    ['value' => 'exclusive_deal', 'label' => 'Exclusive Deal & Offers', 'description' => 'Special offers and limited-time deals']
                ]"
                :selected="is_array($section_category) ? $section_category : [$section_category]"
                placeholder="Choose section categories..."
                description="Select one or more categories to feature your product in specific sections of the store."
                remove-method="removeSectionCategory"
                option-value="value"
                option-label="label"
                option-description="description"
                required
                :show-description="true"
            />
        </div>
        @endif

        

        <!-- Product Images -->
        @if(!$has_variants)
            <!-- Thumbnail Image -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Thumbnail Image
                    <span class="text-xs text-gray-500">(Maximum Size Allow - 1MB each)</span>
                </label>
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
                        <p class="text-xs text-gray-500 mt-1">Size: {{ $this->formatFileSize($thumbnail_image->getSize()) }}</p>
                    </div>
                @endif
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Additional Images (maximum 3 images)
                    <span class="text-xs text-gray-500">(Maximum Size Allow - 1MB each)</span>
                </label>
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
                                    @if($image['formatted_size'])
                                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                            {{ $image['formatted_size'] }}
                                        </div>
                                    @endif
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
                                <div class="relative">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                        {{ $this->formatFileSize($image->getSize()) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

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
                @error('variants') <span class="text-red-500 text-sm mb-4 block">{{ $errors->first('variants') }}</span> @enderror

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
                                @error('variants.' . $variantIndex . '.name') <span class="text-red-500 text-xs">{{ $errors->first('variants.' . $variantIndex . '.name') }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name</label>
                                <input 
                                    type="text" 
                                    wire:model="variants.{{ $variantIndex }}.display_name"
                                    placeholder="e.g., Choose Weight, Select Flavor"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                >
                                @error('variants.' . $variantIndex . '.display_name') <span class="text-red-500 text-xs">{{ $errors->first('variants.' . $variantIndex . '.display_name') }}</span> @enderror
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
                            @error('variants.' . $variantIndex . '.options') <span class="text-red-500 text-xs mb-2 block">{{ $errors->first('variants.' . $variantIndex . '.options') }}</span> @enderror
                            
                            @foreach($variant['options'] as $optionIndex => $option)
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <div>
                                        <input 
                                            type="text" 
                                            wire:model="variants.{{ $variantIndex }}.options.{{ $optionIndex }}.value"
                                            wire:blur="generateVariantCombinations"
                                            placeholder="Value (e.g., 1kg, Chocolate)"
                                            class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                        >
                                        @error('variants.' . $variantIndex . '.options.' . $optionIndex . '.value') <span class="text-red-500 text-xs">{{ $errors->first('variants.' . $variantIndex . '.options.' . $optionIndex . '.value') }}</span> @enderror
                                    </div>
                                    <div class="flex items-center gap-1">
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
                        @error('variant_combinations') <span class="text-red-500 text-sm mb-2 block">{{ $errors->first('variant_combinations') }}</span> @enderror
                        <div class="max-h-100 overflow-y-auto">
                            @foreach($variant_combinations as $combIndex => $combination)
                                <div class="bg-white dark:bg-zinc-900 p-3 rounded border mb-2">
                                    @error('variant_combinations.' . $combIndex) <span class="text-red-500 text-xs mb-2 block">{{ $errors->first('variant_combinations.' . $combIndex) }}</span> @enderror
                                    <div class="grid grid-cols-1 gap-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">
                                                @foreach($combination['options'] as $option)
                                                    {{ $option['value'] }}{{ !$loop->last ? ' × ' : '' }}
                                                @endforeach
                                            </span>
                                        </div>
                                        
                                        <!-- User Type Pricing Row -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Gym Owner Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.gym_owner_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.gym_owner_price') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.gym_owner_price') }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Regular User Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.regular_user_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.regular_user_price') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.regular_user_price') }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Shop Owner Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.shop_owner_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.shop_owner_price') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.shop_owner_price') }}</span> @enderror
                                            </div>
                                        </div>
                                        
                                        <!-- Discount Percentage Row -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Gym Owner Discount (%)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    max="100"
                                                    wire:model.live="variant_combinations.{{ $combIndex }}.gym_owner_discount"
                                                    placeholder="0"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.gym_owner_discount') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.gym_owner_discount') }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Regular User Discount (%)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    max="100"
                                                    wire:model.live="variant_combinations.{{ $combIndex }}.regular_user_discount"
                                                    placeholder="0"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.regular_user_discount') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.regular_user_discount') }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Shop Owner Discount (%)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    max="100"
                                                    wire:model.live="variant_combinations.{{ $combIndex }}.shop_owner_discount"
                                                    placeholder="0"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                                @error('variant_combinations.' . $combIndex . '.shop_owner_discount') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.shop_owner_discount') }}</span> @enderror
                                            </div>
                                        </div>
                                        
                                        <!-- Final Price Row -->
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Gym Owner Final Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.gym_owner_final_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Regular User Final Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.regular_user_final_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                                >
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 dark:text-gray-400">Shop Owner Final Price (₹)</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01"
                                                    wire:model="variant_combinations.{{ $combIndex }}.shop_owner_final_price"
                                                    placeholder="0.00"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
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
                                                @error('variant_combinations.' . $combIndex . '.stock_quantity') <span class="text-red-500 text-xs">{{ $errors->first('variant_combinations.' . $combIndex . '.stock_quantity') }}</span> @enderror
                                            </div>
                                        </div>
                                        
                                        <!-- Variant Thumbnail Image -->
                                        <div class="mt-3 border-t pt-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    Variant Thumbnail Image
                                                    <span class="text-xs text-gray-500">(Maximum Size Allow - 1MB each)</span>
                                                </label>
                                            </div>
                                            <input 
                                                type="file" 
                                                wire:model="variant_thumbnails.{{ $combIndex }}"
                                                accept="image/*"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                            >
                                            @error('variant_thumbnails.' . $combIndex) <span class="text-red-500 text-xs">{{ $errors->first('variant_thumbnails.' . $combIndex) }}</span> @enderror
                                            
                                            <!-- Show existing variant thumbnail -->
                                            @if(isset($existing_variant_thumbnails[$combIndex]) && !empty($existing_variant_thumbnails[$combIndex]))
                                                <div class="mt-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Current Thumbnail:</p>
                                                    <div class="relative inline-block">
                                                        <img src="{{ $existing_variant_thumbnails[$combIndex]['image_url'] }}" alt="Variant thumbnail" class="w-20 h-20 object-cover rounded">
                                                        @if($existing_variant_thumbnails[$combIndex]['formatted_size'])
                                                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                                                {{ $existing_variant_thumbnails[$combIndex]['formatted_size'] }}
                                                            </div>
                                                        @endif
                                                        <button 
                                                            type="button"
                                                            wire:click="removeVariantThumbnail({{ $combIndex }})"
                                                            class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"
                                                        >×</button>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <!-- Show new variant thumbnail preview -->
                                            @if(isset($variant_thumbnails[$combIndex]) && $variant_thumbnails[$combIndex])
                                                <div class="mt-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">New Thumbnail Preview:</p>
                                                    <div class="relative inline-block">
                                                        <img src="{{ $variant_thumbnails[$combIndex]->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded">
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                                            {{ $this->formatFileSize($variant_thumbnails[$combIndex]->getSize()) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Variant Images -->
                                        <div class="mt-3 border-t pt-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    Variant Images (maximum 3 images)
                                                    <span class="text-xs text-gray-500">(Maximum Size Allow - 1MB each)</span>
                                                </label>
                                            </div>
                                            <input 
                                                type="file" 
                                                wire:model="variant_images.{{ $combIndex }}"
                                                accept="image/*"
                                                multiple
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                            >
                                            @error('variant_images.' . $combIndex . '.*') <span class="text-red-500 text-xs">{{ $errors->first('variant_images.' . $combIndex . '.*') }}</span> @enderror
                                            
                                            <!-- Show existing variant images -->
                                            @if(isset($existing_variant_images[$combIndex]) && !empty($existing_variant_images[$combIndex]))
                                                <div class="mt-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Existing Images:</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($existing_variant_images[$combIndex] as $imageIndex => $image)
                                                            <div class="relative">
                                                                <img src="{{ $image['image_url'] }}" alt="Variant image" class="w-16 h-16 object-cover rounded">
                                                                @if($image['formatted_size'])
                                                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                                                        {{ $image['formatted_size'] }}
                                                                    </div>
                                                                @endif
                                                                <button 
                                                                    type="button"
                                                                    wire:click="removeVariantImage({{ $combIndex }}, {{ $imageIndex }})"
                                                                    class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs"
                                                                >×</button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <!-- Show new variant image previews -->
                                            @if(isset($variant_images[$combIndex]) && $variant_images[$combIndex])
                                                <div class="mt-2">
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">New Images Preview:</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($variant_images[$combIndex] as $image)
                                                            <div class="relative">
                                                                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-16 h-16 object-cover rounded">
                                                                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 rounded-b">
                                                                    {{ $this->formatFileSize($image->getSize()) }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
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
            type="button"
            onclick="handleFormSubmission()"
            id="submit-button"
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
