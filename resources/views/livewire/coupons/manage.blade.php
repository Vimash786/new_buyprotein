<?php

use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\User;
use App\Models\products;
use App\Models\Sellers;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $showModal = false;
    public $showReportModal = false;
    public $showAssignModal = false;
    public $editMode = false;
    public $couponId = null;
    public $selectedCoupon = null;
    
    // Form fields
    public $code = '';
    public $name = '';
    public $description = '';
    public $type = 'percentage';
    public $value = '';
    public $minimum_amount = '';
    public $maximum_discount = '';
    public $usage_limit = '';
    public $user_usage_limit = '';
    public $starts_at = '';
    public $expires_at = '';
    public $status = 'active';
    public $applicable_to = 'all';
    public $user_types = [];

    // Assignment fields
    public $assignmentType = 'users'; // 'users', 'products', 'sellers'
    public $selectedItems = [];
    public $searchItems = '';

    // Report functionality
    public $reportData = null;
    public $reportDateFrom = '';
    public $reportDateTo = '';

    protected $rules = [
        'code' => 'required|string|max:255|unique:coupons,code',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|in:percentage,fixed',
        'value' => 'required|numeric|min:0',
        'minimum_amount' => 'nullable|numeric|min:0',
        'maximum_discount' => 'nullable|numeric|min:0',
        'usage_limit' => 'nullable|integer|min:1',
        'user_usage_limit' => 'nullable|integer|min:1',
        'starts_at' => 'required|date',
        'expires_at' => 'required|date|after:starts_at',
        'status' => 'required|in:active,inactive',
        'applicable_to' => 'required|in:all,users,products,sellers',
        'user_types' => 'nullable|array',
    ];

    public function mount()
    {
        // Check if user has super role
        if (auth()->user()->role !== 'Super') {
            abort(403, 'Access denied. Super role required.');
        }

        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
    }

    public function with()
    {
        $query = Coupon::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            if ($this->statusFilter === 'expired') {
                $query->where('expires_at', '<', now());
            } elseif ($this->statusFilter === 'upcoming') {
                $query->where('starts_at', '>', now());
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        return [
            'coupons' => $query->latest()->paginate(10),
            'totalCoupons' => Coupon::count(),
            'activeCoupons' => Coupon::where('status', 'active')->count(),
            'expiredCoupons' => Coupon::where('expires_at', '<', now())->count(),
            'upcomingCoupons' => Coupon::where('starts_at', '>', now())->count(),
            'availableUserTypes' => [
                'User' => 'Regular User',
                'Gym Owner/Trainer/Influencer' => 'Gym Owner/Trainer/Influencer',
                'Shop Owner' => 'Shop Owner',
                'Seller' => 'Seller'
            ],
            'reportData' => $this->reportData, // Pass the reportData property
            'users' => User::all(),
            'products' => products::all(),
            'sellers' => Sellers::all(),
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

    public function openReportModal($id)
    {
        $this->selectedCoupon = Coupon::with(['assignments.assignable', 'usages.user', 'usages.order'])->findOrFail($id);
        $this->showReportModal = true;
    }

    public function closeReportModal()
    {
        $this->showReportModal = false;
        $this->selectedCoupon = null;
    }

    public function openAssignModal($id)
    {
        $this->selectedCoupon = Coupon::findOrFail($id);
        $this->showAssignModal = true;
        $this->selectedItems = [];
        $this->searchItems = '';
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedCoupon = null;
        $this->selectedItems = [];
        $this->searchItems = '';
    }

    public function resetForm()
    {
        $this->couponId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->type = 'percentage';
        $this->value = '';
        $this->minimum_amount = '';
        $this->maximum_discount = '';
        $this->usage_limit = '';
        $this->user_usage_limit = '';
        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
        $this->status = 'active';
        $this->applicable_to = 'all';
        $this->user_types = [];
        $this->resetValidation();
    }

    public function generateCode()
    {
        $this->code = 'COUP' . strtoupper(Str::random(6));
    }

    public function save()
    {
        $rules = $this->rules;
        
        if ($this->editMode) {
            $rules['code'] = 'required|string|max:255|unique:coupons,code,' . $this->couponId;
        }

        $this->validate($rules);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'minimum_amount' => $this->minimum_amount ?: null,
            'maximum_discount' => $this->maximum_discount ?: null,
            'usage_limit' => $this->usage_limit ?: null,
            'user_usage_limit' => $this->user_usage_limit ?: null,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status,
            'applicable_to' => $this->applicable_to,
            'user_types' => $this->user_types,
        ];

        if ($this->editMode) {
            $coupon = Coupon::findOrFail($this->couponId);
            $data['updated_by'] = auth()->id();
            $coupon->update($data);
            session()->flash('message', 'Coupon updated successfully!');
        } else {
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            $data['used_count'] = 0;
            Coupon::create($data);
            session()->flash('message', 'Coupon created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->name = $coupon->name;
        $this->description = $coupon->description;
        $this->type = $coupon->type;
        $this->value = $coupon->value;
        $this->minimum_amount = $coupon->minimum_amount;
        $this->maximum_discount = $coupon->maximum_discount;
        $this->usage_limit = $coupon->usage_limit;
        $this->user_usage_limit = $coupon->user_usage_limit;
        $this->starts_at = $coupon->starts_at->format('Y-m-d\TH:i');
        $this->expires_at = $coupon->expires_at->format('Y-m-d\TH:i');
        $this->status = $coupon->status;
        $this->applicable_to = $coupon->applicable_to;
        $this->user_types = $coupon->user_types ?: [];
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // Delete assignments
        $coupon->assignments()->delete();
        
        $coupon->delete();
        session()->flash('message', 'Coupon deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update([
            'status' => $coupon->status === 'active' ? 'inactive' : 'active',
            'updated_by' => auth()->id()
        ]);
        
        session()->flash('message', 'Coupon status updated successfully!');
    }

    public function getAssignableItems()
    {
        if (!$this->selectedCoupon) return [];

        $query = null;
        
        switch ($this->assignmentType) {
            case 'users':
                $query = User::query();
                if ($this->searchItems) {
                    $query->where(function($q) {
                        $q->where('name', 'like', '%' . $this->searchItems . '%')
                          ->orWhere('email', 'like', '%' . $this->searchItems . '%');
                    });
                }
                break;
                
            case 'products':
                $query = products::query();
                if ($this->searchItems) {
                    $query->where('name', 'like', '%' . $this->searchItems . '%');
                }
                break;
                
            case 'sellers':
                $query = Sellers::query();
                if ($this->searchItems) {
                    $query->where('company_name', 'like', '%' . $this->searchItems . '%');
                }
                break;
        }

        return $query ? $query->limit(20)->get() : collect();
    }

    public function assignCoupon()
    {
        if (!$this->selectedCoupon || empty($this->selectedItems)) {
            session()->flash('error', 'Please select items to assign the coupon to.');
            return;
        }

        $assignedCount = 0;
        
        foreach ($this->selectedItems as $itemId) {
            $modelClass = null;
            
            switch ($this->assignmentType) {
                case 'users':
                    $modelClass = User::class;
                    break;
                case 'products':
                    $modelClass = products::class;
                    break;
                case 'sellers':
                    $modelClass = Sellers::class;
                    break;
            }

            if ($modelClass) {
                // Check if assignment already exists
                $existingAssignment = CouponAssignment::where('coupon_id', $this->selectedCoupon->id)
                    ->where('assignable_type', $this->assignmentType)
                    ->where('assignable_id', $itemId)
                    ->first();

                if (!$existingAssignment) {
                    CouponAssignment::create([
                        'coupon_id' => $this->selectedCoupon->id,
                        'assignable_type' => $this->assignmentType,
                        'assignable_id' => $itemId
                    ]);
                    $assignedCount++;
                }
            }
        }

        session()->flash('message', "Coupon assigned to {$assignedCount} {$this->assignmentType} successfully!");
        $this->closeAssignModal();
    }

    public function removeAssignment($assignmentId)
    {
        CouponAssignment::findOrFail($assignmentId)->delete();
        session()->flash('message', 'Assignment removed successfully!');
    }

    public function generateReport()
    {
        $query = Coupon::query()
            ->withCount('assignments')
            ->with(['assignments']);

        if ($this->reportDateFrom && $this->reportDateTo) {
            $query->whereBetween('created_at', [$this->reportDateFrom, $this->reportDateTo]);
        }

        $coupons = $query->get();

        $this->reportData = [
            'coupons' => $coupons,
            'total_coupons' => $coupons->count(),
            'total_assignments' => $coupons->sum('assignments_count'),
            'total_usage' => 0, // Will be updated when CouponUsage is properly implemented
            'total_discount' => 0, // Will be updated when CouponUsage is properly implemented
        ];

        session()->flash('message', 'Report generated successfully!');
    }

    public function exportReport($format)
    {
        session()->flash('message', 'Report export functionality will be implemented.');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Coupons Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage discount coupons and promotional codes (Super Role Access)</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Coupons</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalCoupons }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9a2 2 0 10-4 0v5a2 2 0 01-2 2h6m-6-4h4m8 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $activeCoupons }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Expired</h3>
                        <p class="text-3xl font-bold text-red-600">{{ $expiredCoupons }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $upcomingCoupons }}</p>
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
                                placeholder="Search coupons..."
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
                            <option value="expired">Expired</option>
                            <option value="upcoming">Upcoming</option>
                        </select>

                        <!-- Type Filter -->
                        <select wire:model.live="typeFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Types</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
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
                        Add Coupon
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

        <!-- Continue with the table and modals... -->
        @include('livewire.coupons.partials.table')
    </div>

    <!-- Modals -->
    @if($showModal)
        @include('livewire.coupons.partials.create-edit-modal')
    @endif
    
    @if($showReportModal)
        @include('livewire.coupons.partials.report-modal')
    @endif
    
    @if($showAssignModal)
        @include('livewire.coupons.partials.assign-modal')
    @endif
</div>
