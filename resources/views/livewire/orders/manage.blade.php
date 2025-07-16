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
    public $editMode = false;
    public $orderId = null;
    public $orderToDelete = null;
    public $orderToToggle = null;
    public $newStatusValue = null;
    
    // Form fields
    public $user_id = '';
    public $seller_id = '';
    public $overall_status = 'pending';
    public $orderItems = []; // Array to hold multiple products
    public $notes = '';

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'seller_id' => 'required|exists:sellers,id',
        'overall_status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
        'orderItems' => 'required|array|min:1',
        'orderItems.*.product_id' => 'required|exists:products,id',
        'orderItems.*.quantity' => 'required|integer|min:1',
        'orderItems.*.unit_price' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
    ];

    public function addOrderItem()
    {
        $this->orderItems[] = [
            'product_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total_amount' => 0,
        ];
    }

    public function removeOrderItem($index)
    {
        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems); // Re-index array
    }

    public function updatedOrderItems($value, $key)
    {
        // Parse the key to get the index and field
        $keyParts = explode('.', $key);
        if (count($keyParts) === 2) {
            $index = $keyParts[0];
            $field = $keyParts[1];
            
            if (in_array($field, ['quantity', 'unit_price']) && isset($this->orderItems[$index])) {
                $this->calculateItemTotal($index);
            }
        }
    }

    public function calculateItemTotal($index)
    {
        if (isset($this->orderItems[$index])) {
            $item = &$this->orderItems[$index];
            if ($item['quantity'] && $item['unit_price']) {
                $item['total_amount'] = $item['quantity'] * $item['unit_price'];
            }
        }
    }

    public function updatedSellerId()
    {
        // Reset order items when seller changes
        $this->orderItems = [];
        $this->addOrderItem();
    }

    public function with()
    {
        $user = auth()->user();
        $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
        $isSeller = $seller !== null;

        // Work with orders but show seller-specific orders
        $query = orders::with(['orderSellerProducts.product', 'orderSellerProducts.seller', 'user']);

        // If user is a seller, only show orders that contain their products
        if ($isSeller) {
            $seller_query = OrderSellerProduct::whereIn('product_id', 
                    products::where('seller_id', $seller->id)->pluck('id')
                )->with(['product', 'order.user'])->latest()->paginate(10);
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

        return [
            'orderCount'=> $isSeller ? $seller_query: null,
            'orders' => $query->latest()->paginate(10),
            'products' => products::where('status', 'active')->get(),
            'sellers' => \App\Models\Sellers::all(),
            'users' => User::all(),
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
        $this->seller_id = $orderItem->seller_id;
        $this->overall_status = $orderItem->status;
        $this->notes = $orderItem->notes ?? '';
        
        // Load order items (single item for seller)
        $this->orderItems = [[
            'product_id' => $orderItem->product_id,
            'quantity' => $orderItem->quantity,
            'unit_price' => $orderItem->unit_price,
            'total_amount' => $orderItem->total_amount,
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
        $this->seller_id = '';
        $this->overall_status = 'pending';
        $this->orderItems = [];
        $this->notes = '';
        $this->addOrderItem(); // Add one empty item
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            // Check if this is a seller editing their order item
            $user = auth()->user();
            $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
            $isSeller = $seller !== null;
            
            if ($isSeller) {
                // Seller editing their order item
                $orderItem = OrderSellerProduct::findOrFail($this->orderId);
                $orderItem->update([
                    'quantity' => $this->orderItems[0]['quantity'],
                    'unit_price' => $this->orderItems[0]['unit_price'],
                    'total_amount' => $this->orderItems[0]['total_amount'],
                    'status' => $this->overall_status,
                    'notes' => $this->notes,
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
                    'status' => $this->overall_status,
                ]);

                // Remove existing order items
                $order->orderSellerProducts()->delete();

                // Add new order items
                foreach ($this->orderItems as $item) {
                    OrderSellerProduct::create([
                        'order_id' => $order->id,
                        'seller_id' => $this->seller_id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_amount' => $item['total_amount'],
                        'status' => $this->overall_status,
                        'notes' => $this->notes,
                    ]);
                }

                session()->flash('message', 'Order updated successfully!');
            }
        } else {
            // Create new order (admin only)
            $order = orders::create([
                'user_id' => $this->user_id,
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'overall_status' => $this->overall_status,
                'total_order_amount' => collect($this->orderItems)->sum('total_amount'),
                'status' => $this->overall_status,
            ]);

            // Create order items
            foreach ($this->orderItems as $item) {
                OrderSellerProduct::create([
                    'order_id' => $order->id,
                    'seller_id' => $this->seller_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['total_amount'],
                    'status' => $this->overall_status,
                    'notes' => $this->notes,
                ]);
            }

            session()->flash('message', 'Order created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $order = orders::with('orderSellerProducts')->findOrFail($id);
        
        $this->orderId = $order->id;
        $this->user_id = $order->user_id;
        $this->overall_status = $order->overall_status;
        $this->notes = $order->orderSellerProducts->first()->notes ?? '';
        
        // Get seller ID from first order item
        $this->seller_id = $order->orderSellerProducts->first()->seller_id ?? '';
        
        // Load order items
        $this->orderItems = $order->orderSellerProducts->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
            ];
        })->toArray();
        
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
            $item->update(['status' => $status]);
            
            // Check if we need to update the main order status based on all items
            $order = $item->order;
            $allItemsStatus = $order->orderSellerProducts()->pluck('status')->unique();
            
            if ($allItemsStatus->count() === 1) {
                // All items have the same status, update order overall_status to match
                $order->update(['overall_status' => $allItemsStatus->first()]);
            } else {
                // Mixed statuses, set order to processing
                $order->update(['overall_status' => 'processing']);
            }
        } else {
            // For admin updating entire order
            $order = $item ?? orders::findOrFail($id);
            
            // Update order status
            $order->update([
                'overall_status' => $status,
                'status' => $status
            ]);
            
            // Update all order items status
            $order->orderSellerProducts()->update(['status' => $status]);
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
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Add Button -->
                    @if(!$isSeller)
                        <button 
                            wire:click="openModal"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Order
                        </button>
                    @endif
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
                                                wire:click="edit({{ $order->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="confirmDelete({{ $order->id }})"
                                                class="text-red-600 hover:text-red-900"
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
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->user->name ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->user->email ?? 'N/A' }}</div>
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
                                                wire:click="editSellerOrder({{ $order->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="confirmDeleteSellerOrder({{ $order->id }})"
                                                class="text-red-600 hover:text-red-900"
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
                                        {{ $order->order->user->name ?? 'User not found' }}
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Product -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                                <select 
                                    wire:model="product_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                    <option value="">Select a product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} - ₹{{ $product->price }}</option>
                                    @endforeach
                                </select>
                                @error('product_id') <span class="text-red-500 text-sm">{{ $errors->first('product_id') }}</span> @enderror
                            </div>

                            <!-- Customer -->
                            <div class="md:col-span-2">
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

                            <!-- Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                                <input 
                                    type="number" 
                                    wire:model.live="quantity"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="1"
                                    min="1"
                                >
                                @error('quantity') <span class="text-red-500 text-sm">{{ $errors->first('quantity') }}</span> @enderror
                            </div>

                            <!-- Unit Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unit Price (₹)</label>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:model.live="unit_price"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="0.00"
                                >
                                @error('unit_price') <span class="text-red-500 text-sm">{{ $errors->first('unit_price') }}</span> @enderror
                            </div>

                            <!-- Total Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Amount (₹)</label>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:model="total_amount"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="0.00"
                                    readonly
                                >
                                @error('total_amount') <span class="text-red-500 text-sm">{{ $errors->first('total_amount') }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                                <select 
                                    wire:model="status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                @error('status') <span class="text-red-500 text-sm">{{ $errors->first('status') }}</span> @enderror
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                                <textarea 
                                    wire:model="notes"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="Order notes (optional)"
                                ></textarea>
                                @error('notes') <span class="text-red-500 text-sm">{{ $errors->first('notes') }}</span> @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
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
                                {{ $newStatusValue === 'confirmed' ? 'bg-blue-100 dark:bg-blue-900/50' : '' }}
                                {{ $newStatusValue === 'shipped' ? 'bg-purple-100 dark:bg-purple-900/50' : '' }}
                                {{ $newStatusValue === 'delivered' ? 'bg-green-100 dark:bg-green-900/50' : '' }}
                                {{ $newStatusValue === 'cancelled' ? 'bg-red-100 dark:bg-red-900/50' : '' }}
                                rounded-full mb-4">
                        @if($newStatusValue === 'pending')
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
</div>
