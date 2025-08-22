<?php

use App\Models\User;
use App\Models\Sellers;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $roleFilter = '';
    public $showModal = false;
    public $editMode = false;
    public $userId = null;
    public $showDocumentModal = false;
    public $selectedUser = null;
    
    // User form fields
    public $name = '';
    public $email = '';
    public $role = '';
    public $document_proof = null;
    public $social_link = '';
    public $business_images = [];
    
    // Seller form fields
    public $company_name = '';
    public $gst_number = '';
    public $product_category = '';
    public $contact_no = '';
    public $commission = '10';
    public $brand_certificate = '';
    public $brand_certificate_file = null;
    public $status = 'not_approved';

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'role' => 'required|string|in:User,Gym Owner/Trainer/Influencer/Dietitian,Shop Owner,Seller',
        'document_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'social_link' => 'nullable|string|url|max:255',
        'business_images.*' => 'nullable|file|image|max:10240',
        // Seller fields
        'company_name' => 'nullable|string|max:255',
        'gst_number' => 'nullable|string|max:255',
        'product_category' => 'nullable|string|max:255',
        'contact_no' => 'nullable|string|max:255',
        'brand_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif|max:2048',
        'status' => 'nullable|in:approved,not_approved',
    ];

    public function with()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('role', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        return [
            'users' => $query->latest()->paginate(10),
            'totalUsers' => User::count(),
            'gymUsers' => User::where('role', 'Gym Owner/Trainer/Influencer/Dietitian')->count(),
            'shopUsers' => User::where('role', 'Shop Owner')->count(),
            'sellers' => User::where('role', 'Seller')->count(),
            'regularUsers' => User::where('role', 'User')->count(),
        ];
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->editMode = false;
        $this->resetForm();
        
        // Set default commission from global commission (only for new sellers)
        $this->commission = \App\Models\GlobalCommission::getActiveCommissionRate();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }    
    
    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->role = '';
        $this->document_proof = null;
        $this->social_link = '';
        $this->business_images = [];
        $this->company_name = '';
        $this->gst_number = '';
        $this->product_category = '';
        $this->contact_no = '';
        $this->brand_certificate = '';
        $this->brand_certificate_file = null;
        $this->status = 'not_approved';
        $this->resetValidation();
    }    
    
    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:User,Gym Owner/Trainer/Influencer/Dietitian,Shop Owner,Seller',
        ];
        
        // Add conditional validation based on role
        if ($this->role === 'Gym Owner/Trainer/Influencer/Dietitian') {
            $rules['document_proof'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
            $rules['social_link'] = ['required', 'string', 'url', 'max:255'];
            $rules['business_images'] = ['required', 'array', 'min:1'];
            $rules['business_images.*'] = ['file', 'image', 'max:10240'];
        } elseif ($this->role === 'Shop Owner') {
            $rules['business_images'] = ['required', 'array', 'min:1'];
            $rules['business_images.*'] = ['file', 'image', 'max:10240'];
        } elseif ($this->role === 'Seller') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['gst_number'] = 'required|string|max:255';
            $rules['product_category'] = 'required|string|max:255';
            $rules['contact_no'] = 'required|string|max:255';
            $rules['brand_certificate_file'] = 'required|file|mimes:pdf,jpg,jpeg,png,gif|max:2048';
            $rules['status'] = 'required|in:approved,not_approved';
            
            if ($this->editMode) {
                $rules['gst_number'] = 'required|string|max:255|unique:sellers,gst_number,' . $this->userId;
            } else {
                $rules['gst_number'] = 'required|string|max:255|unique:sellers,gst_number';
            }
        }

        $this->validate($rules);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'profile_completed' => true,
        ];

        // Handle file uploads based on role
        if ($this->role === 'Gym Owner/Trainer/Influencer/Dietitian') {
            if ($this->document_proof) {
                $userData['document_proof'] = $this->document_proof->store('user_documents', 'public');
            }
            if ($this->social_link) {
                $userData['social_media_link'] = $this->social_link;
            }
            if ($this->business_images && is_array($this->business_images)) {
                $businessImagePaths = [];
                foreach ($this->business_images as $image) {
                    $businessImagePaths[] = $image->store('business_images', 'public');
                }
                $userData['business_images'] = json_encode($businessImagePaths);
            }
        } elseif ($this->role === 'Shop Owner') {
            if ($this->business_images && is_array($this->business_images)) {
                $businessImagePaths = [];
                foreach ($this->business_images as $image) {
                    $businessImagePaths[] = $image->store('business_images', 'public');
                }
                $userData['business_images'] = json_encode($businessImagePaths);
            }
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->userId);
            $user->update($userData);
            
            // Handle seller data if role is Seller
            if ($this->role === 'Seller') {
                $sellerData = [
                    'company_name' => $this->company_name,
                    'gst_number' => $this->gst_number,
                    'product_category' => $this->product_category,
                    'contact_no' => $this->contact_no,
                    'commission' => $this->commission,
                    'status' => $this->status,
                ];
                
                if ($this->brand_certificate_file) {
                    $fileName = time() . '_' . $this->brand_certificate_file->getClientOriginalName();
                    $filePath = $this->brand_certificate_file->storeAs('brand_certificates', $fileName, 'public');
                    $sellerData['brand_certificate'] = $filePath;
                }
                
                $seller = Sellers::where('user_id', $user->id)->first();
                if ($seller) {
                    $seller->update($sellerData);
                } else {
                    $sellerData['user_id'] = $user->id;
                    Sellers::create($sellerData);
                }
            }
            
            session()->flash('message', 'User updated successfully!');
        } else {
            $user = User::create($userData);
            
            // Create seller record if role is Seller
            if ($this->role === 'Seller') {
                $sellerData = [
                    'user_id' => $user->id,
                    'company_name' => $this->company_name,
                    'gst_number' => $this->gst_number,
                    'product_category' => $this->product_category,
                    'contact_no' => $this->contact_no,
                    'commission' => $this->commission,
                    'status' => $this->status,
                ];
                
                if ($this->brand_certificate_file) {
                    $fileName = time() . '_' . $this->brand_certificate_file->getClientOriginalName();
                    $filePath = $this->brand_certificate_file->storeAs('brand_certificates', $fileName, 'public');
                    $sellerData['brand_certificate'] = $filePath;
                }
                
                Sellers::create($sellerData);
            }
            
            session()->flash('message', 'User created successfully!');
        }

        $this->closeModal();
    }   
    
    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->document_proof = $user->document_proof;
        $this->social_link = $user->social_media_link;
        $this->business_images = $user->business_images ? json_decode($user->business_images, true) : [];
        
        // Load seller data if user is a seller
        if ($user->role === 'Seller') {
            $seller = Sellers::where('user_id', $user->id)->first();
            if ($seller) {
                $this->company_name = $seller->company_name;
                $this->gst_number = $seller->gst_number;
                $this->product_category = $seller->product_category;
                $this->contact_no = $seller->contact_no;
                $this->brand_certificate = $seller->brand_certificate;
                $this->status = $seller->status;
            }
        }
        
        $this->brand_certificate_file = null; // Reset file input for edit
        
        $this->editMode = true;
        $this->showModal = true;
    }
    
    public function delete($id)
    {
        $user = User::findOrFail($id);
        
        // Delete seller record if exists
        if ($user->role === 'Seller') {
            Sellers::where('user_id', $user->id)->delete();
        }
        
        $user->delete();
        session()->flash('message', 'User deleted successfully!');
    }
    
    public function viewDocuments($userId)
    {
        $this->selectedUser = User::with('seller')->findOrFail($userId);
        $this->showDocumentModal = true;
    }

    public function closeDocumentModal()
    {
        $this->showDocumentModal = false;
        $this->selectedUser = null;
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->role === 'Seller') {
            $seller = Sellers::where('user_id', $user->id)->first();
            if ($seller) {
                $seller->update([
                    'status' => $seller->status === 'approved' ? 'not_approved' : 'approved'
                ]);
            }
        } else {
            // For non-seller users, toggle profile_completed status
            $user->update([
                'profile_completed' => !$user->profile_completed
            ]);
        }
        
        session()->flash('message', 'User status updated successfully!');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7x2 mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Users Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage user accounts, roles, and profile information</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" hidden>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Users</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalUsers }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gym Users</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $gymUsers }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Shop Owners</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $shopUsers }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sellers</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $sellers }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
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
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Role Filter -->
                        <select wire:model.live="roleFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Roles</option>
                            <option value="User">Regular User</option>
                            <option value="Gym Owner/Trainer/Influencer/Dietitian">Gym Owner/Trainer/Influencer/Dietitian</option>
                            <option value="Shop Owner">Shop Owner</option>
                            <option value="Seller">Seller</option>
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
                        Add User
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

        <!-- Users Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documents</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $user->email }}</div>
                                        @if($user->role === 'Seller')
                                            @php $seller = \App\Models\Sellers::where('user_id', $user->id)->first(); @endphp
                                            @if($seller)
                                                <div class="text-xs text-gray-400">{{ $seller->company_name }}</div>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           {{ $user->role === 'Seller' ? 'bg-purple-100 text-purple-800' :
                                              ($user->role === 'Gym Owner/Trainer/Influencer/Dietitian' ? 'bg-green-100 text-green-800' :
                                               ($user->role === 'Shop Owner' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <div class="space-y-1">
                                        @if($user->role === 'Gym Owner/Trainer/Influencer/Dietitian')
                                            @if($user->document_proof)
                                                <div>
                                                    <a href="{{ Storage::url($user->document_proof) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        Document Proof
                                                    </a>
                                                </div>
                                            @endif
                                            @if($user->social_media_link)
                                                <div>
                                                    <a href="{{ $user->social_media_link }}" target="_blank" class="text-green-600 hover:text-green-800 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                        </svg>
                                                        Social Link
                                                    </a>
                                                </div>                            @endif
                            @if($user->business_images)
                                @php $images = json_decode($user->business_images, true); @endphp
                                @if($images && count($images) > 0)
                                    <div>
                                        <button 
                                            wire:click="viewDocuments({{ $user->id }})"
                                            class="text-orange-600 hover:text-orange-800 flex items-center gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ count($images) }} Business Images
                                        </button>
                                    </div>
                                @endif
                            @endif                        @elseif($user->role === 'Shop Owner')
                            @if($user->business_images)
                                @php $images = json_decode($user->business_images, true); @endphp
                                @if($images && count($images) > 0)
                                    <div>
                                        <button 
                                            wire:click="viewDocuments({{ $user->id }})"
                                            class="text-orange-600 hover:text-orange-800 flex items-center gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ count($images) }} Business Images
                                        </button>
                                    </div>
                                @endif
                            @endif
                                        @elseif($user->role === 'Seller')
                                            @php $seller = \App\Models\Sellers::where('user_id', $user->id)->first(); @endphp
                                            @if($seller && $seller->brand_certificate)
                                                <div>
                                                    <a href="{{ Storage::url($seller->brand_certificate) }}" target="_blank" class="text-purple-600 hover:text-purple-800 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        Brand Certificate
                                                    </a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">No documents</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->role === 'Seller')
                                        @php $seller = \App\Models\Sellers::where('user_id', $user->id)->first(); @endphp
                                        @if($seller)
                                            <button 
                                                wire:click="toggleStatus({{ $user->id }})"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                                       {{ $seller->status === 'approved' 
                                                          ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                                          : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' }}"
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
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                               {{ $user->profile_completed 
                                                  ? 'bg-green-100 text-green-800' 
                                                  : 'bg-gray-100 text-gray-800' }}">
                                            {{ $user->profile_completed ? 'Completed' : 'Incomplete' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="viewDocuments({{ $user->id }})"
                                            class="text-green-600 hover:text-green-900"
                                            title="View Documents"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $user->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
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
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
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
                            {{ $editMode ? 'Edit User' : 'Add New User' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter full name"
                            >
                            @error('name') <span class="text-red-500 text-sm">{{ $errors->first('name') }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input 
                                type="email" 
                                wire:model="email"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter email address"
                            >
                            @error('email') <span class="text-red-500 text-sm">{{ $errors->first('email') }}</span> @enderror
                        </div>

                        <!-- Role -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
                            <select 
                                wire:model.live="role"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Select Role</option>
                                <option value="User">Regular User</option>
                                <option value="Gym Owner/Trainer/Influencer/Dietitian">Gym Owner/Trainer/Influencer/Dietitian</option>
                                <option value="Shop Owner">Shop Owner</option>
                                <option value="Seller">Seller</option>
                            </select>
                            @error('role') <span class="text-red-500 text-sm">{{ $errors->first('role') }}</span> @enderror
                        </div>

                        <!-- Role-specific fields -->
                        @if($role === 'Gym Owner/Trainer/Influencer/Dietitian')
                            <!-- Document Proof -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Document Proof</label>
                                <input 
                                    type="file" 
                                    wire:model="document_proof"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                <p class="text-xs text-gray-500 mt-1">Upload PDF, JPG, JPEG, or PNG files (max 10MB)</p>
                                @error('document_proof') <span class="text-red-500 text-sm">{{ $errors->first('document_proof') }}</span> @enderror
                            </div>

                            <!-- Social Media Link -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Social Media Link</label>
                                <input 
                                    type="url" 
                                    wire:model="social_link"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="https://example.com/profile"
                                >
                                @error('social_link') <span class="text-red-500 text-sm">{{ $errors->first('social_link') }}</span> @enderror
                            </div>

                            <!-- Business Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Business Images</label>
                                <input 
                                    type="file" 
                                    wire:model="business_images"
                                    accept="image/*"
                                    multiple
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                
                                <!-- Loading indicator for file upload -->
                                <div wire:loading wire:target="business_images" class="flex items-center mt-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-blue-600">Uploading images...</span>
                                </div>
                                
                                <p class="text-xs text-gray-500 mt-1">Upload multiple images (max 10MB each)</p>
                                @error('business_images.*') <span class="text-red-500 text-sm">{{ $errors->first('business_images.*') }}</span> @enderror
                            </div>

                        @elseif($role === 'Shop Owner')
                            <!-- Business Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Business Images</label>
                                <input 
                                    type="file" 
                                    wire:model="business_images"
                                    accept="image/*"
                                    multiple
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                <p class="text-xs text-gray-500 mt-1">Upload multiple images (max 10MB each)</p>
                                @error('business_images.*') <span class="text-red-500 text-sm">{{ $errors->first('business_images.*') }}</span> @enderror
                            </div>

                        @elseif($role === 'Seller')
                            <!-- Company Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name</label>
                                <input 
                                    type="text" 
                                    wire:model="company_name"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="Enter company name"
                                >
                                @error('company_name') <span class="text-red-500 text-sm">{{ $errors->first('company_name') }}</span> @enderror
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

                            <!-- Product Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product Category</label>
                                <select 
                                    wire:model="product_category"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                    <option value="">Select Category</option>
                                    <option value="Health Supplements">Health Supplements</option>
                                    <option value="Fitness Equipment">Fitness Equipment</option>
                                    <option value="Apparel">Apparel</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('product_category') <span class="text-red-500 text-sm">{{ $errors->first('product_category') }}</span> @enderror
                            </div>

                            <!-- Contact Person -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact No</label>
                                <input 
                                    type="text" 
                                    wire:model="contact_no"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="+916789012345"
                                >
                                @error('contact_no') <span class="text-red-500 text-sm">{{ $errors->first('contact_no') }}</span> @enderror
                            </div>

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
                                @error('brand_certificate_file') <span class="text-red-500 text-sm">{{ $errors->first('brand_certificate_file') }}</span> @enderror
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
                        @endif

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                {{ $editMode ? 'Update User' : 'Create User' }}
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

    <!-- Document Viewer Modal -->
    @if($showDocumentModal && $selectedUser)
        <div class="fixed inset-0 bg-black-shadow bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border dark:border-gray-600 w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-zinc-900">
                <div class="mt-3">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Documents for {{ $selectedUser->name }}
                        </h3>
                        <button 
                            wire:click="closeDocumentModal"
                            class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- User Info -->
                    <div class="py-4 border-b dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Email: {{ $selectedUser->email }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Role: {{ $selectedUser->role }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                   {{ $selectedUser->role === 'Seller' ? 'bg-purple-100 text-purple-800' :
                                      ($selectedUser->role === 'Gym Owner/Trainer/Influencer/Dietitian' ? 'bg-green-100 text-green-800' :
                                       ($selectedUser->role === 'Shop Owner' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ $selectedUser->role }}
                            </span>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="py-4 max-h-96 overflow-y-auto">
                        @if($selectedUser->role === 'Gym Owner/Trainer/Influencer/Dietitian')
                            <!-- Document Proof -->
                            @if($selectedUser->document_proof)
                                <div class="mb-4 p-4 border dark:border-gray-600 rounded-lg">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Document Proof</h4>
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ basename($selectedUser->document_proof) }}</p>
                                            <div class="flex gap-2 mt-1">
                                                <a href="{{ Storage::url($selectedUser->document_proof) }}" target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">View</a>
                                                <a href="{{ Storage::url($selectedUser->document_proof) }}" download 
                                                   class="text-green-600 hover:text-green-800 text-xs">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Social Media Link -->
                            @if($selectedUser->social_media_link)
                                <div class="mb-4 p-4 border dark:border-gray-600 rounded-lg">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Social Media Link</h4>
                                    <a href="{{ $selectedUser->social_media_link }}" target="_blank" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        {{ $selectedUser->social_media_link }}
                                    </a>
                                </div>
                            @endif

                            <!-- Business Images -->
                            @if($selectedUser->business_images)
                                @php $images = json_decode($selectedUser->business_images, true); @endphp
                                @if($images && count($images) > 0)
                                    <div class="mb-4 p-4 border dark:border-gray-600 rounded-lg">
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Business Images ({{ count($images) }})</h4>
                                        <div class="grid grid-cols-2 gap-3">
                                            @foreach($images as $index => $imagePath)
                                                <div class="border dark:border-gray-600 rounded-lg p-2">
                                                    <img src="{{ Storage::url($imagePath) }}" alt="Business Image {{ $index + 1 }}" 
                                                         class="w-full h-24 object-cover rounded">
                                                    <div class="flex gap-2 mt-2">
                                                        <a href="{{ Storage::url($imagePath) }}" target="_blank" 
                                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">View</a>
                                                        <a href="{{ Storage::url($imagePath) }}" download 
                                                           class="text-green-600 hover:text-green-800 text-xs">Download</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                        @elseif($selectedUser->role === 'Shop Owner')
                            <!-- Business Images -->
                            @if($selectedUser->business_images)
                                @php $images = json_decode($selectedUser->business_images, true); @endphp
                                @if($images && count($images) > 0)
                                    <div class="mb-4 p-4 border dark:border-gray-600 rounded-lg">
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Business Images ({{ count($images) }})</h4>
                                        <div class="grid grid-cols-2 gap-3">
                                            @foreach($images as $index => $imagePath)
                                                <div class="border dark:border-gray-600 rounded-lg p-2">
                                                    <img src="{{ Storage::url($imagePath) }}" alt="Business Image {{ $index + 1 }}" 
                                                         class="w-full h-24 object-cover rounded">
                                                    <div class="flex gap-2 mt-2">
                                                        <a href="{{ Storage::url($imagePath) }}" target="_blank" 
                                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">View</a>
                                                        <a href="{{ Storage::url($imagePath) }}" download 
                                                           class="text-green-600 hover:text-green-800 text-xs">Download</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif

                        @elseif($selectedUser->role === 'Seller')
                            @php $seller = \App\Models\Sellers::where('user_id', $selectedUser->id)->first(); @endphp
                            @if($seller)
                                <!-- Seller Information -->
                                <div class="mb-4 p-4 border rounded-lg bg-gray-50 dark:bg-zinc-800">
                                    <h4 class="font-medium text-gray-900 mb-3">Seller Information</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-300">Company:</span>
                                            <p class="font-medium">{{ $seller->company_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-300">GST Number:</span>
                                            <p class="font-medium">{{ $seller->gst_number }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-300">Product Category:</span>
                                            <p class="font-medium">{{ $seller->product_category }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-300">Contact No:</span>
                                            <p class="font-medium">{{ $seller->contact_no }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 dark:text-gray-300">Status:</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                   {{ $seller->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($seller->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Brand Certificate -->
                                @if($seller->brand_certificate)
                                    <div class="mb-4 p-4 border dark:border-gray-600 rounded-lg">
                                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Brand Certificate</h4>
                                        <div class="flex items-center gap-3">
                                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ basename($seller->brand_certificate) }}</p>
                                                <div class="flex gap-2 mt-1">
                                                    <a href="{{ Storage::url($seller->brand_certificate) }}" target="_blank" 
                                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">View</a>
                                                    <a href="{{ Storage::url($seller->brand_certificate) }}" download 
                                                       class="text-green-600 hover:text-green-800 text-xs">Download</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif

                        @else
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>No documents available for regular users.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end pt-4 border-t dark:border-gray-600">
                        <button 
                            wire:click="closeDocumentModal"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-700 dark:text-white py-2 px-4 rounded-lg font-medium"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
