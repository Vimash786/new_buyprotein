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
    public $showDeleteModal = false;
    public $showStatusModal = false;
    public $editMode = false;
    public $productId = null;
    public $productToDelete = null;
    public $productToToggle = null;
    
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
    
    // Variant image properties
    public $variant_images = []; // Format: ['combination_id' => [array of uploaded files]]
    public $existing_variant_images = []; // Format: ['combination_id' => [array of existing images]]
    public $variant_images_to_delete = [];
    
    // Variant thumbnails
    public $variant_thumbnails = []; // Format: ['combination_id' => uploaded file]
    public $existing_variant_thumbnails = []; // Format: ['combination_id' => existing thumbnail data]
    public $variant_thumbnails_to_delete = [];
    
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
        'thumbnail_image' => 'nullable|image|min:200|max:400', // 200KB to 400KB
        'product_images' => 'nullable|array|max:3', // Maximum 3 images
        'product_images.*' => 'nullable|image|min:200|max:400', // 200KB to 400KB
        'variant_images.*' => 'nullable|array|max:3', // Maximum 3 images per variant
        'variant_images.*.*' => 'nullable|image|min:200|max:400', // 200KB to 400KB
        'variant_thumbnails.*' => 'nullable|image|min:200|max:400', // 200KB to 400KB
    ];

    public function with()
    {
       $user = auth()->user();
       $isSeller = $user && $user->role === 'Seller';
       $seller = Sellers::where('user_id', $user->id)->first();

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
            'seller' => Sellers::where('user_id', $user->id)->first(),
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
        
        // Dispatch event for Quill editor initialization
        $this->dispatch('showModal');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        
        // Dispatch event for Quill editor cleanup
        $this->dispatch('closeModal');
    }

    public function confirmDelete($id)
    {
        $this->productToDelete = products::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->productToDelete = null;
    }

    public function confirmStatusToggle($id)
    {
        $this->productToToggle = products::findOrFail($id);
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->productToToggle = null;
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
        $this->variant_images = [];
        $this->existing_variant_images = [];
        $this->variant_images_to_delete = [];
        $this->variant_thumbnails = [];
        $this->existing_variant_thumbnails = [];
        $this->variant_thumbnails_to_delete = [];
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
            try {
                $data['thumbnail_image'] = $this->storeImageWithValidation($this->thumbnail_image, 'products/thumbnails');
            } catch (\Exception $e) {
                session()->flash('error', 'Thumbnail image error: ' . $e->getMessage());
                return;
            }
        }

        if ($this->editMode) {
            $product = products::findOrFail($this->productId);
            $product->update($data);
            
            // Handle image deletions
            if (!empty($this->images_to_delete)) {
                ProductImage::whereIn('id', $this->images_to_delete)->delete();
            }
            
            // Handle variant image deletions
            if (!empty($this->variant_images_to_delete)) {
                ProductImage::whereIn('id', $this->variant_images_to_delete)->delete();
            }
            
            // Handle variant thumbnail deletions
            if (!empty($this->variant_thumbnails_to_delete)) {
                ProductImage::whereIn('id', $this->variant_thumbnails_to_delete)->delete();
            }
        } else {
            $product = products::create($data);
        }

        // Handle additional product images (for non-variant products)
        if (!$this->has_variants && !empty($this->product_images)) {
            foreach ($this->product_images as $index => $image) {
                if ($image) {
                    try {
                        $imagePath = $this->storeImageWithValidation($image, 'products/images');
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'sort_order' => $index + 1,
                            'is_primary' => false,
                            'file_size' => $image->getSize(),
                            'image_type' => 'product'
                        ]);
                    } catch (\Exception $e) {
                        session()->flash('error', 'Product image error: ' . $e->getMessage());
                        return;
                    }
                }
            }
        }

        // Handle variants if enabled
        if ($this->has_variants && !empty($this->variants)) {
            if ($this->editMode) {
                // In edit mode, we need to handle existing combinations differently
                $combinationMapping = $this->updateVariants($product);
                $this->saveVariantImages($product, $combinationMapping);
                $this->saveVariantThumbnails($product, $combinationMapping);
            } else {
                // In create mode, use the mapping approach
                $combinationMapping = $this->saveVariants($product);
                $this->saveVariantImages($product, $combinationMapping);
                $this->saveVariantThumbnails($product, $combinationMapping);
            }
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

        $combinationMapping = []; // Map form index to database ID

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

        // Save variant combinations and build mapping
        foreach ($this->variant_combinations as $combinationIndex => $combination) {
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
                $createdCombination = ProductVariantCombination::create([
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
                
                // Map form index to database ID
                $combinationMapping[$combinationIndex] = $createdCombination->id;
            }
        }
        
        return $combinationMapping;
    }

    private function updateVariants($product)
    {
        // Store existing variant images before deletion to restore them later
        $existingVariantImages = [];
        if ($product->variantCombinations) {
            foreach ($product->variantCombinations as $combination) {
                $optionValues = [];
                $options = ProductVariantOption::whereIn('id', $combination->variant_options)->get();
                foreach ($options as $option) {
                    $optionValues[] = $option->value;
                }
                sort($optionValues); // Sort to ensure consistent matching
                $combinationKey = implode('|', $optionValues);
                
                // Store images for this combination
                $images = $combination->images;
                if ($images && $images->count() > 0) {
                    $existingVariantImages[$combinationKey] = $images->toArray();
                }
            }
        }

        // Delete existing variants and combinations
        $product->variants()->delete();
        $product->variantCombinations()->delete();

        $combinationMapping = []; // Map form index to new database ID

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

        // Save variant combinations and build mapping
        foreach ($this->variant_combinations as $combinationIndex => $combination) {
            if (empty($combination['options'])) continue;

            $optionIds = array_column($combination['options'], 'id');
            // For new options, we need to get the IDs from the database
            $actualOptionIds = [];
            $optionValues = [];
            
            foreach ($combination['options'] as $option) {
                $variantOption = ProductVariantOption::where('value', $option['value'])
                    ->whereHas('variant', function($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })
                    ->first();
                
                if ($variantOption) {
                    $actualOptionIds[] = $variantOption->id;
                    $optionValues[] = $option['value'];
                }
            }

            if (!empty($actualOptionIds)) {
                $createdCombination = ProductVariantCombination::create([
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
                
                // Map form index to new database ID
                $combinationMapping[$combinationIndex] = $createdCombination->id;
                
                // Update the combination array with the new ID
                $this->variant_combinations[$combinationIndex]['id'] = $createdCombination->id;

                // Restore existing variant images if they exist for this combination
                if (!empty($optionValues)) {
                    sort($optionValues); // Sort to match the key format used above
                    $combinationKey = implode('|', $optionValues);
                    if (isset($existingVariantImages[$combinationKey])) {
                        foreach ($existingVariantImages[$combinationKey] as $imageData) {
                            // Only restore if this image wasn't marked for deletion
                            if (!in_array($imageData['id'], $this->variant_images_to_delete)) {
                                ProductImage::create([
                                    'product_id' => $product->id,
                                    'variant_combination_id' => $createdCombination->id,
                                    'image_path' => $imageData['image_path'],
                                    'sort_order' => $imageData['sort_order'],
                                    'is_primary' => $imageData['is_primary'],
                                    'file_size' => $imageData['file_size'],
                                    'image_type' => $imageData['image_type']
                                ]);
                            }
                        }
                    }
                }
            }
        }
        
        return $combinationMapping;
    }

    private function saveVariantImages($product, $combinationMapping = null)
    {
        // Handle variant images
        if (!empty($this->variant_images)) {
            foreach ($this->variant_images as $combinationIndex => $images) {
                // Determine the actual combination ID
                $actualCombinationId = null;
                
                if ($this->editMode) {
                    // In edit mode, get the actual DB ID from the variant combination
                    if (isset($this->variant_combinations[$combinationIndex]['id'])) {
                        $actualCombinationId = $this->variant_combinations[$combinationIndex]['id'];
                    }
                } else {
                    // In create mode, use the mapping
                    $actualCombinationId = $combinationMapping[$combinationIndex] ?? null;
                }
                
                if ($actualCombinationId && !empty($images)) {
                    foreach ($images as $index => $image) {
                        if ($image) {
                            try {
                                $imagePath = $this->storeImageWithValidation($image, 'products/variants');
                                ProductImage::create([
                                    'product_id' => $product->id,
                                    'variant_combination_id' => $actualCombinationId,
                                    'image_path' => $imagePath,
                                    'sort_order' => $index + 1,
                                    'is_primary' => $index === 0, // First image is primary
                                    'file_size' => $image->getSize(),
                                    'image_type' => 'variant'
                                ]);
                            } catch (\Exception $e) {
                                session()->flash('error', 'Variant image error: ' . $e->getMessage());
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    private function saveVariantThumbnails($product, $combinationMapping = null)
    {
        // Handle variant thumbnails
        if (!empty($this->variant_thumbnails)) {
            foreach ($this->variant_thumbnails as $combinationIndex => $thumbnail) {
                // Determine the actual combination ID
                $actualCombinationId = null;
                
                if ($this->editMode) {
                    // In edit mode, get the actual DB ID from the variant combination
                    if (isset($this->variant_combinations[$combinationIndex]['id'])) {
                        $actualCombinationId = $this->variant_combinations[$combinationIndex]['id'];
                    }
                } else {
                    // In create mode, use the mapping
                    $actualCombinationId = $combinationMapping[$combinationIndex] ?? null;
                }
                
                if ($actualCombinationId && $thumbnail) {
                    try {
                        $imagePath = $this->storeImageWithValidation($thumbnail, 'products/variants/thumbnails');
                        ProductImage::create([
                            'product_id' => $product->id,
                            'variant_combination_id' => $actualCombinationId,
                            'image_path' => $imagePath,
                            'sort_order' => 0, // Thumbnails have sort order 0
                            'is_primary' => false, // Thumbnails are not primary
                            'file_size' => $thumbnail->getSize(),
                            'image_type' => 'variant_thumbnail'
                        ]);
                    } catch (\Exception $e) {
                        session()->flash('error', 'Variant thumbnail error: ' . $e->getMessage());
                        return;
                    }
                }
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
        
        // Load existing images (product images only)
        $this->existing_images = $product->images()->productOnly()->get()->map(function($image) {
            return [
                'id' => $image->id,
                'image_path' => $image->image_path,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'file_size' => $image->file_size,
                'formatted_size' => $image->file_size ? $image->formatted_file_size : null, // Don't show 'Unknown' for legacy images
                'image_url' => asset('storage/' . $image->image_path)
            ];
        })->toArray();
        
        // Load existing variant images
        $this->existing_variant_images = [];
        $this->existing_variant_thumbnails = [];
        if ($product->has_variants) {
            // First collect images by combination ID
            $imagesByComboId = [];
            $thumbnailsByComboId = [];
            
            foreach ($product->variantCombinations as $combination) {
                $variantImages = $combination->images->where('image_type', 'variant')->map(function($image) {
                    return [
                        'id' => $image->id,
                        'image_path' => $image->image_path,
                        'sort_order' => $image->sort_order,
                        'is_primary' => $image->is_primary,
                        'file_size' => $image->file_size,
                        'formatted_size' => $image->file_size ? $image->formatted_file_size : null, // Don't show 'Unknown' for legacy images
                        'image_url' => asset('storage/' . $image->image_path)
                    ];
                })->toArray();
                
                if (!empty($variantImages)) {
                    $imagesByComboId[$combination->id] = $variantImages;
                }
                
                // Load existing variant thumbnails
                $variantThumbnail = $combination->images->where('image_type', 'variant_thumbnail')->first();
                if ($variantThumbnail) {
                    $thumbnailsByComboId[$combination->id] = [
                        'id' => $variantThumbnail->id,
                        'image_path' => $variantThumbnail->image_path,
                        'file_size' => $variantThumbnail->file_size,
                        'formatted_size' => $variantThumbnail->file_size ? $variantThumbnail->formatted_file_size : null, // Don't show 'Unknown' for legacy images
                        'image_url' => asset('storage/' . $variantThumbnail->image_path)
                    ];
                }
            }
        }
        
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
        
        // Now map images and thumbnails to form indices (after variant_combinations is loaded)
        if ($product->has_variants && isset($imagesByComboId, $thumbnailsByComboId)) {
            foreach ($this->variant_combinations as $formIndex => $combination) {
                $dbId = $combination['id'];
                
                // Map variant images from DB ID to form index
                if (isset($imagesByComboId[$dbId])) {
                    $this->existing_variant_images[$formIndex] = $imagesByComboId[$dbId];
                }
                
                // Map variant thumbnails from DB ID to form index
                if (isset($thumbnailsByComboId[$dbId])) {
                    $this->existing_variant_thumbnails[$formIndex] = $thumbnailsByComboId[$dbId];
                }
            }
        }
        
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
        
        // Dispatch events for Quill editor initialization and content loading
        $this->dispatch('showModal');
        $this->dispatch('productLoaded');
    }

    public function delete($id = null)
    {
        $product = $id ? products::findOrFail($id) : $this->productToDelete;
        
        if ($product) {
            // Check if user is a seller and can only delete their own products
            $user = auth()->user();
            $seller = Sellers::where('user_id', $user->id)->first();
            if ($seller && $product->seller_id !== $seller->id) {
                session()->flash('error', 'You can only delete your own products.');
                $this->closeDeleteModal();
                return;
            }
            
            $product->delete();
            session()->flash('message', 'Product deleted successfully!');
            
            $this->closeDeleteModal();
        }
    }

    public function toggleStatus($id = null)
    {
        $product = $id ? products::findOrFail($id) : $this->productToToggle;
        
        if ($product) {
            // Check if user is a seller and can only update their own products
            $user = auth()->user();
            $seller = Sellers::where('user_id', $user->id)->first();
            
            if ($seller) {
                // Sellers can only update their own products' status (not super_status)
                if ($product->seller_id !== $seller->id) {
                    session()->flash('error', 'You can only update your own products.');
                    $this->closeStatusModal();
                    return;
                }
                
                // Sellers can toggle their product status (active/inactive)
                $product->update([
                    'status' => $product->status === 'active' ? 'inactive' : 'active'
                ]);
                
                session()->flash('message', 'Product status updated successfully!');
            } else {
                // Admins can toggle super_status (approved/not_approved)
                $newSuperStatus = $product->super_status === 'approved' ? 'not_approved' : 'approved';
                
                $product->update([
                    'super_status' => $newSuperStatus
                ]);
                
                session()->flash('message', 'Product approval status updated successfully!');
            }
            
            $this->closeStatusModal();
        }
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
            'variantCombinations' => function($query) {
                $query->with(['images']);
            },
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

    /**
     * Validate image file size (200KB to 400KB).
     */
    private function validateImageSize($file)
    {
        if (!$file) return false;
        
        $minSize = 200 * 1024; // 200KB in bytes
        $maxSize = 400 * 1024; // 400KB in bytes
        $fileSize = $file->getSize();
        
        return $fileSize >= $minSize && $fileSize <= $maxSize;
    }

    /**
     * Store image with size validation.
     */
    private function storeImageWithValidation($file, $path)
    {
        if (!$this->validateImageSize($file)) {
            throw new \Exception('Image size must be between 200KB and 400KB. Current size: ' . $this->formatFileSize($file->getSize()));
        }
        
        return $file->store($path, 'public');
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function removeVariantImage($combinationId, $imageIndex)
    {
        if (isset($this->existing_variant_images[$combinationId][$imageIndex])) {
            $this->variant_images_to_delete[] = $this->existing_variant_images[$combinationId][$imageIndex]['id'];
            unset($this->existing_variant_images[$combinationId][$imageIndex]);
            $this->existing_variant_images[$combinationId] = array_values($this->existing_variant_images[$combinationId]);
            
            // Remove the combination key if no images left
            if (empty($this->existing_variant_images[$combinationId])) {
                unset($this->existing_variant_images[$combinationId]);
            }
        }
    }

    public function removeVariantThumbnail($combinationId)
    {
        if (isset($this->existing_variant_thumbnails[$combinationId])) {
            $this->variant_thumbnails_to_delete[] = $this->existing_variant_thumbnails[$combinationId]['id'];
            unset($this->existing_variant_thumbnails[$combinationId]);
        }
    }

    public function getVariantDisplayName($combination)
    {
        if (empty($combination['options'])) {
            return 'Unknown Variant';
        }
        
        $optionNames = [];
        foreach ($combination['options'] as $option) {
            $optionNames[] = $option['display_value'] ?? $option['value'];
        }
        
        return implode(' / ', $optionNames);
    }

    public function getVariantCombinationName($combination)
    {
        if (!$combination || !$combination->variant_options) {
            return 'Unknown Variant';
        }
        
        $optionNames = [];
        $options = ProductVariantOption::whereIn('id', $combination->variant_options)->with('variant')->get();
        
        foreach ($options as $option) {
            $variantName = $option->variant->display_name ?? $option->variant->name;
            $optionValue = $option->display_value ?? $option->value;
            $optionNames[] = $variantName . ': ' . $optionValue;
        }
        
        return empty($optionNames) ? 'Unknown Variant' : implode(' / ', $optionNames);
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
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8" hidden>
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

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $productToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-50 mx-auto p-5 w-120 shadow-lg rounded-md bg-white dark:bg-zinc-900">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-2">Delete Product</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to delete the product "<strong>{{ $productToDelete->name }}</strong>"? 
                            This action cannot be undone and will permanently remove the product, its variants, and all associated data.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <div class="flex gap-3 justify-center">
                            <button
                                wire:click="closeDeleteModal"
                                class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                Cancel
                            </button>
                            <button
                                wire:click="delete"
                                class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status Toggle Confirmation Modal -->
    @if($showStatusModal && $productToToggle)
        @php
            $user = auth()->user();
            $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
            $isSeller = $seller !== null;
            
            if ($isSeller) {
                $currentStatus = $productToToggle->status;
                $statusText = $currentStatus === 'active' ? 'deactivate' : 'activate';
                $actionText = $currentStatus === 'active' ? 'Deactivate' : 'Activate';
                $modalTitle = $actionText . ' Product';
            } else {
                $currentStatus = $productToToggle->super_status;
                $statusText = $currentStatus === 'approved' ? 'remove approval from' : 'approve';
                $actionText = $currentStatus === 'approved' ? 'Remove Approval' : 'Approve';
                $modalTitle = $actionText . ' Product';
            }
        @endphp
        
        <div class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50">
            <div class="relative top-50 mx-auto p-5  w-120 shadow-lg rounded-md bg-white dark:bg-zinc-900">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full 
                                @if($isSeller)
                                    {{ $currentStatus === 'active' ? 'bg-red-100 dark:bg-red-900/50' : 'bg-green-100 dark:bg-green-900/50' }}
                                @else
                                    {{ $currentStatus === 'approved' ? 'bg-red-100 dark:bg-red-900/50' : 'bg-green-100 dark:bg-green-900/50' }}
                                @endif">
                        <svg class="h-6 w-6 
                                    @if($isSeller)
                                        {{ $currentStatus === 'active' ? 'text-red-600' : 'text-green-600' }}
                                    @else
                                        {{ $currentStatus === 'approved' ? 'text-red-600' : 'text-green-600' }}
                                    @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if(($isSeller && $currentStatus === 'active') || (!$isSeller && $currentStatus === 'approved'))
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @endif
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-2">{{ $modalTitle }}</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to {{ $statusText }} the product "<strong>{{ $productToToggle->name }}</strong>"?
                            @if($isSeller)
                                @if($currentStatus === 'active')
                                    This will make your product unavailable for purchase.
                                @else
                                    This will make your product available for purchase (subject to admin approval).
                                @endif
                            @else
                                @if($currentStatus === 'approved')
                                    This will remove admin approval and make the product unavailable for purchase.
                                @else
                                    This will approve the product and make it available for purchase.
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <div class="flex gap-3 justify-center">
                            <button
                                wire:click="closeStatusModal"
                                class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                Cancel
                            </button>
                            <button
                                wire:click="toggleStatus"
                                class="px-4 py-2 
                                       @if(($isSeller && $currentStatus === 'active') || (!$isSeller && $currentStatus === 'approved'))
                                           bg-red-600 hover:bg-red-700 focus:ring-red-500
                                       @else
                                           bg-green-600 hover:bg-green-700 focus:ring-green-500
                                       @endif
                                       text-white text-base font-medium rounded-md shadow-sm focus:outline-none focus:ring-2"
                            >
                                {{ $actionText }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Quill Editor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

    <!-- Custom Quill Dark Mode CSS -->
    <style>
      /* Dark mode styles for Quill editor */
      .dark .ql-toolbar.ql-snow {
        border-color: #4b5563;
        background-color: #374151;
      }
      
      .dark .ql-toolbar.ql-snow .ql-stroke {
        stroke: #d1d5db;
      }
      
      .dark .ql-toolbar.ql-snow .ql-fill {
        fill: #d1d5db;
      }
      
      .dark .ql-toolbar.ql-snow .ql-picker-label {
        color: #d1d5db;
      }
      
      .dark .ql-container.ql-snow {
        border-color: #4b5563;
        background-color: #1f2937;
        color: #f9fafb;
      }
      
      .dark .ql-editor {
        color: #f9fafb;
      }
      
      .dark .ql-editor::before {
        color: #9ca3af;
      }
      
      /* Hover states */
      .dark .ql-toolbar.ql-snow .ql-picker-label:hover {
        color: #ffffff;
      }
      
      .dark .ql-toolbar.ql-snow button:hover {
        color: #ffffff;
      }
      
      .dark .ql-toolbar.ql-snow button:hover .ql-stroke {
        stroke: #ffffff;
      }
      
      .dark .ql-toolbar.ql-snow button:hover .ql-fill {
        fill: #ffffff;
      }
    </style>

    <!-- Quill Editor JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

    <!-- Initialize Quill editor for product description -->
    <script>
      let productDescriptionQuill;
      let quillContent = '';
      
      // Function to initialize or reinitialize Quill editor
      function initializeQuillEditor() {
        const editorElement = document.getElementById('product-description-editor');
        
        if (!editorElement) {
          return;
        }
        
        // Destroy existing editor if it exists
        if (productDescriptionQuill) {
          // Save current content before destroying
          quillContent = productDescriptionQuill.root.innerHTML;
          productDescriptionQuill = null;
        }
        
        // Create new Quill instance
        productDescriptionQuill = new Quill('#product-description-editor', {
          theme: 'snow',
          placeholder: 'Enter product description...',
          modules: {
            toolbar: [
              ['bold', 'italic', 'underline', 'strike'],
              ['blockquote', 'code-block'],
              [{ 'header': 1 }, { 'header': 2 }],
              [{ 'list': 'ordered'}, { 'list': 'bullet' }],
              [{ 'script': 'sub'}, { 'script': 'super' }],
              [{ 'indent': '-1'}, { 'indent': '+1' }],
              [{ 'direction': 'rtl' }],
              [{ 'size': ['small', false, 'large', 'huge'] }],
              [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'font': [] }],
              [{ 'align': [] }],
              ['clean']
            ]
          }
        });

        // Function to load content into Quill
        function loadContentIntoQuill() {
          const hiddenInput = document.getElementById('description-input');
          let contentToLoad = '';
          
          // Priority: saved content > hidden input value > empty
          if (quillContent && quillContent.trim() !== '' && quillContent !== '<p><br></p>') {
            contentToLoad = quillContent;
          } else if (hiddenInput && hiddenInput.value && hiddenInput.value.trim() !== '') {
            contentToLoad = hiddenInput.value;
          }
          
          if (contentToLoad) {
            productDescriptionQuill.root.innerHTML = contentToLoad;
          }
        }

        // Load initial content
        loadContentIntoQuill();

        // Update hidden input and save content on text change
        productDescriptionQuill.on('text-change', function() {
          const content = productDescriptionQuill.root.innerHTML;
          quillContent = content; // Save to global variable
          
          const hiddenInput = document.getElementById('description-input');
          if (hiddenInput) {
            hiddenInput.value = content;
          }
          
          // Hide validation error immediately when typing
          const errorElement = document.querySelector('span.text-red-500');
          if (errorElement && errorElement.textContent.includes('description field is required')) {
            errorElement.style.display = 'none';
          }
        });
      }
      
      // Initialize Quill when modal opens
      document.addEventListener('livewire:init', function () {
        Livewire.on('showModal', () => {
          setTimeout(initializeQuillEditor, 100);
        });

        // Reinitialize Quill after Livewire updates (e.g., when has_variants changes)
        Livewire.hook('morph.updated', () => {
          setTimeout(() => {
            const editorElement = document.getElementById('product-description-editor');
            if (editorElement && (!productDescriptionQuill || !productDescriptionQuill.root.isConnected)) {
              initializeQuillEditor();
            }
          }, 100);
        });

        // Listen for Livewire updates to reload content in edit mode
        Livewire.on('productLoaded', () => {
          setTimeout(() => {
            if (productDescriptionQuill) {
              const hiddenInput = document.getElementById('description-input');
              if (hiddenInput && hiddenInput.value && hiddenInput.value.trim() !== '') {
                productDescriptionQuill.root.innerHTML = hiddenInput.value;
                quillContent = hiddenInput.value;
              }
            }
          }, 100);
        });

        // Cleanup when modal closes
        Livewire.on('closeModal', () => {
          if (productDescriptionQuill) {
            productDescriptionQuill = null;
          }
          quillContent = '';
        });
      });

      // Function to sync content before form submission
      function syncProductDescriptionContent() {
        if (productDescriptionQuill) {
          const content = productDescriptionQuill.root.innerHTML;
          quillContent = content;
          const hiddenInput = document.getElementById('description-input');
          if (hiddenInput) {
            hiddenInput.value = content;
          }
          @this.set('description', content);
        } else if (quillContent) {
          // If editor was destroyed but we have saved content
          const hiddenInput = document.getElementById('description-input');
          if (hiddenInput) {
            hiddenInput.value = quillContent;
          }
          @this.set('description', quillContent);
        }
      }
    </script>
</div>
