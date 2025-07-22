<?php

namespace App\Livewire\Coupons;

use App\Models\Coupon;
use App\Models\CouponAssignment;
use App\Models\User;
use App\Models\products;
use App\Models\Sellers;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class ManageCoupons extends Component
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
    public $reportCouponId = '';

    public function mount()
    {
        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
        $this->reportDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->reportDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:255|unique:coupons,code,' . $this->couponId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'user_usage_limit' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'status' => 'required|in:active,inactive,expired',
            'applicable_to' => 'required|in:all,users,products,sellers',
            'user_types' => 'nullable|array',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->code = 'COUP-' . strtoupper(Str::random(8));
        $this->showModal = true;
    }

    public function generateCode()
    {
        $this->code = 'COUP-' . strtoupper(Str::random(6));
    }

    public function save()
    {
        if ($this->editMode) {
            $this->update();
        } else {
            $this->store();
        }
    }

    public function store()
    {
        $validatedData = $this->validate();
        
        // Convert user_types array to JSON if needed
        $validatedData['user_types'] = !empty($this->user_types) ? json_encode($this->user_types) : null;
        
        Coupon::create($validatedData);
        
        session()->flash('message', 'Coupon created successfully!');
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
        $this->user_types = $coupon->user_types ? json_decode($coupon->user_types, true) : [];
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function update()
    {
        $validatedData = $this->validate();
        
        // Convert user_types array to JSON if needed
        $validatedData['user_types'] = !empty($this->user_types) ? json_encode($this->user_types) : null;
        
        $coupon = Coupon::findOrFail($this->couponId);
        $coupon->update($validatedData);
        
        session()->flash('message', 'Coupon updated successfully!');
        $this->closeModal();
    }

    public function delete($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        
        session()->flash('message', 'Coupon deleted successfully!');
    }

    public function resetForm()
    {
        $this->reset([
            'couponId', 'code', 'name', 'description', 'type', 'value', 
            'minimum_amount', 'maximum_discount', 'usage_limit', 'user_usage_limit',
            'starts_at', 'expires_at', 'status', 'applicable_to', 'user_types'
        ]);
        $this->editMode = false;
        $this->type = 'percentage';
        $this->status = 'active';
        $this->applicable_to = 'all';
        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function openAssignModal($couponId)
    {
        $this->selectedCoupon = Coupon::findOrFail($couponId);
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

    public function getAssignableItems()
    {
        if (!$this->assignmentType || !$this->searchItems) {
            return collect();
        }

        $query = null;
        
        switch ($this->assignmentType) {
            case 'users':
                $query = User::query();
                if ($this->searchItems) {
                    $query->where('name', 'like', '%' . $this->searchItems . '%')
                          ->orWhere('email', 'like', '%' . $this->searchItems . '%');
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
            $assignableType = null;
            
            switch ($this->assignmentType) {
                case 'users':
                    $modelClass = User::class;
                    $assignableType = 'user';
                    break;
                case 'products':
                    $modelClass = products::class;
                    $assignableType = 'product';
                    break;
                case 'sellers':
                    $modelClass = Sellers::class;
                    $assignableType = 'seller';
                    break;
            }

            if ($modelClass && $assignableType) {
                // Check if assignment already exists
                $existingAssignment = CouponAssignment::where('coupon_id', $this->selectedCoupon->id)
                    ->where('assignable_type', $assignableType)
                    ->where('assignable_id', $itemId)
                    ->first();

                if (!$existingAssignment) {
                    CouponAssignment::create([
                        'coupon_id' => $this->selectedCoupon->id,
                        'assignable_type' => $assignableType,
                        'assignable_id' => $itemId,
                        'assigned_at' => now()
                    ]);
                    $assignedCount++;
                }
            }
        }

        session()->flash('message', "Coupon assigned to {$assignedCount} items successfully!");
        $this->closeAssignModal();
    }

    public function removeAssignment($assignmentId)
    {
        $assignment = CouponAssignment::find($assignmentId);
        
        if ($assignment) {
            $assignment->delete();
            session()->flash('message', 'Assignment removed successfully!');
        } else {
            session()->flash('error', 'Assignment not found!');
        }
    }

    public function openReportModal()
    {
        $this->showReportModal = true;
        $this->generateReport();
    }

    public function closeReportModal()
    {
        $this->showReportModal = false;
        $this->reportData = null;
    }

    public function generateReport()
    {
        $dateFrom = $this->reportDateFrom ?: now()->startOfMonth()->toDateString();
        $dateTo = $this->reportDateTo ?: now()->endOfMonth()->toDateString();
        
        // Get coupons based on filters
        $couponsQuery = Coupon::query();
        
        if ($this->reportCouponId) {
            $couponsQuery->where('id', $this->reportCouponId);
        }
        
        $coupons = $couponsQuery->withCount([
            'assignments',
            'usages' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        ])->get();
        
        // Calculate totals
        $totalUsage = $coupons->sum('usages_count');
        $totalDiscount = $coupons->sum(function($coupon) {
            return $coupon->usages->sum('discount_amount') ?? 0;
        });
        
        $this->reportData = [
            'total_coupons' => $coupons->count(),
            'total_usage' => $totalUsage,
            'total_discount' => $totalDiscount,
            'total_assignments' => $coupons->sum('assignments_count'),
            'coupons' => $coupons->map(function($coupon) {
                return (object)[
                    'id' => $coupon->id,
                    'name' => $coupon->name,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'status' => $coupon->status,
                    'usage_limit' => $coupon->usage_limit,
                    'used_count' => $coupon->usages_count ?? 0,
                    'assignments_count' => $coupon->assignments_count ?? 0,
                    'total_discount_amount' => $coupon->usages->sum('discount_amount') ?? 0,
                ];
            }),
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ]
        ];
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

    public function render()
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
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
        
        return view('livewire.coupons.manage-coupons', [
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
            'sellers' => Sellers::limit(50)->get(),
            'users' => User::limit(50)->get(),
            'products' => products::limit(50)->get(),
            'assignableItems' => $this->getAssignableItems(),
        ]);
    }
}
