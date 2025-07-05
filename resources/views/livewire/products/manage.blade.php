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
                                            wire:click="delete({{ $product->id }})"
                                            wire:confirm="Are you sure you want to delete this product?"
                                            class="text-red-600 hover:text-red-900"
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

    <!-- Variant Prices Modal -->
    @if($showVariantPricesModal && $selectedProduct)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-6xl w-full max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            Variant Prices for {{ $selectedProduct->name }}
                        </h2>
                        <button wire:click="closeVariantPricesModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variant</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Type Prices</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Discounts</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Final Prices</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($variantPrices as $variant)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            {{ $variant['variant_name'] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            <div class="flex flex-col gap-1">
                                                @if($variant['gym_owner_price'])
                                                    <div><span class="font-medium text-blue-600">Gym Owner:</span> ₹{{ number_format($variant['gym_owner_price'], 2) }}</div>
                                                @endif
                                                @if($variant['regular_user_price'])
                                                    <div><span class="font-medium text-green-600">Regular User:</span> ₹{{ number_format($variant['regular_user_price'], 2) }}</div>
                                                @endif
                                                @if($variant['shop_owner_price'])
                                                    <div><span class="font-medium text-purple-600">Shop Owner:</span> ₹{{ number_format($variant['shop_owner_price'], 2) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            <div class="flex flex-col gap-1">
                                                <div><span class="font-medium text-blue-600">Gym Owner:</span> 
                                                    @if($variant['gym_owner_discount'] > 0)
                                                        <span class="text-red-600">{{ $variant['gym_owner_discount'] }}%</span>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </div>
                                                <div><span class="font-medium text-green-600">Regular User:</span> 
                                                    @if($variant['regular_user_discount'] > 0)
                                                        <span class="text-red-600">{{ $variant['regular_user_discount'] }}%</span>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </div>
                                                <div><span class="font-medium text-purple-600">Shop Owner:</span> 
                                                    @if($variant['shop_owner_discount'] > 0)
                                                        <span class="text-red-600">{{ $variant['shop_owner_discount'] }}%</span>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            <div class="flex flex-col gap-1">
                                                @if($variant['gym_owner_final_price'])
                                                    <div><span class="font-medium text-blue-600">Gym Owner:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['gym_owner_final_price'], 2) }}</span></div>
                                                @endif
                                                @if($variant['regular_user_final_price'])
                                                    <div><span class="font-medium text-green-600">Regular User:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['regular_user_final_price'], 2) }}</span></div>
                                                @endif
                                                @if($variant['shop_owner_final_price'])
                                                    <div><span class="font-medium text-purple-600">Shop Owner:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['shop_owner_final_price'], 2) }}</span></div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Variant Stock Modal -->
    @if($showVariantStockModal && $selectedProduct)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            Variant Stock for {{ $selectedProduct->name }}
                        </h2>
                        <button wire:click="closeVariantStockModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variant</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($variantStock as $variant)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $variant['variant_name'] }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium {{ $variant['stock_quantity'] <= 10 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                            {{ $variant['stock_quantity'] }}
                                            @if($variant['stock_quantity'] <= 10)
                                                <span class="ml-1 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                                                    Low Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $variant['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $variant['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
</div>
