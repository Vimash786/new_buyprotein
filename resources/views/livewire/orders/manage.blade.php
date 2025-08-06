<?php

use App\Models\orders;
use App\Models\OrderSellerProduct;
use App\Models\products;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $showStatusModal = false;
    public $showViewModal = false;
    public $editMode = false;
    public $orderId = null;
    public $orderToDelete = null;
    public $orderToToggle = null;
    public $newStatusValue = null;
    public $viewOrder = null;
    
    // Form fields
    public $user_id = '';
    public $selectedProducts = []; // Array of selected product IDs
    public $orderItems = []; // Array to hold multiple products with their details
    public $overall_status = 'pending';
    public $notes = '';

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'selectedProducts' => 'required|array|min:1',
        'selectedProducts.*' => 'required|exists:products,id',
        'overall_status' => 'required|in:pending,processing,partially_shipped,completed,cancelled',
        'orderItems' => 'required|array|min:1',
        'orderItems.*.product_id' => 'required|exists:products,id',
        'orderItems.*.quantity' => 'required|integer|min:1',
        'orderItems.*.unit_price' => 'required|numeric|min:0',
        'orderItems.*.seller_id' => 'required|exists:sellers,id',
        'orderItems.*.variant_combination_id' => 'nullable|exists:product_variant_combinations,id',
        'notes' => 'nullable|string',
    ];

    public function addOrderItem()
    {
        // This method is no longer needed as we'll handle items based on selected products
    }

    public function removeOrderItem($index)
    {
        if (isset($this->orderItems[$index])) {
            // Remove from selectedProducts array as well
            $productId = (string) $this->orderItems[$index]['product_id']; // Convert to string for comparison
            $this->selectedProducts = array_filter($this->selectedProducts, function($id) use ($productId) {
                return $id != $productId;
            });
            
            unset($this->orderItems[$index]);
            $this->orderItems = array_values($this->orderItems); // Re-index array
        }
    }

    public function updatedSelectedProducts()
    {
        // Update orderItems based on selected products
        $currentProductIds = collect($this->orderItems)->pluck('product_id')->toArray();
        $selectedProductIds = array_map('intval', $this->selectedProducts); // Convert strings to integers

        // Remove items for unselected products
        $this->orderItems = array_filter($this->orderItems, function($item) use ($selectedProductIds) {
            return in_array($item['product_id'], $selectedProductIds);
        });

        // Add items for newly selected products
        foreach ($selectedProductIds as $productId) {
            if (!in_array($productId, $currentProductIds)) {
                $product = products::with('activeVariantCombinations')->find($productId);
                if ($product) {
                    $this->orderItems[] = [
                        'product_id' => $productId,
                        'quantity' => 1,
                        'unit_price' => $product->regular_user_final_price ?? $product->regular_user_price ?? 0,
                        'total_amount' => $product->regular_user_final_price ?? $product->regular_user_price ?? 0,
                        'seller_id' => $product->seller_id,
                        'variant_combination_id' => null,
                    ];
                }
            }
        }

        // Re-index array
        $this->orderItems = array_values($this->orderItems);
    }

    public function updatedOrderItems($value, $key)
    {
        // Parse the key to get the index and field
        $keyParts = explode('.', $key);
        if (count($keyParts) === 2) {
            $index = $keyParts[0];
            $field = $keyParts[1];
            
            // Handle variant selection
            if ($field === 'variant_combination_id' && isset($this->orderItems[$index])) {
                $this->updatePriceForVariant($index, $value);
            }
            
            if (in_array($field, ['quantity', 'unit_price']) && isset($this->orderItems[$index])) {
                $this->calculateItemTotal($index);
            }
        }
    }

    public function updatePriceForVariant($index, $variantCombinationId)
    {
        if (!isset($this->orderItems[$index])) {
            return;
        }

        $item = &$this->orderItems[$index];
        
        if ($variantCombinationId) {
            // Get the variant combination price
            $variantCombination = \App\Models\ProductVariantCombination::find($variantCombinationId);
            if ($variantCombination) {
                $item['unit_price'] = $variantCombination->regular_user_final_price ?? $variantCombination->regular_user_price ?? 0;
            }
        } else {
            // Use product's base price
            $product = products::find($item['product_id']);
            if ($product) {
                $item['unit_price'] = $product->regular_user_final_price ?? $product->regular_user_price ?? 0;
            }
        }
        
        // Recalculate total
        $this->calculateItemTotal($index);
    }

    public function calculateItemTotal($index)
    {
        if (isset($this->orderItems[$index])) {
            $item = &$this->orderItems[$index];
            if (isset($item['quantity']) && isset($item['unit_price']) && $item['quantity'] && $item['unit_price']) {
                $item['total_amount'] = $item['quantity'] * $item['unit_price'];
            }
        }
    }

    public function updatedSellerId()
    {
        // This method is no longer needed as seller_id is determined by product selection
    }

    public function with()
    {
        $user = auth()->user();
        $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
        $isSeller = $seller !== null;

        // Work with orders but show seller-specific orders
        $query = orders::with([
            'orderSellerProducts.product.images', 
            'orderSellerProducts.seller', 
            'orderSellerProducts.variantCombination',
            'user',
            'billingDetail.shippingAddress'
        ]);

        // If user is a seller, only show orders that contain their products
        if ($isSeller) {
            $seller_query = OrderSellerProduct::whereIn('product_id', 
                    products::where('seller_id', $seller->id)->pluck('id')
                )->with([
                    'product.images', 
                    'order.user', 
                    'order.billingDetail.shippingAddress',
                    'variantCombination'
                ])->latest()->paginate(10);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('orderSellerProducts.product', function($product) {
                    $product->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('user', function($user) {
                    $user->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('email', 'like', '%' . $this->search . '%');
                })->orWhere('order_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('overall_status', $this->statusFilter);
        }

        // Calculate statistics based on user role
        if ($isSeller) {
            $totalOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->count();
            
            $pendingOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->where('overall_status', 'pending')->count();
            
            $confirmedOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->where('overall_status', 'confirmed')->count();
            
            $shippedOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->where('overall_status', 'shipped')->count();
            
            $deliveredOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->where('overall_status', 'delivered')->count();
            
            $cancelledOrders = orders::whereHas('orderSellerProducts', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->where('overall_status', 'cancelled')->count();
            
            $totalRevenue = OrderSellerProduct::where('seller_id', $seller->id)
                ->whereHas('order', function($orderQuery) {
                    $orderQuery->whereIn('overall_status', ['confirmed', 'shipped', 'delivered']);
                })->sum('total_amount');
        } else {
            $totalOrders = orders::count();
            $pendingOrders = orders::where('overall_status', 'pending')->count();
            $confirmedOrders = orders::where('overall_status', 'confirmed')->count();
            $shippedOrders = orders::where('overall_status', 'shipped')->count();
            $deliveredOrders = orders::where('overall_status', 'delivered')->count();
            $cancelledOrders = orders::where('overall_status', 'cancelled')->count();
            $totalRevenue = orders::whereIn('overall_status', ['confirmed', 'shipped', 'delivered'])
                ->sum('total_order_amount');
        }

        // Debug: Get products for seller
        $sellerProducts = null;
        if ($isSeller) {
            $sellerProducts = products::with('seller')->where('seller_id', $seller->id)->get();
            $activeSellerProducts = products::with('seller')->where('seller_id', $seller->id)->where('status', 'active')->get();
            
            \Log::info('Seller Products Debug', [
                'seller_id' => $seller->id,
                'total_products' => $sellerProducts->count(),
                'active_products' => $activeSellerProducts->count(),
                'all_products' => $sellerProducts->pluck('name', 'id')->toArray(),
                'active_products_details' => $activeSellerProducts->pluck('name', 'id')->toArray(),
                'product_statuses' => $sellerProducts->pluck('status', 'id')->toArray(),
            ]);
        }

        return [
            'orderCount'=> $isSeller ? $seller_query: null,
            'orders' => $query->latest()->paginate(10),
            'products' => $isSeller 
                ? products::with(['seller', 'activeVariantCombinations'])->where('seller_id', $seller->id)->get()
                : products::with(['seller', 'activeVariantCombinations'])->where('status', 'active')->get(),
            'sellers' => \App\Models\Sellers::all(),
            'users' => User::whereNotIn('role', ['Seller', 'Super'])->get(),
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'confirmedOrders' => $confirmedOrders,
            'shippedOrders' => $shippedOrders,
            'deliveredOrders' => $deliveredOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalRevenue' => $totalRevenue,
            'isSeller' => $isSeller,
            'currentSeller' => $seller,
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

    public function openViewModal($id)
    {
        $this->viewOrder = orders::with([
            'user',
            'orderSellerProducts.product.images',
            'orderSellerProducts.seller',
            'orderSellerProducts.variantCombination',
            'billingDetail.shippingAddress'
        ])->findOrFail($id);
        
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewOrder = null;
    }

    public function confirmDelete($id)
    {
        $this->orderToDelete = orders::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->orderToDelete = null;
    }

    public function confirmStatusChange($id, $newStatus)
    {
        $this->orderToToggle = orders::findOrFail($id);
        $this->newStatusValue = $newStatus;
        $this->showStatusModal = true;
    }
    
    public function confirmStatus($id, $newStatus)
    {
        $this->orderToToggle = OrderSellerProduct::findOrFail($id);
        $this->newStatusValue = $newStatus;
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->orderToToggle = null;
        $this->newStatusValue = null;
    }

    public function editSellerOrder($id)
    {
        $orderItem = OrderSellerProduct::with(['order', 'product'])->findOrFail($id);
        
        $this->orderId = $orderItem->id;
        $this->user_id = $orderItem->order->user_id;
        $this->overall_status = $orderItem->status;
        $this->notes = $orderItem->notes ?? '';
        
        // Load selected products and order items (single item for seller) - convert to strings
        $this->selectedProducts = [(string) $orderItem->product_id];
        $this->orderItems = [[
            'product_id' => $orderItem->product_id,
            'quantity' => $orderItem->quantity,
            'unit_price' => $orderItem->unit_price,
            'total_amount' => $orderItem->total_amount,
            'seller_id' => $orderItem->seller_id,
            'variant_combination_id' => $orderItem->variant_combination_id,
        ]];
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function confirmDeleteSellerOrder($id)
    {
        $this->orderToDelete = OrderSellerProduct::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteSellerOrder($id = null)
    {
        $orderItem = $this->orderToDelete ?? OrderSellerProduct::findOrFail($id);
        $order = $orderItem->order;
        
        // Delete the order item
        $orderItem->delete();
        
        // Check if this was the last item in the order
        if ($order->orderSellerProducts()->count() === 0) {
            // Delete the entire order if no items left
            $order->delete();
        } else {
            // Recalculate order total
            $newTotal = $order->orderSellerProducts()->sum('total_amount');
            $order->update(['total_order_amount' => $newTotal]);
        }
        
        session()->flash('message', 'Order item deleted successfully!');
        
        $this->closeDeleteModal();
    }

    public function resetForm()
    {
        $this->orderId = null;
        $this->user_id = '';
        $this->selectedProducts = [];
        $this->overall_status = 'pending';
        $this->orderItems = [];
        $this->notes = '';
        $this->resetValidation();
    }

    public function save()
    {
        // Convert selectedProducts to integers for validation
        $this->selectedProducts = array_map('intval', $this->selectedProducts);
        
        // Get current user and seller info
        $user = auth()->user();
        $currentSeller = \App\Models\Sellers::where('user_id', $user->id)->first();
        $isSeller = $currentSeller !== null;
        
        $this->validate();
        
        // Additional validation for variants
        $this->validateOrderItems();

        if ($this->editMode) {
            if ($isSeller) {
                // Seller editing their order item
                $orderItem = OrderSellerProduct::findOrFail($this->orderId);
                $orderItem->update([
                    'quantity' => $this->orderItems[0]['quantity'],
                    'unit_price' => $this->orderItems[0]['unit_price'],
                    'total_amount' => $this->orderItems[0]['total_amount'],
                    'status' => $this->mapOverallStatusToItemStatus($this->overall_status),
                    'notes' => $this->notes,
                    'variant_combination_id' => $this->orderItems[0]['variant_combination_id'] ?? null,
                    'variant' => $this->getVariantOptions($this->orderItems[0]['variant_combination_id'] ?? null),
                ]);
                
                // Update order total
                $order = $orderItem->order;
                $newTotal = $order->orderSellerProducts()->sum('total_amount');
                $order->update(['total_order_amount' => $newTotal]);
                
                session()->flash('message', 'Order item updated successfully!');
            } else {
                // Admin editing entire order
                $order = orders::findOrFail($this->orderId);
                
                // Update order basic info
                $order->update([
                    'user_id' => $this->user_id,
                    'overall_status' => $this->overall_status,
                    'total_order_amount' => collect($this->orderItems)->sum('total_amount'),
                    'status' => $this->mapOverallStatusToOrderStatus($this->overall_status),
                ]);

                // Remove existing order items
                $order->orderSellerProducts()->delete();

                // Add new order items
                foreach ($this->orderItems as $item) {
                    OrderSellerProduct::create([
                        'order_id' => $order->id,
                        'seller_id' => $item['seller_id'],
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_amount' => $item['total_amount'],
                        'status' => $this->mapOverallStatusToItemStatus($this->overall_status),
                        'notes' => $this->notes,
                        'variant_combination_id' => $item['variant_combination_id'] ?? null,
                        'variant' => $this->getVariantOptions($item['variant_combination_id'] ?? null),
                    ]);
                }

                session()->flash('message', 'Order updated successfully!');
            }
        } else {
            // Create new order
            $order = orders::create([
                'user_id' => $this->user_id,
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'overall_status' => $this->overall_status,
                'total_order_amount' => collect($this->orderItems)->sum('total_amount'),
                'status' => $this->mapOverallStatusToOrderStatus($this->overall_status),
            ]);

            // Create order items
            foreach ($this->orderItems as $item) {
                OrderSellerProduct::create([
                    'order_id' => $order->id,
                    'seller_id' => $isSeller ? $currentSeller->id : $item['seller_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['total_amount'],
                    'status' => $this->mapOverallStatusToItemStatus($this->overall_status),
                    'notes' => $this->notes,
                    'variant_combination_id' => $item['variant_combination_id'] ?? null,
                    'variant' => $this->getVariantOptions($item['variant_combination_id'] ?? null),
                ]);
            }

            session()->flash('message', $isSeller ? 'Order created successfully for customer!' : 'Order created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $order = orders::with('orderSellerProducts.product')->findOrFail($id);
        
        $this->orderId = $order->id;
        $this->user_id = $order->user_id;
        $this->overall_status = $order->overall_status;
        $this->notes = $order->orderSellerProducts->first()->notes ?? '';
        
        // Load selected products - convert to strings to match checkbox values
        $this->selectedProducts = $order->orderSellerProducts->pluck('product_id')->map(function($id) {
            return (string) $id;
        })->toArray();
        
        // Load order items with seller information
        $this->orderItems = $order->orderSellerProducts->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
                'seller_id' => $item->seller_id,
                'variant_combination_id' => $item->variant_combination_id,
            ];
        })->toArray();
        
        // Debug: Log the values to see what's happening
        \Log::info('Edit Order Debug', [
            'order_id' => $this->orderId,
            'selectedProducts' => $this->selectedProducts,
            'orderItems' => $this->orderItems
        ]);
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($id = null)
    {
        $order = $this->orderToDelete ?? orders::findOrFail($id);
        
        // Delete all order items first
        $order->orderSellerProducts()->delete();
        
        // Delete the order
        $order->delete();
        
        session()->flash('message', 'Order deleted successfully!');
        
        $this->closeDeleteModal();
    }

    public function updateStatus($id = null, $newStatus = null)
    {
        $item = $this->orderToToggle ?? null;
        $status = $this->newStatusValue ?? $newStatus;
        
        if ($item instanceof OrderSellerProduct) {
            // For seller updating individual order item - only update the OrderSellerProduct
            $oldStatus = $item->status;
            $item->update(['status' => $status]);
            
            // Get the customer user for email notifications
            $order = $item->order;
            $customer = $order->user;
            
            // Send email notifications for status changes
            if ($customer && $oldStatus !== $status) {
                if ($status === 'shipped') {
                    // Send shipped notification
                    try {
                        \Mail::to($customer->email)->send(new \App\Mail\OrderShip($customer));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send shipped email: ' . $e->getMessage());
                    }
                } elseif ($status === 'delivered') {
                    // Send delivered notification
                    try {
                        \Mail::to($customer->email)->send(new \App\Mail\DeliveryDone($customer));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send delivered email: ' . $e->getMessage());
                    }
                }
            }
            
            // Check if we need to update the main order status based on all items
            $allItemsStatus = $order->orderSellerProducts()->pluck('status')->unique();
            
            if ($allItemsStatus->count() === 1) {
                // All items have the same status, map to appropriate overall_status
                $itemStatus = $allItemsStatus->first();
                $overallStatus = '';
                
                if ($itemStatus === 'delivered') {
                    $overallStatus = 'completed';
                } elseif ($itemStatus === 'shipped') {
                    $overallStatus = 'partially_shipped';
                } elseif ($itemStatus === 'confirmed') {
                    $overallStatus = 'processing';
                } else {
                    // For pending, cancelled, etc. - map to valid overall_status values
                    $allowedStatuses = ['pending', 'processing', 'partially_shipped', 'completed', 'cancelled'];
                    $overallStatus = in_array($itemStatus, $allowedStatuses) ? $itemStatus : 'processing';
                }
                
                // Map overall_status to appropriate status value for orders table
                $orderStatus = $this->mapOverallStatusToOrderStatus($overallStatus);
                
                $order->update([
                    'overall_status' => $overallStatus,
                    'status' => $orderStatus
                ]);
            } else {
                // Mixed statuses, set order to processing or partially_shipped
                if ($allItemsStatus->contains('shipped') || $allItemsStatus->contains('delivered')) {
                    $order->update([
                        'overall_status' => 'partially_shipped',
                        'status' => 'shipped'
                    ]);
                } else {
                    $order->update([
                        'overall_status' => 'processing',
                        'status' => 'confirmed'
                    ]);
                }
            }
        } else {
            // For admin updating entire order
            $order = $item ?? orders::findOrFail($id);
            $oldStatus = $order->overall_status;
            
            // Map overall_status to appropriate status value for the orders table
            $orderStatus = $this->mapOverallStatusToOrderStatus($status);
            
            // Update order status
            $order->update([
                'overall_status' => $status,
                'status' => $orderStatus
            ]);
            
            // Map status for order items
            $itemStatus = $this->mapOverallStatusToItemStatus($status);
            
            // Update all order items status
            $order->orderSellerProducts()->update(['status' => $itemStatus]);
            
            // Send email notifications for admin status changes
            $customer = $order->user;
            if ($customer && $oldStatus !== $status) {
                if ($status === 'shipped') {
                    // Send shipped notification
                    try {
                        \Mail::to($customer->email)->send(new \App\Mail\OrderShip($customer));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send shipped email: ' . $e->getMessage());
                    }
                } elseif ($status === 'delivered') {
                    // Send delivered notification
                    try {
                        \Mail::to($customer->email)->send(new \App\Mail\DeliveryDone($customer));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send delivered email: ' . $e->getMessage());
                    }
                }
            }
        }
        
        session()->flash('message', 'Order status updated successfully!');
        
        $this->closeStatusModal();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function validateOrderItems()
    {
        foreach ($this->orderItems as $index => $item) {
            $product = products::with('activeVariantCombinations')->find($item['product_id']);
            
            // Check if variant is required but not selected
            if ($product && $product->has_variants && $product->activeVariantCombinations->count() > 0) {
                // If product has variants, it's recommended but not required to select one
                // The base product price will be used if no variant is selected
            }
        }
    }

    private function getVariantOptions($variantCombinationId)
    {
        if (!$variantCombinationId) {
            return null;
        }

        $variant = \App\Models\ProductVariantCombination::find($variantCombinationId);
        return $variant ? $variant->variant_options : null;
    }

    /**
     * Map overall_status values to valid status values for orders table
     */
    private function mapOverallStatusToOrderStatus($overallStatus)
    {
        $mapping = [
            'pending' => 'pending',
            'processing' => 'confirmed',
            'partially_shipped' => 'shipped',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
        ];

        return $mapping[$overallStatus] ?? 'pending';
    }

    /**
     * Map overall_status values to valid status values for order_seller_products table
     */
    private function mapOverallStatusToItemStatus($overallStatus)
    {
        $mapping = [
            'pending' => 'pending',
            'processing' => 'confirmed',
            'partially_shipped' => 'shipped',
            'completed' => 'delivered',
            'cancelled' => 'cancelled',
        ];

        return $mapping[$overallStatus] ?? 'pending';
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Orders Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Track and manage customer orders and fulfillment</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8" hidden>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Total Orders</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $totalOrders }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Pending</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Confirmed</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $confirmedOrders }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Shipped</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $shippedOrders }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Delivered</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $deliveredOrders }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Revenue</h3>
                    <p class="text-xl font-bold text-green-600">₹{{ number_format($totalRevenue, 2) }}</p>
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
                                placeholder="Search orders..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="partially_shipped">Partially Shipped</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
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
                        {{ $isSeller ? 'Create Order' : 'Add Order' }}
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

        <!-- Orders Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            @if(!$isSeller)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Products</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Items Count</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            @else
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                       @if(!$isSeller)
                            @forelse($orders as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <button 
                                                wire:click="openViewModal({{ $order->id }})"
                                                class="text-green-600 hover:text-green-900"
                                                title="View Order Details"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="edit({{ $order->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Edit Order"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <a 
                                                href="{{ route('invoice.order.download', $order->id) }}"
                                                class="text-purple-600 hover:text-purple-900"
                                                title="Download Invoice PDF"
                                                target="_blank"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                            <a 
                                                href="{{ route('invoice.standard.download', $order->id) }}"
                                                class="text-orange-600 hover:text-orange-900"
                                                title="Download Standard Invoice PDF"
                                                target="_blank"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                            <button 
                                                wire:click="confirmDelete({{ $order->id }})"
                                                class="text-red-600 hover:text-red-900"
                                                title="Delete Order"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        #{{ $order->order_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $order->user->name ?? ($order->guest_email ? 'Guest (' . $order->guest_email . ')' : 'N/A') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $order->user->email ?? ($order->guest_email ?? 'N/A') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs">
                                            @foreach($order->orderSellerProducts->take(2) as $item)
                                                <div class="text-sm text-gray-900 dark:text-white">{{ $item->product->name ?? 'N/A' }} ({{ $item->quantity }})</div>
                                            @endforeach
                                            @if($order->orderSellerProducts->count() > 2)
                                                <div class="text-sm text-gray-500 dark:text-gray-400">+{{ $order->orderSellerProducts->count() - 2 }} more...</div>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->orderSellerProducts->first()->seller->company_name ?? 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $order->orderSellerProducts->count() }} items
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        ₹{{ number_format($order->total_order_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="relative inline-block">
                                            <select 
                                                onchange="if(this.value !== '{{ $order->overall_status }}') { @this.call('confirmStatusChange', {{ $order->id }}, this.value); } else { this.value = '{{ $order->overall_status }}'; }"
                                                class="appearance-none bg-transparent border-0 text-xs font-medium rounded-full px-3 py-1 pr-8
                                                    {{ $order->overall_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $order->overall_status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $order->overall_status === 'partially_shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $order->overall_status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $order->overall_status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}"
                                            >
                                                <option value="pending" {{ $order->overall_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="processing" {{ $order->overall_status === 'processing' ? 'selected' : '' }}>Processing</option>
                                                <option value="partially_shipped" {{ $order->overall_status === 'partially_shipped' ? 'selected' : '' }}>Partially Shipped</option>
                                                <option value="completed" {{ $order->overall_status === 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ $order->overall_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> 
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $order->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No orders found.
                                    </td>
                                </tr>
                            @endforelse
                        @else
                             @forelse($orderCount as $order)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <button 
                                                wire:click="openViewModal({{ $order->order->id }})"
                                                class="text-green-600 hover:text-green-900"
                                                title="View Order Details"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="editSellerOrder({{ $order->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                                title="Edit Order Item"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <a 
                                                href="{{ route('invoice.seller.download', $order->id) }}"
                                                class="text-purple-600 hover:text-purple-900"
                                                title="Download Invoice PDF"
                                                target="_blank"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </a>
                                            <button 
                                                wire:click="confirmDeleteSellerOrder({{ $order->id }})"
                                                class="text-red-600 hover:text-red-900"
                                                title="Delete Order Item"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        #{{ $order->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $order->product->name ?? 'Product not found' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $order->order->user->name ?? ($order->order->guest_email ? 'Guest (' . $order->order->guest_email . ')' : 'User not found') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                        ₹{{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="relative inline-block">
                                            <select 
                                                onchange="if(this.value !== '{{ $order->status }}') { @this.call('confirmStatus', {{ $order->id }}, this.value); } else { this.value = '{{ $order->status }}'; }"
                                                class="appearance-none bg-transparent border-0 text-xs font-medium rounded-full px-3 py-1 pr-8
                                                    {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $order->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $order->status === 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $order->status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}"
                                            >
                                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                                <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> 
                                            </select>

                                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $order->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No orders found.
                                    </td>
                                </tr>
                             @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            @if(!$isSeller)
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $orderCount->links() }}
                </div>
            @endif

        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Order' : 'Add New Order' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <!-- Customer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer</label>
                            <select 
                                wire:model="user_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Select a customer</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-red-500 text-sm">{{ $errors->first('user_id') }}</span> @enderror
                        </div>

                        <!-- Products Multi-Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Products</label>
                            
                            <!-- Debug info -->
                            @if($editMode)
                                <div class="text-xs text-gray-500 mb-2">
                                    Debug - Selected Products: {{ json_encode($selectedProducts) }}
                                </div>
                            @endif
                            
                            <!-- Debug info for seller products -->
                            @if($isSeller)
                                <div class="text-xs text-gray-500 mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <strong>Debug - Seller Products:</strong><br>
                                    Total Products Available: {{ count($products) }}<br>
                                    Seller ID: {{ $currentSeller->id ?? 'N/A' }}<br>
                                    @if(count($products) > 0)
                                        Product IDs: {{ $products->pluck('id')->implode(', ') }}<br>
                                        Product Names: {{ $products->pluck('name')->implode(', ') }}<br>
                                        Product Statuses: {{ $products->pluck('status')->implode(', ') }}
                                    @else
                                        <span class="text-red-600">No products found for this seller!</span>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-zinc-800 max-h-48 overflow-y-auto">
                                @foreach($products as $product)
                                    @php
                                        $isChecked = in_array((string)$product->id, $selectedProducts);
                                    @endphp
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-zinc-700 rounded cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            wire:model.live="selectedProducts" 
                                            value="{{ $product->id }}"
                                            {{ $isChecked ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ₹{{ number_format($product->regular_user_final_price ?? $product->regular_user_price ?? 0, 2) }}
                                                | Seller: {{ $product->seller->company_name ?? 'N/A' }}
                                                @if($product->has_variants && $product->activeVariantCombinations->count() > 0)
                                                    | <span class="text-blue-600">Has {{ $product->activeVariantCombinations->count() }} variants</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedProducts') <span class="text-red-500 text-sm">{{ $errors->first('selectedProducts') }}</span> @enderror
                        </div>

                        <!-- Order Items Details -->
                        @if(count($orderItems) > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order Items</label>
                                <div class="space-y-3">
                                    @foreach($orderItems as $index => $item)
                                        @php
                                            $product = $products->firstWhere('id', $item['product_id']);
                                        @endphp
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-zinc-800">
                                            <div class="flex items-center justify-between mb-3">
                                                <div>
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $product->name ?? 'Product not found' }}</h4>
                                                    <!-- Debug info for product lookup -->
                                                    <div class="text-xs text-gray-500">
                                                        Looking for Product ID: {{ $item['product_id'] }} | Found: {{ $product ? 'Yes' : 'No' }}
                                                        @if($product)
                                                            | Product Name: {{ $product->name }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <button 
                                                    type="button"
                                                    wire:click="removeOrderItem({{ $index }})"
                                                    class="text-red-600 hover:text-red-800"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <!-- Variant Selection (only if product has variants) -->
                                @php
                                    $hasVariants = $product && $product->has_variants && $product->activeVariantCombinations->count() > 0;
                                @endphp
                                @if($hasVariants)
                                    <div class="md:col-span-4 mb-2">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Product Variant</label>
                                        <select 
                                            wire:model.live="orderItems.{{ $index }}.variant_combination_id"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                        >
                                            <option value="">Select variant (optional)</option>
                                            @foreach($product->activeVariantCombinations as $variant)
                                                <option value="{{ $variant->id }}">
                                                    {{ $variant->getFormattedVariantText() }}
                                                    - ₹{{ number_format($variant->regular_user_final_price ?? $variant->regular_user_price ?? 0, 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($hasVariants)
                                            <p class="text-xs text-blue-600 mt-1">This product has variants available</p>
                                        @endif
                                        @error("orderItems.{$index}.variant_combination_id") <span class="text-red-500 text-xs">{{ $errors->first("orderItems.{$index}.variant_combination_id") }}</span> @enderror
                                    </div>
                                @endif

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Quantity</label>
                                    <input 
                                        type="number" 
                                        wire:model.live="orderItems.{{ $index }}.quantity"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                        min="1"
                                    >
                                    @error("orderItems.{$index}.quantity") <span class="text-red-500 text-xs">{{ $errors->first("orderItems.{$index}.quantity") }}</span> @enderror
                                </div>

                                <!-- Unit Price -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Unit Price (₹)</label>
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        wire:model.live="orderItems.{{ $index }}.unit_price"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-700 text-gray-900 dark:text-white"
                                    >
                                    @error("orderItems.{$index}.unit_price") <span class="text-red-500 text-xs">{{ $errors->first("orderItems.{$index}.unit_price") }}</span> @enderror
                                </div>

                                <!-- Total Amount -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Total Amount (₹)</label>
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        wire:model="orderItems.{{ $index }}.total_amount"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100 dark:bg-zinc-600 text-gray-900 dark:text-white"
                                        readonly
                                    >
                                </div>
                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Overall Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Overall Status</label>
                            <select 
                                wire:model="overall_status"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="partially_shipped">Partially Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            @error('overall_status') <span class="text-red-500 text-sm">{{ $errors->first('overall_status') }}</span> @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                            <textarea 
                                wire:model="notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Order notes (optional)"
                            ></textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $errors->first('notes') }}</span> @enderror
                        </div>

                        <!-- Order Total -->
                        @if(count($orderItems) > 0)
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-medium text-gray-900 dark:text-white">Total Order Amount:</span>
                                    <span class="text-xl font-bold text-green-600">
                                        ₹{{ number_format(collect($orderItems)->sum('total_amount'), 2) }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                                @if(count($orderItems) === 0) disabled @endif
                            >
                                {{ $editMode ? 'Update Order' : 'Create Order' }}
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
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $orderToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        @if($orderToDelete instanceof \App\Models\OrderSellerProduct)
                            Delete Order Item
                        @else
                            Delete Order
                        @endif
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        @if($orderToDelete instanceof \App\Models\OrderSellerProduct)
                            Are you sure you want to delete this order item? This action cannot be undone and will permanently remove this item from the order.
                        @else
                            Are you sure you want to delete this order? This action cannot be undone and will permanently remove this order and all its items from the system.
                        @endif
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Item:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    @if($orderToDelete instanceof \App\Models\OrderSellerProduct)
                                        Order Item
                                    @else
                                        Full Order
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Action:</span>
                                <span class="font-medium text-red-600">
                                    @if($orderToDelete instanceof \App\Models\OrderSellerProduct)
                                        Delete Item
                                    @else
                                        Delete Order
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="{{ $orderToDelete instanceof \App\Models\OrderSellerProduct ? 'deleteSellerOrder' : 'delete' }}"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            @if($orderToDelete instanceof \App\Models\OrderSellerProduct)
                                Delete Item
                            @else
                                Delete Order
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status Change Confirmation Modal -->
    @if($showStatusModal && $orderToToggle && $newStatusValue)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto 
                                {{ $newStatusValue === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/50' : '' }}
                                {{ $newStatusValue === 'processing' ? 'bg-blue-100 dark:bg-blue-900/50' : '' }}
                                {{ $newStatusValue === 'partially_shipped' ? 'bg-purple-100 dark:bg-purple-900/50' : '' }}
                                {{ $newStatusValue === 'completed' ? 'bg-green-100 dark:bg-green-900/50' : '' }}
                                {{ $newStatusValue === 'confirmed' ? 'bg-blue-100 dark:bg-blue-900/50' : '' }}
                                {{ $newStatusValue === 'shipped' ? 'bg-purple-100 dark:bg-purple-900/50' : '' }}
                                {{ $newStatusValue === 'delivered' ? 'bg-green-100 dark:bg-green-900/50' : '' }}
                                {{ $newStatusValue === 'cancelled' ? 'bg-red-100 dark:bg-red-900/50' : '' }}
                                rounded-full mb-4">
                        @if($newStatusValue === 'pending')
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0114 0z" />
                            </svg>
                        @elseif($newStatusValue === 'processing')
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        @elseif($newStatusValue === 'partially_shipped')
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        @elseif($newStatusValue === 'completed')
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($newStatusValue === 'confirmed')
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($newStatusValue === 'shipped')
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        @elseif($newStatusValue === 'delivered')
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif($newStatusValue === 'cancelled')
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        Change Order Status
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-4">
                        @if($orderToToggle instanceof \App\Models\OrderSellerProduct)
                            Are you sure you want to change the status of this order item to "<strong>{{ ucfirst($newStatusValue) }}</strong>"?
                        @else
                            Are you sure you want to change the status of this order to "<strong>{{ ucfirst($newStatusValue) }}</strong>"?
                        @endif
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    @if($orderToToggle instanceof \App\Models\OrderSellerProduct)
                                        Order Item
                                    @else
                                        Full Order
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">New Status:</span>
                                <span class="font-medium text-blue-600">{{ ucfirst($newStatusValue) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    @if($newStatusValue === 'cancelled')
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <div class="text-sm text-red-700 dark:text-red-300">
                                    <strong>Warning:</strong> Cancelling this order may require additional steps like processing refunds or updating inventory.
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="closeStatusModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="updateStatus"
                            class="px-4 py-2 text-sm font-medium text-white 
                                   {{ $newStatusValue === 'pending' ? 'bg-yellow-600 hover:bg-yellow-700' : '' }}
                                   {{ $newStatusValue === 'processing' ? 'bg-blue-600 hover:bg-blue-700' : '' }}
                                   {{ $newStatusValue === 'partially_shipped' ? 'bg-purple-600 hover:bg-purple-700' : '' }}
                                   {{ $newStatusValue === 'completed' ? 'bg-green-600 hover:bg-green-700' : '' }}
                                   {{ $newStatusValue === 'confirmed' ? 'bg-blue-600 hover:bg-blue-700' : '' }}
                                   {{ $newStatusValue === 'shipped' ? 'bg-purple-600 hover:bg-purple-700' : '' }}
                                   {{ $newStatusValue === 'delivered' ? 'bg-green-600 hover:bg-green-700' : '' }}
                                   {{ $newStatusValue === 'cancelled' ? 'bg-red-600 hover:bg-red-700' : '' }}
                                   rounded-lg"
                        >
                            Change to {{ ucfirst($newStatusValue) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Order Details Modal -->
    @if($showViewModal && $viewOrder)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Order Details: #{{ $viewOrder->order_number }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Created on {{ $viewOrder->created_at->format('M d, Y \a\t g:i A') }}
                            </p>
                        </div>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column: Order Information -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Order Summary -->
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Order Summary</h3>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Order Status:</span>
                                        <span class="ml-2 inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            {{ $viewOrder->overall_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $viewOrder->overall_status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $viewOrder->overall_status === 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $viewOrder->overall_status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $viewOrder->overall_status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst($viewOrder->overall_status) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Total Amount:</span>
                                        <span class="ml-2 font-semibold text-green-600">₹{{ number_format($viewOrder->total_order_amount, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Total Items:</span>
                                        <span class="ml-2">{{ $viewOrder->orderSellerProducts->count() }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Payment Status:</span>
                                        @if($viewOrder->billingDetail)
                                            <span class="ml-2 inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                {{ $viewOrder->billingDetail->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $viewOrder->billingDetail->payment_status === 'completed' || $viewOrder->billingDetail->payment_status === 'complete' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $viewOrder->billingDetail->payment_status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ ucfirst($viewOrder->billingDetail->payment_status) }}
                                            </span>
                                        @else
                                            <span class="ml-2 text-gray-500">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Information -->
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Customer Information</h3>
                                @if($viewOrder->user)
                                    <div class="space-y-2 text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Name:</span>
                                            <span class="ml-2 font-medium">
                                                {{ $viewOrder->user->name ?? ($viewOrder->guest_email ? 'Guest Customer' : 'N/A') }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Email:</span>
                                            <span class="ml-2">{{ $viewOrder->user->email ?? ($viewOrder->guest_email ?? 'N/A') }}</span>
                                        </div>
                                        @if($viewOrder->billingDetail)
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                                                <span class="ml-2">{{ $viewOrder->billingDetail->billing_phone }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-500">Customer information not available</p>
                                @endif
                            </div>

                            <!-- Order Items -->
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Order Items</h3>
                                <div class="space-y-4">
                                    @foreach($viewOrder->orderSellerProducts as $item)
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-zinc-900">
                                            <div class="flex items-start space-x-4">
                                                <!-- Product Image -->
                                                <div class="flex-shrink-0">
                                                    @if($item->product && $item->product->images->count() > 0)
                                                        <img src="{{ asset('storage/products/' . $item->product->images->first()->image_path) }}" 
                                                             alt="{{ $item->product->name }}" 
                                                             class="w-16 h-16 object-cover rounded-lg">
                                                    @else
                                                        <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <!-- Product Details -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex justify-between items-start">
                                                        <div>
                                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                                {{ $item->product->name ?? 'Product not found' }}
                                                            </h4>
                                                            @if($item->variantCombination)
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                    Variant: {{ $item->variantCombination->getFormattedVariantText() }}
                                                                </p>
                                                            @endif
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                Seller: {{ $item->seller->company_name ?? 'N/A' }}
                                                            </p>
                                                            @if($item->notes)
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                    Notes: {{ $item->notes }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                                ₹{{ number_format($item->total_amount, 2) }}
                                                            </p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $item->quantity }} × ₹{{ number_format($item->unit_price, 2) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Item Status -->
                                                    <div class="mt-2">
                                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                            {{ $item->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                            {{ $item->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : '' }}
                                                            {{ $item->status === 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                                            {{ $item->status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                                            {{ $item->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                                            {{ ucfirst($item->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Billing & Shipping -->
                        <div class="space-y-6">
                            <!-- Billing Information -->
                            @if($viewOrder->billingDetail)
                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Billing Information</h3>
                                    <div class="space-y-2 text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                                            <span class="ml-2">{{ $viewOrder->billingDetail->billing_phone }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-400">Address:</span>
                                            <div class="ml-2 mt-1">
                                                {{ $viewOrder->billingDetail->billing_address }}<br>
                                                {{ $viewOrder->billingDetail->billing_city }}, {{ $viewOrder->billingDetail->billing_state }}<br>
                                                {{ $viewOrder->billingDetail->billing_postal_code }}<br>
                                                {{ $viewOrder->billingDetail->billing_country }}
                                            </div>
                                        </div>
                                        @if($viewOrder->billingDetail->gst_number)
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">GST Number:</span>
                                                <span class="ml-2 font-mono">{{ $viewOrder->billingDetail->gst_number }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Shipping Information -->
                                @if($viewOrder->billingDetail->shippingAddress)
                                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Shipping Information</h3>
                                        <div class="space-y-2 text-sm">
                                            @if($viewOrder->billingDetail->shippingAddress->recipient_name)
                                                <div>
                                                    <span class="text-gray-600 dark:text-gray-400">Recipient:</span>
                                                    <span class="ml-2">{{ $viewOrder->billingDetail->shippingAddress->recipient_name }}</span>
                                                </div>
                                            @endif
                                            @if($viewOrder->billingDetail->shippingAddress->recipient_phone)
                                                <div>
                                                    <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                                                    <span class="ml-2">{{ $viewOrder->billingDetail->shippingAddress->recipient_phone }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="text-gray-600 dark:text-gray-400">Address:</span>
                                                <div class="ml-2 mt-1">
                                                    {{ $viewOrder->billingDetail->shippingAddress->address_line_1 }}<br>
                                                    @if($viewOrder->billingDetail->shippingAddress->address_line_2)
                                                        {{ $viewOrder->billingDetail->shippingAddress->address_line_2 }}<br>
                                                    @endif
                                                    {{ $viewOrder->billingDetail->shippingAddress->city }}, {{ $viewOrder->billingDetail->shippingAddress->state }}<br>
                                                    {{ $viewOrder->billingDetail->shippingAddress->postal_code }}<br>
                                                    {{ $viewOrder->billingDetail->shippingAddress->country }}
                                                </div>
                                            </div>
                                            @if($viewOrder->billingDetail->shippingAddress->landmark)
                                                <div>
                                                    <span class="text-gray-600 dark:text-gray-400">Landmark:</span>
                                                    <span class="ml-2">{{ $viewOrder->billingDetail->shippingAddress->landmark }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Payment Details -->
                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Payment Details</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                            <span>₹{{ number_format($viewOrder->billingDetail->subtotal, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Tax Amount:</span>
                                            <span>₹{{ number_format($viewOrder->billingDetail->tax_amount, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">Shipping Charge:</span>
                                            <span>₹{{ number_format($viewOrder->billingDetail->shipping_charge, 2) }}</span>
                                        </div>
                                        @if($viewOrder->billingDetail->discount_amount > 0)
                                            <div class="flex justify-between text-green-600">
                                                <span>Discount:</span>
                                                <span>-₹{{ number_format($viewOrder->billingDetail->discount_amount, 2) }}</span>
                                            </div>
                                        @endif
                                        <div class="border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                                            <div class="flex justify-between font-semibold text-lg">
                                                <span class="text-gray-900 dark:text-white">Total:</span>
                                                <span class="text-green-600">₹{{ number_format($viewOrder->billingDetail->total_amount, 2) }}</span>
                                            </div>
                                        </div>
                                        @if($viewOrder->billingDetail->payment_method)
                                            <div class="flex justify-between pt-2">
                                                <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                                                <span class="capitalize">{{ str_replace('_', ' ', $viewOrder->billingDetail->payment_method) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Billing Information</h3>
                                    <p class="text-gray-500 dark:text-gray-400">No billing information available</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                        <button 
                            wire:click="closeViewModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
