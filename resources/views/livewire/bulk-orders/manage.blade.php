<?php

use App\Models\BulkOrder;
use App\Models\products;
use App\Models\User;
use App\Models\Sellers;
use App\Models\ProductVariantCombination;
use App\Models\ProductVariantOption;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $editMode = false;
    public $bulkOrderId = null;
    public $bulkOrderToDelete = null;
    public $viewBulkOrder = null;
    
    // Form fields
    public $user_id = '';
    public $seller_id = '';
    public $product_id = '';
    public $variant_combination_id = '';
    public $variant_option_ids = [];
    public $quantity = 1;
    public $notes = '';
    public $selectedProductVariants = [];

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'seller_id' => 'required|exists:sellers,id',
        'product_id' => 'required|exists:products,id',
        'variant_combination_id' => 'nullable|exists:product_variant_combinations,id',
        'quantity' => 'required|integer|min:1',
        'variant_option_ids' => 'nullable|array',
        'notes' => 'nullable|string',
    ];

    public function with()
    {
        $user = auth()->user();
        $isSeller = $user && $user->role === 'Seller';
        
        if ($isSeller && $user) {
            // Get the seller record for the authenticated user
            $seller = Sellers::where('user_id', $user->id)->first();
            
            if ($seller) {
                $query = BulkOrder::where('seller_id', $seller->id)
                    ->with(['user', 'seller', 'product']);
                
                if ($this->search) {
                    $query->whereHas('user', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                }
                
                $bulkOrders = $query->latest()->paginate(10);
                
                // Stats for seller
                $totalBulkOrders = BulkOrder::where('seller_id', $seller->id)->count();
                $totalQuantity = BulkOrder::where('seller_id', $seller->id)->sum('quantity');
                
                // Get seller's products for the dropdown
                $sellerProducts = products::where('seller_id', $seller->id)
                    ->where('status', 'active')
                    ->get();
                
                return [
                    'bulkOrders' => $bulkOrders,
                    'users' => User::where('role', '!=', 'Super')->get(),
                    'sellers' => collect([$seller]), // Only current seller
                    'sellerProducts' => $sellerProducts,
                    'totalBulkOrders' => $totalBulkOrders,
                    'totalQuantity' => $totalQuantity,
                    'isSeller' => true,
                ];
            }
        }
        
        // Default return for non-sellers or if no seller found
        return [
            'bulkOrders' => collect(),
            'users' => collect(),
            'sellers' => collect(),
            'sellerProducts' => collect(),
            'totalBulkOrders' => 0,
            'totalQuantity' => 0,
            'isSeller' => $isSeller,
        ];
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
        
        // Pre-fill seller_id if user is a seller
        $user = auth()->user();
        if ($user && $user->role === 'Seller') {
            $seller = Sellers::where('user_id', $user->id)->first();
            if ($seller) {
                $this->seller_id = $seller->id;
            }
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editMode = false;
        $this->bulkOrderId = null;
        $this->user_id = '';
        $this->seller_id = '';
        $this->product_id = '';
        $this->variant_combination_id = '';
        $this->variant_option_ids = [];
        $this->quantity = 1;
        $this->notes = '';
        $this->selectedProductVariants = [];
        $this->resetValidation();
    }

    public function updatedProductId()
    {
        // Reset variant selection when product changes
        $this->variant_combination_id = '';
        $this->variant_option_ids = [];
        
        // Load product variants
        if ($this->product_id) {
            $product = products::with(['variantCombinations.images', 'variantCombinations' => function($query) {
                $query->where('is_active', true);
            }])->find($this->product_id);
            
            if ($product && $product->variantCombinations->count() > 0) {
                $this->selectedProductVariants = $product->variantCombinations->map(function($combination) {
                    $options = ProductVariantOption::whereIn('id', $combination->variant_options)->with('variant')->get();
                    $variantText = [];
                    foreach ($options->groupBy('variant.name') as $variantName => $variantOptions) {
                        foreach ($variantOptions as $option) {
                            $variantText[] = ucfirst($variantName) . ': ' . ($option->display_value ?? $option->value);
                        }
                    }
                    
                    return [
                        'id' => $combination->id,
                        'text' => implode(', ', $variantText),
                        'price' => $combination->regular_user_final_price ?? $combination->regular_user_price,
                        'stock' => $combination->stock_quantity,
                    ];
                })->toArray();
            } else {
                $this->selectedProductVariants = [];
            }
        }
    }

    public function save()
    {
        $this->validate();

        try {
            // Get variant option IDs if variant combination is selected
            $variantOptionIds = null;
            if ($this->variant_combination_id) {
                $combination = ProductVariantCombination::find($this->variant_combination_id);
                if ($combination) {
                    $variantOptionIds = $combination->variant_options;
                }
            }

            if ($this->editMode) {
                $bulkOrder = BulkOrder::findOrFail($this->bulkOrderId);
                $bulkOrder->update([
                    'user_id' => $this->user_id,
                    'seller_id' => $this->seller_id,
                    'product_id' => $this->product_id,
                    'variant_combination_id' => $this->variant_combination_id ?: null,
                    'variant_option_ids' => $variantOptionIds,
                    'quantity' => $this->quantity,
                ]);
                session()->flash('message', 'Bulk order updated successfully.');
            } else {
                BulkOrder::create([
                    'user_id' => $this->user_id,
                    'seller_id' => $this->seller_id,
                    'product_id' => $this->product_id,
                    'variant_combination_id' => $this->variant_combination_id ?: null,
                    'variant_option_ids' => $variantOptionIds,
                    'quantity' => $this->quantity,
                ]);
                session()->flash('message', 'Bulk order created successfully.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving bulk order: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $bulkOrder = BulkOrder::findOrFail($id);
        
        $this->editMode = true;
        $this->bulkOrderId = $id;
        $this->user_id = $bulkOrder->user_id ?? '';
        $this->seller_id = $bulkOrder->seller_id ?? '';
        $this->product_id = $bulkOrder->product_id ?? '';
        $this->variant_option_ids = $bulkOrder->variant_option_ids ?? [];
        $this->quantity = $bulkOrder->quantity ?? 1;
        
        $this->showModal = true;
    }

    public function openViewModal($id)
    {
        $this->viewBulkOrder = BulkOrder::with(['user', 'seller', 'product'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewBulkOrder = null;
    }

    public function confirmDelete($id)
    {
        $this->bulkOrderToDelete = BulkOrder::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteBulkOrder()
    {
        if ($this->bulkOrderToDelete) {
            $this->bulkOrderToDelete->delete();
            session()->flash('message', 'Bulk order deleted successfully.');
            $this->showDeleteModal = false;
            $this->bulkOrderToDelete = null;
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->bulkOrderToDelete = null;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7x2 mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bulk Orders</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage bulk orders for your products</p>
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
                                placeholder="Search customers..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Add Button -->
                    <button 
                        wire:click="openModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Bulk Order
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

        <!-- Bulk Orders Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($bulkOrders as $bulkOrder)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="openViewModal({{ $bulkOrder->id }})"
                                            class="text-green-600 hover:text-green-900"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $bulkOrder->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="confirmDelete({{ $bulkOrder->id }})"
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
                                    #{{ $bulkOrder->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $bulkOrder->user->name ?? 'N/A' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $bulkOrder->user->email ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $bulkOrder->product->name ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        @php
                                            $variantText = $bulkOrder->getVariantDisplayText();
                                        @endphp
                                        @if($variantText)
                                            {{ $variantText }}
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">No variant</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $bulkOrder->quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $bulkOrder->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No bulk orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($bulkOrders->hasPages())
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $bulkOrders->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Bulk Order' : 'Add New Bulk Order' }}
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
                                <option value="">Select Customer</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Product -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                            <select 
                                wire:model="product_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Select Product</option>
                                @foreach($sellerProducts as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} - ₹{{ $product->regular_user_price }}</option>
                                @endforeach
                            </select>
                            @error('product_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Quantity -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                            <input 
                                type="number" 
                                wire:model="quantity"
                                min="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter quantity"
                            >
                            @error('quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                            <textarea 
                                wire:model="notes"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter any notes..."
                            ></textarea>
                            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove>{{ $editMode ? 'Update' : 'Create' }}</span>
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
                </div>
            </div>
        </div>
    @endif

    <!-- View Modal -->
    @if($showViewModal && $viewBulkOrder)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            Bulk Order Details #{{ $viewBulkOrder->id }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Bulk Order Information -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Order Information</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Order ID:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">#{{ $viewBulkOrder->id }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->quantity }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Created:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Updated:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->updated_at->format('M d, Y g:i A') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Information -->
                        @if($viewBulkOrder->product)
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Product Information</h3>
                                <div class="text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Product Name:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->product->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Price:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">₹{{ $viewBulkOrder->product->regular_user_price }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                        <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $viewBulkOrder->product->status }}</span>
                                    </div>
                                    @if($viewBulkOrder->product->description)
                                    <div class="pt-2">
                                        <span class="text-gray-600 dark:text-gray-400">Description:</span>
                                        <p class="mt-1 text-gray-900 dark:text-white">{{ $viewBulkOrder->product->description }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Variant Information -->
                        @php
                            $viewVariantText = $viewBulkOrder->getVariantDisplayText();
                        @endphp
                        @if($viewVariantText)
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Variant Information</h3>
                                <div class="text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Selected Variant:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewVariantText }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Customer Information -->
                        @if($viewBulkOrder->user)
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Customer Information</h3>
                                <div class="text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Name:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->user->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Email:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->user->email }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Role:</span>
                                        <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $viewBulkOrder->user->role }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Seller Information -->
                        @if($viewBulkOrder->seller)
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Seller Information</h3>
                                <div class="text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Company:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->seller->company_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Brand:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->seller->brand }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Contact:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $viewBulkOrder->seller->contact_no }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
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

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $bulkOrderToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            Delete Bulk Order
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Are you sure you want to delete bulk order #{{ $bulkOrderToDelete->id }}? This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            wire:click="deleteBulkOrder"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium"
                        >
                            Delete
                        </button>
                        <button 
                            wire:click="closeDeleteModal"
                            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
