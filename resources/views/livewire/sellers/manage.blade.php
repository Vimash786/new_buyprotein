<?php

use App\Models\Sellers;
use App\Models\Category;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $statusFilter = '';
    public $showModal = false;
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $showStatusModal = false;
    public $editMode = false;
    public $sellerId = null;
    public $viewingSeller = null;
    public $sellerToDelete = null;
    public $sellerToToggle = null;
    
    // Form fields
    public $company_name = '';
    public $gst_number = '';
    public $product_category = [];
    public $contact_person = '';
    public $commission = '';
    public $brand = '';
    public $brand_logo = '';
    public $brand_logo_file = null;
    public $brand_certificate = '';
    public $brand_certificate_file = null;
    public $status = 'not_approved';

    protected $rules = [
        'company_name' => 'required|string|max:255',
        'gst_number' => 'required|string|max:255|unique:sellers,gst_number',
        'product_category' => 'required|array|min:1',
        'product_category.*' => 'string',
        'contact_person' => 'required|string|max:255',
        'commission' => 'required|string|max:255',
        'brand' => 'required|string|max:255',
        'brand_logo_file' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
        'brand_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif|max:2048',
        'status' => 'required|in:approved,not_approved',
    ];

    public function with()
    {
        $query = Sellers::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('gst_number', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $this->search . '%')
                  ->orWhere('brand', 'like', '%' . $this->search . '%')
                  ->orWhere('product_category', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'sellers' => $query->latest()->paginate(10),
            'categories' => Category::all(),
            'totalSellers' => Sellers::count(),
            'approvedSellers' => Sellers::where('status', 'approved')->count(),
            'pendingSellers' => Sellers::where('status', 'not_approved')->count(),
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
        $seller = Sellers::with(['products', 'orders'])->findOrFail($id);
        $this->viewingSeller = $seller;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingSeller = null;
    }

    public function confirmDelete($id)
    {
        $this->sellerToDelete = Sellers::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->sellerToDelete = null;
    }

    public function confirmStatusToggle($id)
    {
        $this->sellerToToggle = Sellers::findOrFail($id);
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->sellerToToggle = null;
    }    
    
    public function resetForm()
    {
        $this->sellerId = null;
        $this->company_name = '';
        $this->gst_number = '';
        $this->product_category = [];
        $this->contact_person = '';
        $this->commission = '';
        $this->brand = '';
        $this->brand_logo = '';
        $this->brand_logo_file = null;
        $this->brand_certificate = '';
        $this->brand_certificate_file = null;
        $this->status = 'not_approved';
        $this->resetValidation();
    }    
    
    public function save()
    {
        $rules = $this->rules;
        
        if ($this->editMode) {
            $rules['gst_number'] = 'required|string|max:255|unique:sellers,gst_number,' . $this->sellerId;
        }

        $this->validate($rules);

        $data = [
            'user_id' => auth()->id(),
            'company_name' => $this->company_name,
            'gst_number' => $this->gst_number,
            'product_category' => implode(',', array_filter($this->product_category)), // Convert array to comma-separated string
            'contact_person' => $this->contact_person,
            'commission' => $this->commission,
            'brand' => $this->brand,
            'status' => $this->status,
        ];

        // Handle brand logo upload
        if ($this->brand_logo_file) {
            $fileName = time() . '_brand_logo_' . $this->brand_logo_file->getClientOriginalName();
            $filePath = $this->brand_logo_file->storeAs('brand_logos', $fileName, 'public');
            $data['brand_logo'] = $filePath;
        } elseif (!$this->editMode) {
            $data['brand_logo'] = null;
        }

        // Handle brand certificate upload
        if ($this->brand_certificate_file) {
            $fileName = time() . '_brand_certificate_' . $this->brand_certificate_file->getClientOriginalName();
            $filePath = $this->brand_certificate_file->storeAs('brand_certificates', $fileName, 'public');
            $data['brand_certificate'] = $filePath;
        } elseif (!$this->editMode) {
            $data['brand_certificate'] = null;
        }
        
        if ($this->editMode) {
            Sellers::findOrFail($this->sellerId)->update($data);
            session()->flash('message', 'Seller updated successfully!');
        } else {
            Sellers::create($data);
            session()->flash('message', 'Seller created successfully!');
        }

        $this->closeModal();
    }   
    
    public function edit($id)
    {
        $seller = Sellers::findOrFail($id);
        
        $this->sellerId = $seller->id;
        $this->company_name = $seller->company_name;
        $this->gst_number = $seller->gst_number;
        $this->product_category = $seller->product_category ? array_filter(array_map('trim', preg_split('/,\s*/', $seller->product_category))) : [];
        $this->contact_person = $seller->contact_person;
        $this->commission = $seller->commission;
        $this->brand = $seller->brand;
        $this->brand_logo = $seller->brand_logo;
        $this->brand_logo_file = null; // Reset file input for edit
        $this->brand_certificate = $seller->brand_certificate;
        $this->brand_certificate_file = null; // Reset file input for edit
        $this->status = $seller->status;
        
        $this->editMode = true;
        $this->showModal = true;
    }
    public function delete($id = null)
    {
        $seller = $this->sellerToDelete ?? Sellers::findOrFail($id);
        $seller->delete();
        session()->flash('message', 'Seller deleted successfully!');
        
        $this->closeDeleteModal();
    }

    public function toggleStatus($id = null)
    {
        $seller = $this->sellerToToggle ?? Sellers::findOrFail($id);
        $seller->update([
            'status' => $seller->status === 'approved' ? 'not_approved' : 'approved'
        ]);
        
        session()->flash('message', 'Seller status updated successfully!');
        
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

    public function removeCategory($category)
    {
        $this->product_category = array_values(array_filter($this->product_category, function($cat) use ($category) {
            return $cat !== $category;
        }));
    }

    public function getSellerStats($seller)
    {
        $totalProducts = $seller->products->count();
        $totalProductsSold = $seller->orders->sum('quantity');
        $totalRevenue = $seller->orders->sum('total_amount');
        $commission = floatval($seller->commission);
        $sellerRevenue = $totalRevenue * ($commission / 100);

        return [
            'totalProducts' => $totalProducts,
            'totalProductsSold' => $totalProductsSold,
            'totalRevenue' => $totalRevenue,
            'sellerRevenue' => $sellerRevenue,
            'commission' => $commission
        ];
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sellers Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage seller accounts, approvals, and information</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" hidden>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Sellers</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalSellers }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Approved</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $approvedSellers }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pending</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $pendingSellers }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                                placeholder="Search sellers..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Status</option>
                            <option value="approved">Approved</option>
                            <option value="not_approved">Not Approved</option>
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
                        Add Seller
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

        <!-- Sellers Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Seller Id</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Brand</th>

                            <!--<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">GST Number</th>-->
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contact No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kyc Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($sellers as $seller)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 dark:hover:bg-zinc-800">  
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="openViewModal({{ $seller->id }})"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $seller->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="confirmDelete({{ $seller->id }})"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>     
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <button 
                                            wire:click="openViewModal({{ $seller->id }})"
                                            class=" text-gray-900 dark:text-white"
                                            title="View Details"
                                        >
                                            #S{{ $seller->id}}
                                    </button>
                                </td>                         
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">{{ $seller->company_name }}</div>
                                        @if($seller->brand_certificate)
                                            <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                                <a href="{{ Storage::url($seller->brand_certificate) }}" 
                                                   target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 00-2.828-2.828z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m0 0l4-4M9 12l4-4" />
                                                    </svg>
                                                    Certificate
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($seller->brand_logo)
                                            <img src="{{ Storage::url($seller->brand_logo) }}" alt="Brand Logo" class="w-8 h-8 rounded object-cover">
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $seller->brand ?? 'Not specified' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!--<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $seller->gst_number }}
                                </td>-->
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    @if($seller->product_category)
                                        <div class="flex flex-wrap gap-1 max-w-xs">
                                            @php
                                                $category = array_filter(array_map('trim', explode(',', $seller->product_category)));
                                            @endphp
                                            @foreach($category as $p_category)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 whitespace-nowrap">
                                                    {{ $p_category }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 dark:text-gray-400">No categories</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $seller->contact_person }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="confirmStatusToggle({{ $seller->id }})"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                               {{ $seller->status === 'approved' 
                                                  ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/70' 
                                                  : 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/70' }}"
                                    >
                                        @if($seller->status === 'approved')
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Approved
                                        @else
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Pending
                                        @endif
                                    </button>
                                </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $seller->created_at->format('M d, Y')}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No sellers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                {{ $sellers->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black-shadow bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Seller' : 'Add New Seller' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 dark:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <!-- Company Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name</label>
                            <input 
                                type="text" 
                                wire:model="company_name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter company name"
                            >
                            @error('company_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- GST Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST Number</label>
                            <input 
                                type="text" 
                                wire:model="gst_number"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter GST number"
                            >
                            @error('gst_number') <span class="text-red-500 text-sm">{{ $errors->first('gst_number') }}</span> @enderror
                        </div>
                        <!-- Commission assign -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assign Commission</label>
                            <input 
                                type="text" 
                                wire:model="commission"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter Commission number" 
                            >
                            @error('commission') <span class="text-red-500 text-sm">{{ $errors->first('commission') }}</span> @enderror
                        </div>
                        <!-- Contact Person -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Person</label>
                            <input 
                                type="text" 
                                wire:model="contact_person"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter contact person name"
                            >
                            @error('contact_person') <span class="text-red-500 text-sm">{{ $errors->first('contact_person') }}</span> @enderror
                        </div>   

                        <!-- Brand Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Brand Name</label>
                            <input 
                                type="text" 
                                wire:model="brand"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter brand name"
                            >
                            @error('brand') <span class="text-red-500 text-sm">{{ $errors->first('brand') }}</span> @enderror
                        </div>

                        <!-- Brand Logo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Brand Logo</label>
                            
                            @if($editMode && $brand_logo)
                                <div class="mb-3 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ Storage::url($brand_logo) }}" alt="Brand Logo" class="w-10 h-10 rounded object-cover">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Current brand logo</span>
                                        </div>
                                        <a href="{{ Storage::url($brand_logo) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm">
                                            View Logo
                                        </a>
                                    </div>
                                </div>
                            @endif
                            
                            <input 
                                type="file" 
                                wire:model="brand_logo_file"
                                accept=".jpg,.jpeg,.png,.gif"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <p class="text-xs text-gray-500 mt-1">Upload JPG, JPEG, PNG, or GIF files (max 2MB)</p>
                            
                            @if($brand_logo_file)
                                <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                                    Logo selected: {{ $brand_logo_file->getClientOriginalName() }}
                                </div>
                            @endif
                            @error('brand_logo_file') 
                                <span class="text-red-500 text-sm">{{ $errors->first('brand_logo_file') }}</span> 
                            @enderror
                        </div>   

                        <!-- Product Category -->
                        <x-multiselect
                            label="Product Categories"
                            wire-model="product_category"
                            :options="$categories"
                            :selected="is_array($product_category) ? $product_category : (empty($product_category) ? [] : array_filter(array_map('trim', preg_split('/,\s*/', $product_category))))"
                            placeholder="Choose product categories..."
                            description="Select one or more categories that best describe your products. You can choose multiple categories to reach a broader audience."
                            remove-method="removeCategory"
                            option-value="id"
                            option-label="name"
                            option-description="description"
                            required
                            :show-description="true"
                        />
                                        
                        <!-- Brand Certificate -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Brand Certificate</label>
                            
                            @if($editMode && $brand_certificate)
                                <div class="mb-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Current file uploaded</span>
                                        </div>
                                        <a href="{{ Storage::url($brand_certificate) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm">
                                            View File
                                        </a>
                                    </div>
                                </div>
                            @endif
                            
                            <input 
                                type="file" 
                                wire:model="brand_certificate_file"
                                accept=".pdf,.jpg,.jpeg,.png,.gif"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <p class="text-xs text-gray-500 mt-1">Upload PDF, JPG, JPEG, PNG, or GIF files (max 2MB)</p>
                            
                            @if($brand_certificate_file)
                                <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                                    File selected: {{ $brand_certificate_file->getClientOriginalName() }}
                                </div>
                            @endif
                              @error('brand_certificate_file') 
                                <span class="text-red-500 text-sm">{{ $errors->first('brand_certificate_file') }}</span> 
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select 
                                wire:model="status"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="not_approved">Not Approved</option>
                                <option value="approved">Approved</option>
                            </select>
                            @error('status') <span class="text-red-500 text-sm">{{ $errors->first('status') }}</span> @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                {{ $editMode ? 'Update Seller' : 'Create Seller' }}
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

    <!-- View Seller Modal -->
    @if($showViewModal && $viewingSeller)
        @php
            $stats = $this->getSellerStats($viewingSeller);
        @endphp
        <div class="fixed inset-0 bg-black-shadow bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Seller Details - {{ $viewingSeller->company_name }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 dark:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Seller Info Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Company Name</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingSeller->company_name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">GST Number</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingSeller->gst_number }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Contact Person</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingSeller->contact_person }}</p>
                                </div>
                                @if($viewingSeller->brand)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Brand Name</label>
                                        <p class="text-gray-900 dark:text-white">{{ $viewingSeller->brand }}</p>
                                    </div>
                                @endif
                                @if($viewingSeller->brand_logo)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Brand Logo</label>
                                        <div class="mt-2">
                                            <img src="{{ Storage::url($viewingSeller->brand_logo) }}" alt="Brand Logo" class="w-16 h-16 rounded object-cover border border-gray-300 dark:border-gray-600">
                                        </div>
                                    </div>
                                @endif
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Commission Rate</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingSeller->commission }}%</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</label>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                           {{ $viewingSeller->status === 'approved' 
                                              ? 'bg-green-100 text-green-800' 
                                              : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $viewingSeller->status === 'approved' ? 'Approved' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Stats -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Statistics</h3>
                            <div class="space-y-4">
                                <div class="bg-white dark:bg-zinc-700 rounded p-3 border dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Products</span>
                                        <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['totalProducts'] }}</span>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-zinc-700 rounded p-3 border dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Order</span>
                                        <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ $stats['totalProductsSold'] }}</span>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-zinc-700 rounded p-3 border dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Sales</span>
                                        <span class="text-xl font-bold text-purple-600 dark:text-purple-400">₹{{ number_format($stats['totalRevenue'], 2) }}</span>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-zinc-700 rounded p-3 border dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Pending Payout</span>
                                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">₹{{ number_format($stats['sellerRevenue'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Categories -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Product Categories</h3>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            @if($viewingSeller->product_category)
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $categories = array_filter(array_map('trim', explode(',', $viewingSeller->product_category)));
                                    @endphp
                                    @foreach($categories as $category)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $category }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">No categories assigned</p>
                            @endif
                        </div>
                    </div>

                    <!-- Brand Certificate -->                        @if($viewingSeller->brand_certificate)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Brand Certificate</h3>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Certificate Document</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Uploaded brand certificate</p>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($viewingSeller->brand_certificate) }}" 
                                       target="_blank" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        View Certificate
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Recent Products -->                        @if($viewingSeller->products->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Recent Products</h3>
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-100 dark:bg-zinc-700">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Product Name</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Price</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                            @foreach($viewingSeller->products->take(5) as $product)
                                                <tr class="bg-white dark:bg-zinc-700">
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $product->name }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">₹{{ number_format($product->price, 2) }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $product->stock_quantity }}</td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                               {{ $product->status === 'active' 
                                                                  ? 'bg-green-100 text-green-800' 
                                                                  : 'bg-red-100 text-red-800' }}">
                                                            {{ ucfirst($product->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($viewingSeller->products->count() > 5)
                                    <div class="px-4 py-3 bg-gray-100 dark:bg-zinc-700 text-center">
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Showing 5 of {{ $viewingSeller->products->count() }} products
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Close Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            wire:click="closeViewModal"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $sellerToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">Delete Seller</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        Are you sure you want to delete "<strong>{{ $sellerToDelete->company_name }}</strong>"? This action cannot be undone and will remove all associated data.
                    </p>
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="closeDeleteModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="delete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            Delete Seller
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status Change Confirmation Modal -->
    @if($showStatusModal && $sellerToToggle)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto 
                                {{ $sellerToToggle->status === 'approved' ? 'bg-yellow-100 dark:bg-yellow-900/50' : 'bg-green-100 dark:bg-green-900/50' }} 
                                rounded-full mb-4">
                        @if($sellerToToggle->status === 'approved')
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        {{ $sellerToToggle->status === 'approved' ? 'Change to Pending' : 'Approve Seller' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        Are you sure you want to {{ $sellerToToggle->status === 'approved' ? 'change status to pending for' : 'approve' }} 
                        "<strong>{{ $sellerToToggle->company_name }}</strong>"?
                        @if($sellerToToggle->status !== 'approved')
                            <br><span class="text-sm text-gray-500">This will grant them access to sell products.</span>
                        @endif
                    </p>
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="closeStatusModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="toggleStatus"
                            class="px-4 py-2 text-sm font-medium text-white 
                                   {{ $sellerToToggle->status === 'approved' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} 
                                   rounded-lg"
                        >
                            {{ $sellerToToggle->status === 'approved' ? 'Change to Pending' : 'Approve Seller' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
