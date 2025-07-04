<?php

use App\Models\products;
use App\Models\Sellers;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Models\ProductVariantCombination;
use App\Models\ProductImage;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $statusFilter = '';
    public $categoryFilter = '';
    public $subCategoryFilter = '';
    public $showModal = false;
    public $editMode = false;
    public $productId = null;
    
    // Form fields
    public $seller_id = '';
    public $name = '';
    public $description = '';
    public $price = '';
    public $stock_quantity = '';
    public $category_id = '';
    public $sub_category_id = '';
    public $status = 'active';
    public $section_category = 'everyday_essential';
    public $discount_percentage = 0;
    public $discounted_price = '';
    public $has_variants = false;
    
    // Image upload properties
    public $thumbnail_image;
    public $product_images = [];
    public $existing_images = [];
    public $images_to_delete = [];
    
    // Variant properties
    public $variants = [];
    public $variant_combinations = [];
    public $show_variant_modal = false;
    public $generating_combinations = false;
    
    // For dynamic subcategory loading
    public $availableSubCategories = [];

    protected $rules = [
        'seller_id' => 'required|exists:sellers,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'stock_quantity' => 'required|integer|min:0',
        'category_id' => 'required|exists:categories,id',
        'sub_category_id' => 'nullable|exists:sub_categories,id',
        'status' => 'required|in:active,inactive',
        'section_category' => 'required|in:everyday_essential,popular_pick,exclusive_deal',
        'discount_percentage' => 'nullable|numeric|min:0|max:100',
        'discounted_price' => 'nullable|numeric|min:0',
        'has_variants' => 'boolean',
        'thumbnail_image' => 'nullable|image|max:2048',
        'product_images.*' => 'nullable|image|max:2048',
    ];

    public function with()
    {
        $query = products::with(['seller', 'category', 'subCategory', 'variants', 'images']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('seller', function($seller) {
                      $seller->where('company_name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('category', function($category) {
                      $category->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('subCategory', function($subCategory) {
                      $subCategory->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->subCategoryFilter) {
            $query->where('sub_category_id', $this->subCategoryFilter);
        }

        $totalProducts = products::count();
        $activeProducts = products::where('status', 'active')->count();
        $inactiveProducts = products::where('status', 'inactive')->count();
        $lowStockProducts = products::where('stock_quantity', '<=', 10)->count();
        $variantProducts = products::where('has_variants', true)->count();

        return [
            'products' => $query->latest()->paginate(10),
            'sellers' => Sellers::where('status', 'approved')->get(),
            'categories' => Category::active()->ordered()->get(),
            'subCategories' => SubCategory::active()->ordered()->get(),
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'inactiveProducts' => $inactiveProducts,
            'lowStockProducts' => $lowStockProducts,
            'variantProducts' => $variantProducts,
        ];
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->editMode = false;
        $this->resetForm();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->productId = null;
        $this->seller_id = '';
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->stock_quantity = '';
        $this->category_id = '';
        $this->sub_category_id = '';
        $this->status = 'active';
        $this->section_category = 'everyday_essential';
        $this->discount_percentage = 0;
        $this->discounted_price = '';
        $this->has_variants = false;
        $this->thumbnail_image = null;
        $this->product_images = [];
        $this->existing_images = [];
        $this->images_to_delete = [];
        $this->variants = [];
        $this->variant_combinations = [];
        $this->availableSubCategories = [];
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        // Calculate discounted price if discount percentage is provided
        if ($this->discount_percentage > 0) {
            $this->discounted_price = $this->price * (1 - ($this->discount_percentage / 100));
        }

        $data = [
            'seller_id' => $this->seller_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id ?: null,
            'status' => $this->status,
            'section_category' => $this->section_category,
            'discount_percentage' => $this->discount_percentage ?: 0,
            'discounted_price' => $this->discounted_price ?: null,
            'has_variants' => $this->has_variants,
        ];

        // Handle thumbnail image upload
        if ($this->thumbnail_image) {
            $data['thumbnail_image'] = $this->thumbnail_image->store('products/thumbnails', 'public');
        }

        if ($this->editMode) {
            $product = products::findOrFail($this->productId);
            $product->update($data);
            
            // Handle image deletions
            if (!empty($this->images_to_delete)) {
                ProductImage::whereIn('id', $this->images_to_delete)->delete();
            }
        } else {
            $product = products::create($data);
        }

        // Handle additional product images
        if (!empty($this->product_images)) {
            foreach ($this->product_images as $index => $image) {
                if ($image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $image->store('products/images', 'public'),
                        'sort_order' => $index + 1,
                        'is_primary' => false
                    ]);
                }
            }
        }

        // Handle variants if enabled
        if ($this->has_variants && !empty($this->variants)) {
            $this->saveVariants($product);
        } else {
            // Remove all variants if variants are disabled
            $product->variants()->delete();
            $product->variantCombinations()->delete();
        }

        $message = $this->editMode ? 'Product updated successfully!' : 'Product created successfully!';
        session()->flash('message', $message);
        $this->closeModal();
    }

    private function saveVariants($product)
    {
        // Delete existing variants and combinations
        $product->variants()->delete();
        $product->variantCombinations()->delete();

        foreach ($this->variants as $variantIndex => $variantData) {
            if (empty(trim($variantData['name']))) continue;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => $variantData['name'],
                'display_name' => $variantData['display_name'] ?: $variantData['name'],
                'sort_order' => $variantIndex,
                'is_required' => $variantData['is_required'] ?? true,
            ]);

            foreach ($variantData['options'] as $optionIndex => $optionData) {
                if (empty(trim($optionData['value']))) continue;

                ProductVariantOption::create([
                    'product_variant_id' => $variant->id,
                    'value' => $optionData['value'],
                    'display_value' => $optionData['display_value'] ?: $optionData['value'],
                    'price_adjustment' => $optionData['price_adjustment'] ?: 0,
                    'sort_order' => $optionIndex,
                    'is_active' => true,
                ]);
            }
        }

        // Save variant combinations
        foreach ($this->variant_combinations as $combination) {
            if (empty($combination['options'])) continue;

            $optionIds = array_column($combination['options'], 'id');
            // For new options, we need to get the IDs from the database
            $actualOptionIds = [];
            
            foreach ($combination['options'] as $option) {
                $variantOption = ProductVariantOption::where('value', $option['value'])
                    ->whereHas('variant', function($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })
                    ->first();
                
                if ($variantOption) {
                    $actualOptionIds[] = $variantOption->id;
                }
            }

            if (!empty($actualOptionIds)) {
                ProductVariantCombination::create([
                    'product_id' => $product->id,
                    'variant_options' => $actualOptionIds,
                    'price' => $combination['price'] ?: $product->price,
                    'stock_quantity' => $combination['stock_quantity'] ?: 0,
                    'is_active' => $combination['is_active'] ?? true,
                ]);
            }
        }
    }

    public function edit($id)
    {
        $product = products::with(['variants.options', 'variantCombinations', 'images'])->findOrFail($id);
        
        $this->productId = $product->id;
        $this->seller_id = $product->seller_id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->stock_quantity = $product->stock_quantity;
        $this->category_id = $product->category_id;
        $this->sub_category_id = $product->sub_category_id;
        $this->status = $product->status;
        $this->section_category = $product->section_category;
        $this->discount_percentage = $product->discount_percentage;
        $this->discounted_price = $product->discounted_price;
        $this->has_variants = $product->has_variants;
        
        // Load existing images
        $this->existing_images = $product->images->map(function($image) {
            return [
                'id' => $image->id,
                'image_path' => $image->image_path,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'image_url' => asset('storage/' . $image->image_path)
            ];
        })->toArray();
        
        // Load variants and options
        $this->variants = $product->variants->map(function($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->name,
                'display_name' => $variant->display_name,
                'is_required' => $variant->is_required,
                'options' => $variant->options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'value' => $option->value,
                        'display_value' => $option->display_value,
                        'price_adjustment' => $option->price_adjustment,
                    ];
                })->toArray()
            ];
        })->toArray();

        // Load variant combinations
        $this->variant_combinations = $product->variantCombinations->map(function($combination) {
            $options = ProductVariantOption::whereIn('id', $combination->variant_options)->get();
            return [
                'id' => $combination->id,
                'options' => $options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'value' => $option->value,
                        'display_value' => $option->display_value,
                        'price_adjustment' => $option->price_adjustment,
                    ];
                })->toArray(),
                'price' => $combination->price,
                'stock_quantity' => $combination->stock_quantity,
                'is_active' => $combination->is_active,
            ];
        })->toArray();
        
        // Load subcategories for the selected category
        if ($this->category_id) {
            $this->availableSubCategories = SubCategory::where('category_id', $this->category_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->availableSubCategories = [];
        }
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($id)
    {
        products::findOrFail($id)->delete();
        session()->flash('message', 'Product deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $product = products::findOrFail($id);
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active'
        ]);
        
        session()->flash('message', 'Product status updated successfully!');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingSubCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedCategoryId()
    {
        // Reset subcategory when category changes
        $this->sub_category_id = '';
        
        // Load subcategories for the selected category
        if ($this->category_id) {
            try {
                $this->availableSubCategories = SubCategory::where('category_id', $this->category_id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->toArray(); // Convert to array to ensure it's properly serialized
            } catch (\Exception $e) {
                // Fallback: get all subcategories for the category without scopes
                $this->availableSubCategories = SubCategory::where('category_id', $this->category_id)
                    ->get()
                    ->toArray();
            }
        } else {
            $this->availableSubCategories = [];
        }
        
        // Force a re-render of the component
        $this->dispatch('subcategories-updated');
    }

    public function addVariant()
    {
        $this->variants[] = [
            'id' => null,
            'name' => '',
            'display_name' => '',
            'is_required' => true,
            'options' => [
                ['id' => null, 'value' => '', 'display_value' => '', 'price_adjustment' => 0]
            ]
        ];
    }

    public function removeVariant($index)
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
        $this->generateVariantCombinations();
    }

    public function addVariantOption($variantIndex)
    {
        $this->variants[$variantIndex]['options'][] = [
            'id' => null,
            'value' => '',
            'display_value' => '',
            'price_adjustment' => 0
        ];
        $this->generateVariantCombinations();
    }

    public function removeVariantOption($variantIndex, $optionIndex)
    {
        unset($this->variants[$variantIndex]['options'][$optionIndex]);
        $this->variants[$variantIndex]['options'] = array_values($this->variants[$variantIndex]['options']);
        $this->generateVariantCombinations();
    }

    public function generateVariantCombinations()
    {
        if (!$this->has_variants || empty($this->variants)) {
            $this->variant_combinations = [];
            return;
        }

        // Filter variants that have at least one option with value
        $validVariants = array_filter($this->variants, function($variant) {
            return !empty(array_filter($variant['options'], function($option) {
                return !empty(trim($option['value']));
            }));
        });

        if (empty($validVariants)) {
            $this->variant_combinations = [];
            return;
        }

        // Generate all combinations
        $combinations = $this->getCombinations($validVariants);
        
        $this->variant_combinations = array_map(function($combination, $index) {
            return [
                'id' => null,
                'options' => $combination,
                'sku' => '',
                'price' => $this->price,
                'stock_quantity' => 0,
                'is_active' => true
            ];
        }, $combinations, array_keys($combinations));
    }

    private function getCombinations($variants)
    {
        $result = [[]];
        
        foreach ($variants as $variant) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($variant['options'] as $option) {
                    if (!empty(trim($option['value']))) {
                        $temp[] = array_merge($combination, [$option]);
                    }
                }
            }
            $result = $temp;
        }
        
        return $result;
    }

    public function updatedHasVariants()
    {
        if ($this->has_variants) {
            if (empty($this->variants)) {
                $this->addVariant();
            }
        } else {
            $this->variants = [];
            $this->variant_combinations = [];
        }
    }

    public function removeImage($imageIndex)
    {
        if (isset($this->existing_images[$imageIndex])) {
            $this->images_to_delete[] = $this->existing_images[$imageIndex]['id'];
            unset($this->existing_images[$imageIndex]);
            $this->existing_images = array_values($this->existing_images);
        }
    }

    public function toggleVariantModal()
    {
        $this->show_variant_modal = !$this->show_variant_modal;
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage product inventory, pricing, and availability</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Products</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $activeProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Inactive</h3>
                        <p class="text-3xl font-bold text-red-600">{{ $inactiveProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Low Stock</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $lowStockProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">With Variants</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $variantProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Add Button -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="search"
                                placeholder="Search products..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <!-- Category Filter -->
                        <select wire:model.live="categoryFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>

                        <!-- SubCategory Filter -->
                        <select wire:model.live="subCategoryFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Subcategories</option>
                            @foreach($subCategories as $subCategory)
                                <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Add Button -->
                    <button 
                        wire:click="openModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Products Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variants</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($products as $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                        @if($product->thumbnail_image)
                                            <img src="{{ asset('storage/' . $product->thumbnail_image) }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded mt-1">
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $product->seller->company_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        @if($product->has_discount)
                                            <div class="line-through text-gray-500">${{ number_format($product->price, 2) }}</div>
                                            <div class="font-medium text-green-600">${{ number_format($product->final_price, 2) }}</div>
                                            @if($product->discount_percentage > 0)
                                                <div class="text-xs text-red-600">{{ $product->discount_percentage }}% off</div>
                                            @endif
                                        @else
                                            <div class="font-medium">${{ number_format($product->price, 2) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium 
                                        {{ $product->stock_quantity <= 10 ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                    @if($product->stock_quantity <= 10)
                                        <span class="ml-1 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                                            Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>{{ $product->category->name ?? 'N/A' }}</div>
                                    @if($product->subCategory)
                                        <div class="text-xs text-gray-500">{{ $product->subCategory->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $product->section_category === 'popular_pick' ? 'bg-blue-100 text-blue-800' : 
                                           ($product->section_category === 'exclusive_deal' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $product->section_category_display }}
                                    </span>
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
                                    <button 
                                        wire:click="toggleStatus({{ $product->id }})"
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="edit({{ $product->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="delete({{ $product->id }})"
                                            wire:confirm="Are you sure you want to delete this product?"
                                            class="text-red-600 hover:text-red-900"
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
                                    No products found.
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
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Product' : 'Add New Product' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @include('livewire.products.enhanced-form')
                </div>
            </div>
        </div>
    @endif
</div>
