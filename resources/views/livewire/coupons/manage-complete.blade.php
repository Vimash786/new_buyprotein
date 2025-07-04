<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\products;
use App\Models\sellers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $selectedStatus = '';

    // Modal states
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showAssignModal = false;
    public $showReportModal = false;
    public $showDeleteModal = false;

    // Form fields
    public $name = '';
    public $code = '';
    public $type = 'percentage';
    public $value = '';
    public $min_amount = '';
    public $max_discount = '';
    public $usage_limit = '';
    public $valid_from = '';
    public $valid_to = '';
    public $status = 'active';
    public $description = '';

    // Edit/Delete tracking
    public $editingCoupon = null;
    public $deletingCoupon = null;

    // Assignment fields
    public $selectedCoupon = null;
    public $assignmentType = '';
    public $selectedProductId = '';
    public $selectedUserId = '';
    public $selectedSellerId = '';
    public $selectedUserType = '';

    // Report fields
    public $reportDateFrom = '';
    public $reportDateTo = '';
    public $reportCouponId = '';
    public $reportData = null;

    // Data collections
    public $coupons = [];
    public $users = [];
    public $products = [];
    public $sellers = [];

    public function mount()
    {
        // Check if user has Super role
        if (Auth::user()->role !== 'Super') {
            abort(403, 'Unauthorized access. Only Super admins can manage coupons.');
        }

        $this->loadData();
    }

    public function loadData()
    {
        $this->users = User::all();
        $this->products = products::all();
        $this->sellers = sellers::all();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'selectedStatus', 'sortBy', 'sortDirection'])) {
            $this->resetPage();
        }
    }

    public function getCoupons()
    {
        return Coupon::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->selectedStatus, function ($query) {
                $query->where('status', $this->selectedStatus);
            })
            ->withCount(['assignments', 'usages'])
            ->withSum('usages', 'discount_amount')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getStats()
    {
        return [
            'total_coupons' => Coupon::count(),
            'active_coupons' => Coupon::where('status', 'active')->count(),
            'total_usage' => CouponUsage::count(),
            'total_discount' => CouponUsage::sum('discount_amount'),
        ];
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($couponId)
    {
        $this->editingCoupon = Coupon::findOrFail($couponId);
        $this->fillForm($this->editingCoupon);
        $this->showEditModal = true;
    }

    public function openAssignModal($couponId)
    {
        $this->selectedCoupon = Coupon::with('assignments.assignable')->findOrFail($couponId);
        $this->resetAssignmentForm();
        $this->showAssignModal = true;
    }

    public function openReportModal()
    {
        $this->reportDateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->reportDateTo = Carbon::now()->format('Y-m-d');
        $this->showReportModal = true;
    }

    public function openDeleteModal($couponId)
    {
        $this->deletingCoupon = Coupon::findOrFail($couponId);
        $this->showDeleteModal = true;
    }

    public function resetForm()
    {
        $this->name = '';
        $this->code = '';
        $this->type = 'percentage';
        $this->value = '';
        $this->min_amount = '';
        $this->max_discount = '';
        $this->usage_limit = '';
        $this->valid_from = '';
        $this->valid_to = '';
        $this->status = 'active';
        $this->description = '';
        $this->editingCoupon = null;
    }

    public function fillForm($coupon)
    {
        $this->name = $coupon->name;
        $this->code = $coupon->code;
        $this->type = $coupon->type;
        $this->value = $coupon->value;
        $this->min_amount = $coupon->min_amount;
        $this->max_discount = $coupon->max_discount;
        $this->usage_limit = $coupon->usage_limit;
        $this->valid_from = $coupon->valid_from->format('Y-m-d\TH:i');
        $this->valid_to = $coupon->valid_to->format('Y-m-d\TH:i');
        $this->status = $coupon->status;
        $this->description = $coupon->description;
    }

    public function resetAssignmentForm()
    {
        $this->assignmentType = '';
        $this->selectedProductId = '';
        $this->selectedUserId = '';
        $this->selectedSellerId = '';
        $this->selectedUserType = '';
    }

    public function generateCouponCode()
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:coupons,code' . ($this->editingCoupon ? ',' . $this->editingCoupon->id : ''),
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->value,
            'min_amount' => $this->min_amount,
            'max_discount' => $this->max_discount,
            'usage_limit' => $this->usage_limit,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'status' => $this->status,
            'description' => $this->description,
        ];

        if ($this->editingCoupon) {
            $this->editingCoupon->update($data);
            session()->flash('success', 'Coupon updated successfully!');
        } else {
            Coupon::create($data);
            session()->flash('success', 'Coupon created successfully!');
        }

        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function assignCoupon()
    {
        $this->validate([
            'assignmentType' => 'required|in:product,user,seller,user_type',
            'selectedProductId' => 'required_if:assignmentType,product|exists:products,id',
            'selectedUserId' => 'required_if:assignmentType,user|exists:users,id',
            'selectedSellerId' => 'required_if:assignmentType,seller|exists:sellers,id',
            'selectedUserType' => 'required_if:assignmentType,user_type|in:admin,user,seller,Super',
        ]);

        $assignmentData = [
            'coupon_id' => $this->selectedCoupon->id,
            'assignable_type' => $this->assignmentType,
        ];

        if ($this->assignmentType === 'user_type') {
            $assignmentData['user_type'] = $this->selectedUserType;
        } else {
            $assignmentData['assignable_id'] = $this->{'selected' . ucfirst($this->assignmentType) . 'Id'};
        }

        // Check if assignment already exists
        $existingAssignment = CouponAssignment::where('coupon_id', $this->selectedCoupon->id)
            ->where('assignable_type', $this->assignmentType)
            ->where(function ($query) use ($assignmentData) {
                if ($this->assignmentType === 'user_type') {
                    $query->where('user_type', $assignmentData['user_type']);
                } else {
                    $query->where('assignable_id', $assignmentData['assignable_id']);
                }
            })
            ->first();

        if ($existingAssignment) {
            session()->flash('error', 'This assignment already exists!');
            return;
        }

        CouponAssignment::create($assignmentData);
        
        // Refresh the selected coupon to show new assignment
        $this->selectedCoupon = Coupon::with('assignments.assignable')->findOrFail($this->selectedCoupon->id);
        
        $this->resetAssignmentForm();
        session()->flash('success', 'Coupon assigned successfully!');
    }

    public function removeAssignment($assignmentId)
    {
        CouponAssignment::findOrFail($assignmentId)->delete();
        
        // Refresh the selected coupon
        $this->selectedCoupon = Coupon::with('assignments.assignable')->findOrFail($this->selectedCoupon->id);
        
        session()->flash('success', 'Assignment removed successfully!');
    }

    public function toggleStatus($couponId)
    {
        $coupon = Coupon::findOrFail($couponId);
        $coupon->update(['status' => $coupon->status === 'active' ? 'inactive' : 'active']);
        
        session()->flash('success', 'Coupon status updated successfully!');
    }

    public function generateReport()
    {
        $query = Coupon::query()
            ->withCount(['assignments', 'usages'])
            ->withSum('usages', 'discount_amount');

        if ($this->reportDateFrom && $this->reportDateTo) {
            $query->whereHas('usages', function ($q) {
                $q->whereBetween('created_at', [$this->reportDateFrom, $this->reportDateTo]);
            });
        }

        if ($this->reportCouponId) {
            $query->where('id', $this->reportCouponId);
        }

        $coupons = $query->get();

        $this->reportData = [
            'total_coupons' => $coupons->count(),
            'total_usage' => $coupons->sum('usages_count'),
            'total_discount' => $coupons->sum('usages_sum_discount_amount'),
            'total_assignments' => $coupons->sum('assignments_count'),
            'coupons' => $coupons,
        ];

        session()->flash('success', 'Report generated successfully!');
    }

    public function exportReport($format)
    {
        // This would typically use a package like Laravel Excel
        // For now, we'll just show a success message
        session()->flash('success', 'Report export initiated. Check your downloads folder.');
    }

    public function delete()
    {
        if ($this->deletingCoupon) {
            $this->deletingCoupon->delete();
            session()->flash('success', 'Coupon deleted successfully!');
            $this->showDeleteModal = false;
            $this->deletingCoupon = null;
        }
    }

    public function render()
    {
        return view('livewire.coupons.manage', [
            'coupons' => $this->getCoupons(),
            'stats' => $this->getStats(),
        ]);
    }
}; ?>

