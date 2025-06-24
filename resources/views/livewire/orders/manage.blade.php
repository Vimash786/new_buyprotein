<?php

use App\Models\orders;
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
    public $editMode = false;
    public $orderId = null;
    
    // Form fields
    public $product_id = '';
    public $user_id = '';
    public $quantity = '';
    public $unit_price = '';
    public $total_amount = '';
    public $status = 'pending';
    public $notes = '';

    protected $rules = [
        'product_id' => 'required|exists:products,id',
        'user_id' => 'required|exists:users,id',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'total_amount' => 'required|numeric|min:0',
        'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
        'notes' => 'nullable|string',
    ];

    public function updatedQuantity()
    {
        $this->calculateTotal();
    }

    public function updatedUnitPrice()
    {
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        if ($this->quantity && $this->unit_price) {
            $this->total_amount = $this->quantity * $this->unit_price;
        }
    }

    public function with()
    {
        $query = orders::with(['product', 'user', 'product.seller']);

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('product', function($product) {
                    $product->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('user', function($user) {
                    $user->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('email', 'like', '%' . $this->search . '%');
                })->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $totalOrders = orders::count();
        $pendingOrders = orders::where('status', 'pending')->count();
        $confirmedOrders = orders::where('status', 'confirmed')->count();
        $shippedOrders = orders::where('status', 'shipped')->count();
        $deliveredOrders = orders::where('status', 'delivered')->count();
        $cancelledOrders = orders::where('status', 'cancelled')->count();
        $totalRevenue = orders::whereIn('status', ['confirmed', 'shipped', 'delivered'])->sum('total_amount');

        return [
            'orders' => $query->latest()->paginate(10),
            'products' => products::where('status', 'active')->get(),
            'users' => User::all(),
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'confirmedOrders' => $confirmedOrders,
            'shippedOrders' => $shippedOrders,
            'deliveredOrders' => $deliveredOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalRevenue' => $totalRevenue,
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
        $this->orderId = null;
        $this->product_id = '';
        $this->user_id = '';
        $this->quantity = '';
        $this->unit_price = '';
        $this->total_amount = '';
        $this->status = 'pending';
        $this->notes = '';
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'notes' => $this->notes,
        ];

        if ($this->editMode) {
            orders::findOrFail($this->orderId)->update($data);
            session()->flash('message', 'Order updated successfully!');
        } else {
            orders::create($data);
            session()->flash('message', 'Order created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $order = orders::findOrFail($id);
        
        $this->orderId = $order->id;
        $this->product_id = $order->product_id;
        $this->user_id = $order->user_id;
        $this->quantity = $order->quantity;
        $this->unit_price = $order->unit_price;
        $this->total_amount = $order->total_amount;
        $this->status = $order->status;
        $this->notes = $order->notes;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($id)
    {
        orders::findOrFail($id)->delete();
        session()->flash('message', 'Order deleted successfully!');
    }

    public function updateStatus($id, $newStatus)
    {
        $order = orders::findOrFail($id);
        $order->update(['status' => $newStatus]);
        
        session()->flash('message', 'Order status updated successfully!');
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

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Orders Management</h1>
            <p class="mt-2 text-sm text-gray-600">Track and manage customer orders and fulfillment</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Total Orders</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $totalOrders }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Pending</h3>
                    <p class="text-2xl font-bold text-yellow-600">{{ $pendingOrders }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Confirmed</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $confirmedOrders }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Shipped</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $shippedOrders }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Delivered</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $deliveredOrders }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-900">Revenue</h3>
                    <p class="text-xl font-bold text-green-600">${{ number_format($totalRevenue, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters and Add Button -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="search"
                                placeholder="Search orders..."
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
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
                        Add Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ $order->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $order->user->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $order->user->email ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $order->product->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $order->product->seller->company_name ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $order->quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="relative inline-block">
                                        <select 
                                            wire:change="updateStatus({{ $order->id }}, $event.target.value)"
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
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
                                            wire:click="delete({{ $order->id }})"
                                            wire:confirm="Are you sure you want to delete this order?"
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
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ $editMode ? 'Edit Order' : 'Add New Order' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Product -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                                <select 
                                    wire:model="product_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">Select a product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} - ${{ $product->price }}</option>
                                    @endforeach
                                </select>
                                @error('product_id') <span class="text-red-500 text-sm">{{ $errors->first('product_id') }}</span> @enderror
                            </div>

                            <!-- Customer -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                                <select 
                                    wire:model="user_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                <input 
                                    type="number" 
                                    wire:model.live="quantity"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="1"
                                    min="1"
                                >
                                @error('quantity') <span class="text-red-500 text-sm">{{ $errors->first('quantity') }}</span> @enderror
                            </div>

                            <!-- Unit Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Price ($)</label>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:model.live="unit_price"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="0.00"
                                >
                                @error('unit_price') <span class="text-red-500 text-sm">{{ $errors->first('unit_price') }}</span> @enderror
                            </div>

                            <!-- Total Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount ($)</label>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:model="total_amount"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                                    placeholder="0.00"
                                    readonly
                                >
                                @error('total_amount') <span class="text-red-500 text-sm">{{ $errors->first('total_amount') }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select 
                                    wire:model="status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea 
                                    wire:model="notes"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
</div>
