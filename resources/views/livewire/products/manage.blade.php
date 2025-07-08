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
    public $gym_owner_price = '';
    public $regular_user_price = '';
    public $shop_owner_price = '';
    public $gym_owner_discount = 0;
    public $regular_user_discount = 0;
    public $shop_owner_discount = 0;
    public $gym_owner_final_price = '';
    public $regular_user_final_price = '';
    public $shop_owner_final_price = '';
    public $stock_quantity = '';
    public $weight = '';
    public $category_id = '';
    public $sub_category_id = '';
    public $status = 'active';
    public $section_category = 'everyday_essential';
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

    // For variant details and product details views
    public $showVariantPricesModal = false;
    public $showVariantStockModal = false;
    public $showDetailsModal = false;
    public $selectedProduct = null;
    public $variantPrices = [];
    public $variantStock = [];
    public $productDetails = [];

    protected $rules = [
        'seller_id' => 'required|exists:sellers,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'gym_owner_price' => 'required_if:has_variants,false|nullable|numeric|min:0',
        'regular_user_price' => 'required_if:has_variants,false|nullable|numeric|min:0',
        'shop_owner_price' => 'required_if:has_variants,false|nullable|numeric|min:0',
        'gym_owner_discount' => 'nullable|numeric|min:0|max:100',
        'regular_user_discount' => 'nullable|numeric|min:0|max:100',
        'shop_owner_discount' => 'nullable|numeric|min:0|max:100',
        'gym_owner_final_price' => 'nullable|numeric|min:0',
        'regular_user_final_price' => 'nullable|numeric|min:0',
        'shop_owner_final_price' => 'nullable|numeric|min:0',
        'stock_quantity' => 'required_if:has_variants,false|nullable|integer|min:0',
        'weight' => 'nullable|string|max:50',
        'category_id' => 'required|exists:categories,id',
        'sub_category_id' => 'nullable|exists:sub_categories,id',
        'status' => 'required|in:active,inactive',
        'section_category' => 'required|in:everyday_essential,popular_pick,exclusive_deal',
        'has_variants' => 'boolean',
        'thumbnail_image' => 'nullable|image|max:2048',
        'product_images.*' => 'nullable|image|max:2048',
    ];

    public function with()
    {
        $user = auth()->user();
        $seller = Sellers::where('user_id', $user->id)->first();
        $isSeller = $seller !== null;

        $query = products::with(['seller', 'category', 'subCategory', 'variants', 'images']);

        // If user is a seller, only show their products
        if ($isSeller) {
            $query->where('seller_id', $seller->id);
        }

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

        // Calculate statistics based on user role
        if ($isSeller) {
            $totalProducts = products::where('seller_id', $seller->id)->count();
            $activeProducts = products::where('seller_id', $seller->id)->where('status', 'active')->count();
            $inactiveProducts = products::where('seller_id', $seller->id)->where('status', 'inactive')->count();
            $lowStockProducts = products::where('seller_id', $seller->id)->where('stock_quantity', '<=', 10)->count();
            $variantProducts = products::where('seller_id', $seller->id)->where('has_variants', true)->count();
        } else {
            $totalProducts = products::count();
            $activeProducts = products::where('status', 'active')->count();
            $inactiveProducts = products::where('status', 'inactive')->count();
            $lowStockProducts = products::where('stock_quantity', '<=', 10)->count();
            $variantProducts = products::where('has_variants', true)->count();
        }

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
            'isSeller' => $isSeller,
            'currentSeller' => $seller,
        ];
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->editMode = false;
        $this->resetForm();
        
        // Auto-select seller if user is a seller
        $user = auth()->user();
        $seller = Sellers::where('user_id', $user->id)->first();
        if ($seller) {
            $this->seller_id = $seller->id;
        }
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
        $this->gym_owner_price = '';
        $this->regular_user_price = '';
        $this->shop_owner_price = '';
        $this->gym_owner_discount = 0;
        $this->regular_user_discount = 0;
        $this->shop_owner_discount = 0;
        $this->gym_owner_final_price = '';
        $this->regular_user_final_price = '';
        $this->shop_owner_final_price = '';
        $this->stock_quantity = '';
        $this->weight = '';
        $this->category_id = '';
        $this->sub_category_id = '';
        $this->status = 'active';
        $this->section_category = 'everyday_essential';
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

    public function calculateFinalPrices()
    {
        // Calculate gym owner final price
        if ($this->gym_owner_price && $this->gym_owner_discount > 0) {
            $this->gym_owner_final_price = $this->gym_owner_price * (1 - ($this->gym_owner_discount / 100));
        } else {
            $this->gym_owner_final_price = $this->gym_owner_price;
        }

        // Calculate regular user final price
        if ($this->regular_user_price && $this->regular_user_discount > 0) {
            $this->regular_user_final_price = $this->regular_user_price * (1 - ($this->regular_user_discount / 100));
        } else {
            $this->regular_user_final_price = $this->regular_user_price;
        }

        // Calculate shop owner final price
        if ($this->shop_owner_price && $this->shop_owner_discount > 0) {
            $this->shop_owner_final_price = $this->shop_owner_price * (1 - ($this->shop_owner_discount / 100));
        } else {
            $this->shop_owner_final_price = $this->shop_owner_price;
        }
    }

    public function updatedGymOwnerDiscount()
    {
        $this->calculateFinalPrices();
    }

    public function updatedRegularUserDiscount()
    {
        $this->calculateFinalPrices();
    }

    public function updatedShopOwnerDiscount()
    {
        $this->calculateFinalPrices();
    }

    public function updatedGymOwnerPrice()
    {
        $this->calculateFinalPrices();
    }

    public function updatedRegularUserPrice()
    {
        $this->calculateFinalPrices();
    }

    public function updatedShopOwnerPrice()
    {
        $this->calculateFinalPrices();
    }

    public function save()
    {
        $this->validate();

        // Calculate final prices based on discounts
        $this->calculateFinalPrices();

        // Set default values for empty numeric fields
        $numericFields = [
            'gym_owner_price', 'regular_user_price', 'shop_owner_price', 
            'gym_owner_discount', 'regular_user_discount', 'shop_owner_discount',
            'gym_owner_final_price', 'regular_user_final_price', 'shop_owner_final_price',
            'stock_quantity'
        ];
        
        foreach ($numericFields as $field) {
            if ($this->$field === '' || $this->$field === null) {
                if (in_array($field, ['gym_owner_price', 'regular_user_price', 'shop_owner_price', 'stock_quantity'])) {
                    // Required fields must have a default value of 0
                    $this->$field = 0;
                } else {
                    // Optional fields can be 0 or null depending on database schema
                    $this->$field = 0;
                }
            }
        }

        $data = [
            'seller_id' => $this->seller_id,
            'name' => $this->name,
            'description' => $this->description,
            'gym_owner_price' => $this->gym_owner_price,
            'regular_user_price' => $this->regular_user_price,
            'shop_owner_price' => $this->shop_owner_price,
            'gym_owner_discount' => $this->gym_owner_discount ?: 0,
            'regular_user_discount' => $this->regular_user_discount ?: 0,
            'shop_owner_discount' => $this->shop_owner_discount ?: 0,
            'gym_owner_final_price' => $this->gym_owner_final_price,
            'regular_user_final_price' => $this->regular_user_final_price,
            'shop_owner_final_price' => $this->shop_owner_final_price,
            'stock_quantity' => $this->stock_quantity,
            'weight' => $this->weight,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id ?: null,
            'status' => $this->status,
            'section_category' => $this->section_category,
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
                    'gym_owner_price' => $combination['gym_owner_price'] ?: 0,
                    'regular_user_price' => $combination['regular_user_price'] ?: 0,
                    'shop_owner_price' => $combination['shop_owner_price'] ?: 0,
                    'gym_owner_discount' => $combination['gym_owner_discount'] ?: 0,
                    'regular_user_discount' => $combination['regular_user_discount'] ?: 0,
                    'shop_owner_discount' => $combination['shop_owner_discount'] ?: 0,
                    'gym_owner_final_price' => $combination['gym_owner_final_price'] ?: 0,
                    'regular_user_final_price' => $combination['regular_user_final_price'] ?: 0,
                    'shop_owner_final_price' => $combination['shop_owner_final_price'] ?: 0,
                    'stock_quantity' => $combination['stock_quantity'] ?: 0,
                    'is_active' => $combination['is_active'] ?? true,
                ]);
            }
        }
    }

    public function edit($id)
    {
        $product = products::with(['variants.options', 'variantCombinations', 'images'])->findOrFail($id);
        
        // Check if user is a seller and can only edit their own products
        $user = auth()->user();
        $seller = Sellers::where('user_id', $user->id)->first();
        if ($seller && $product->seller_id !== $seller->id) {
            session()->flash('error', 'You can only edit your own products.');
            return;
        }
        
        $this->productId = $product->id;
        $this->seller_id = $product->seller_id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->gym_owner_price = $product->gym_owner_price;
        $this->regular_user_price = $product->regular_user_price;
        $this->shop_owner_price = $product->shop_owner_price;
        $this->gym_owner_discount = $product->gym_owner_discount;
        $this->regular_user_discount = $product->regular_user_discount;
        $this->shop_owner_discount = $product->shop_owner_discount;
        $this->gym_owner_final_price = $product->gym_owner_final_price;
        $this->regular_user_final_price = $product->regular_user_final_price;
        $this->shop_owner_final_price = $product->shop_owner_final_price;
        $this->stock_quantity = $product->stock_quantity;
        $this->weight = $product->weight;
        $this->category_id = $product->category_id;
        $this->sub_category_id = $product->sub_category_id;
        $this->status = $product->status;
        $this->section_category = $product->section_category;
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
                    ];
                })->toArray(),
                'gym_owner_price' => $combination->gym_owner_price,
                'regular_user_price' => $combination->regular_user_price,
                'shop_owner_price' => $combination->shop_owner_price,
                'gym_owner_discount' => $combination->gym_owner_discount,
                'regular_user_discount' => $combination->regular_user_discount,
                'shop_owner_discount' => $combination->shop_owner_discount,
                'gym_owner_final_price' => $combination->gym_owner_final_price,
                'regular_user_final_price' => $combination->regular_user_final_price,
                'shop_owner_final_price' => $combination->shop_owner_final_price,
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
        $product = products::findOrFail($id);
        
        // Check if user is a seller and can only delete their own products
        $user = auth()->user();
        $seller = Sellers::where('user_id', $user->id)->first();
        if ($seller && $product->seller_id !== $seller->id) {
            session()->flash('error', 'You can only delete your own products.');
            return;
        }
        
        $product->delete();
        session()->flash('message', 'Product deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $product = products::findOrFail($id);
        
        // Check if user is a seller and can only update their own products
        $user = auth()->user();
        $seller = Sellers::where('user_id', $user->id)->first();
        if ($seller) {
            if ($product->seller_id !== $seller->id) {
                session()->flash('error', 'You can only update your own products.');
                return;
            } else {
                session()->flash('error', 'Sellers cannot change product status. Please contact an administrator.');
                return;
            }
        }
        
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
                ['id' => null, 'value' => '', 'display_value' => '']
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
                'gym_owner_price' => $this->gym_owner_price ?: 0,
                'regular_user_price' => $this->regular_user_price ?: 0,
                'shop_owner_price' => $this->shop_owner_price ?: 0,
                'gym_owner_discount' => 0,
                'regular_user_discount' => 0,
                'shop_owner_discount' => 0,
                'gym_owner_final_price' => $this->gym_owner_price ?: 0,
                'regular_user_final_price' => $this->regular_user_price ?: 0,
                'shop_owner_final_price' => $this->shop_owner_price ?: 0,
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

    public function updatedVariantCombinations($value, $key)
    {
        // Check if any discount percentage was updated
        if (strpos($key, '.gym_owner_discount') !== false || 
            strpos($key, '.regular_user_discount') !== false || 
            strpos($key, '.shop_owner_discount') !== false) {
            
            $parts = explode('.', $key);
            $index = $parts[0];
            
            // Calculate gym owner final price
            $gymOwnerDiscount = $this->variant_combinations[$index]['gym_owner_discount'] ?? 0;
            $gymOwnerPrice = $this->variant_combinations[$index]['gym_owner_price'] ?? 0;
            
            if ($gymOwnerDiscount > 0 && $gymOwnerPrice > 0) {
                $this->variant_combinations[$index]['gym_owner_final_price'] = $gymOwnerPrice * (1 - ($gymOwnerDiscount / 100));
            } else {
                $this->variant_combinations[$index]['gym_owner_final_price'] = $gymOwnerPrice;
            }
            
            // Calculate regular user final price
            $regularUserDiscount = $this->variant_combinations[$index]['regular_user_discount'] ?? 0;
            $regularUserPrice = $this->variant_combinations[$index]['regular_user_price'] ?? 0;
            
            if ($regularUserDiscount > 0 && $regularUserPrice > 0) {
                $this->variant_combinations[$index]['regular_user_final_price'] = $regularUserPrice * (1 - ($regularUserDiscount / 100));
            } else {
                $this->variant_combinations[$index]['regular_user_final_price'] = $regularUserPrice;
            }
            
            // Calculate shop owner final price
            $shopOwnerDiscount = $this->variant_combinations[$index]['shop_owner_discount'] ?? 0;
            $shopOwnerPrice = $this->variant_combinations[$index]['shop_owner_price'] ?? 0;
            
            if ($shopOwnerDiscount > 0 && $shopOwnerPrice > 0) {
                $this->variant_combinations[$index]['shop_owner_final_price'] = $shopOwnerPrice * (1 - ($shopOwnerDiscount / 100));
            } else {
                $this->variant_combinations[$index]['shop_owner_final_price'] = $shopOwnerPrice;
            }
        }
    }

    public function viewVariantPrices($productId)
    {
        $product = products::with(['variantCombinations'])->findOrFail($productId);
        $this->selectedProduct = $product;
        
        $this->variantPrices = $product->variantCombinations->map(function($combination) {
            $optionNames = [];
            $options = ProductVariantOption::whereIn('id', $combination->variant_options)->get();
            
            foreach ($options as $option) {
                $variant = $option->variant;
                $optionNames[] = $variant->display_name . ': ' . $option->display_value;
            }
            
            return [
                'id' => $combination->id,
                'variant_name' => implode(' / ', $optionNames),
                'gym_owner_price' => $combination->gym_owner_price,
                'regular_user_price' => $combination->regular_user_price,
                'shop_owner_price' => $combination->shop_owner_price,
                'gym_owner_discount' => $combination->gym_owner_discount,
                'regular_user_discount' => $combination->regular_user_discount,
                'shop_owner_discount' => $combination->shop_owner_discount,
                'gym_owner_final_price' => $combination->gym_owner_final_price,
                'regular_user_final_price' => $combination->regular_user_final_price,
                'shop_owner_final_price' => $combination->shop_owner_final_price,
            ];
        })->toArray();
        
        $this->showVariantPricesModal = true;
    }
    
    public function viewVariantStock($productId)
    {
        $product = products::with(['variantCombinations'])->findOrFail($productId);
        $this->selectedProduct = $product;
        
        $this->variantStock = $product->variantCombinations->map(function($combination) {
            $optionNames = [];
            $options = ProductVariantOption::whereIn('id', $combination->variant_options)->get();
            
            foreach ($options as $option) {
                $variant = $option->variant;
                $optionNames[] = $variant->display_name . ': ' . $option->display_value;
            }
            
            return [
                'id' => $combination->id,
                'variant_name' => implode(' / ', $optionNames),
                'stock_quantity' => $combination->stock_quantity,
                'is_active' => $combination->is_active,
            ];
        })->toArray();
        
        $this->showVariantStockModal = true;
    }
    
    public function viewDetails($productId)
    {
        $product = products::with([
            'seller', 
            'category', 
            'subCategory', 
            'variants.options', 
            'variantCombinations',
            'images'
        ])->findOrFail($productId);
        
        $this->selectedProduct = $product;
        $this->productDetails = $product;
        $this->showDetailsModal = true;
    }
    
    public function closeVariantPricesModal()
    {
        $this->showVariantPricesModal = false;
        $this->variantPrices = [];
        $this->selectedProduct = null;
    }
    
    public function closeVariantStockModal()
    {
        $this->showVariantStockModal = false;
        $this->variantStock = [];
        $this->selectedProduct = null;
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->productDetails = [];
        $this->selectedProduct = null;
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            @if($isSeller)
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Products</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage your product inventory, pricing, and availability</p>
            @else
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products Management</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage product inventory, pricing, and availability</p>
            @endif
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            @if($isSeller)
                                My Products
                            @else
                                Total Products
                            @endif
                        </h3>
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
                        @if($isSeller)
                            Add My Product
                        @else
                            Add Product
                        @endif
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
        
        @if (session()->has('error'))
            <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Products Table -->
        @include('livewire.products.table')
    </div>

    <!-- Modals -->
    @include('livewire.products.modals.product-form-modal')
    @include('livewire.products.modals.variant-prices-modal')
    @include('livewire.products.modals.variant-stock-modal')
    @include('livewire.products.modals.product-details-modal')
</div>