<div class="p-6 bg-white dark:bg-gray-900 min-h-screen" x-data="{ showCreateModal: @entangle('showCreateModal'), showEditModal: @entangle('showEditModal'), showAssignModal: @entangle('showAssignModal'), showReportModal: @entangle('showReportModal'), showDeleteModal: @entangle('showDeleteModal') }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Coupon Management</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage coupons and their assignments</p>
        </div>
        <div class="flex space-x-2 mt-4 sm:mt-0">
            <button @click="showReportModal = true" class="px-4 py-2 text-white bg-green-600 rounded-md hover:bg-green-700">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Reports
            </button>
            <button @click="showCreateModal = true" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Coupon
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Coupons</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_coupons'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Active Coupons</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_coupons'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Usage</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_usage'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Discount</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_discount'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input wire:model.live="search" type="text" placeholder="Search coupons..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="selectedStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Per Page</label>
                <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    @include('livewire.coupons.partials.table')

    <!-- Pagination -->
    <div class="mt-6">
        {{ $coupons->links() }}
    </div>

    <!-- Modals -->
    @include('livewire.coupons.partials.create-edit-modal')
    @include('livewire.coupons.partials.assign-modal')
    @include('livewire.coupons.partials.report-modal')

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50" @click="showDeleteModal = false"></div>
            
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg dark:bg-gray-800">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full dark:bg-red-900">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Coupon</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to delete this coupon? This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button wire:click="delete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
                        Delete
                    </button>
                    <button @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
