<?php

namespace App\Livewire\Reference;

use App\Models\Reference;
use App\Models\ReferenceAssign;
use App\Models\User;
use App\Models\products;
use App\Models\Sellers;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ManageReference extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $showModal = false;
    public $showReportModal = false;
    public $showAssignModal = false;
    public $editMode = false;
    public $ReferenceId = null; 
    public $selectedReference = null;

    // Form fields
    public $code = '';
    public $name = '';
    public $description = '';
    public $type = 'percentage';
    public $giver_discount = '';
    public $applyer_discount = '';
    public $minimum_amount = '';
    public $maximum_discount = '';
    public $usage_limit = '';
    public $user_usage_limit = '';
    public $starts_at = '';
    public $expires_at = '';
    public $status = 'active';
    public $applicable_to = [];
    public $user_types = [];

    // Assignment fields
    public $assignmentType = [];
    public $selectedItems = [];
    public $searchItems = '';
    public $assignmentError = '';

    // Report functionality
    public $reportData = null;
    public $reportDateFrom = '';
    public $reportDateTo = '';
    public $reportReferenceId = '';

    // Delete modal properties
    public $showDeleteModal = false;
    public $referenceToDelete = null;

    public function mount()
    {
        // Check if user has access to references (only Super role)
        if (Auth::user()->role !== 'Super') {
            abort(403, 'Access denied. Only administrators can manage references.');
        }

        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
        $this->reportDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->reportDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:255|unique:reference,code,' . $this->ReferenceId,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'giver_discount' => 'required|numeric|min:0',
            'applyer_discount' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'user_usage_limit' => 'nullable|integer|min:0',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'status' => 'required|in:active,inactive,expired',
            'applicable_to' => 'required|array|min:1', // Changed to array validation
            'applicable_to.*' => 'in:all,all_users,all_gym,all_shop', // Validate each item in array
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
        
        // user_types will be automatically cast to JSON by Laravel
        $validatedData['user_types'] = $this->user_types;
        
        Reference::create($validatedData);
        
        session()->flash('message', 'Reference created successfully!');
        $this->closeModal();
    }

    public function edit($id)
    {
        $reference = Reference::findOrFail($id);
        $this->ReferenceId = $reference->id;
        $this->code = $reference->code;
        $this->name = $reference->name;
        $this->description = $reference->description;
        $this->type = $reference->type;
        $this->giver_discount = $reference->giver_discount;
        $this->applyer_discount = $reference->applyer_discount;
        $this->minimum_amount = $reference->minimum_amount;
        $this->maximum_discount = $reference->maximum_discount;
        $this->usage_limit = $reference->usage_limit;
        $this->user_usage_limit = $reference->user_usage_limit;
        $this->starts_at = $reference->starts_at->format('Y-m-d\TH:i');
        $this->expires_at = $reference->expires_at->format('Y-m-d\TH:i');
        $this->status = $reference->status;
        $this->applicable_to = is_array($reference->applicable_to) ? $reference->applicable_to : [$reference->applicable_to];
        $this->user_types = $reference->user_types ?? [];
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function update()
    {
        $validatedData = $this->validate();
        
        // user_types will be automatically cast to JSON by Laravel
        $validatedData['user_types'] = $this->user_types;
        
        $reference = Reference::findOrFail($this->ReferenceId);
        $reference->update($validatedData);
        
        session()->flash('message', 'Reference updated successfully!');
        $this->closeModal();
    }

    public function delete($id = null)
    {
        $reference = $this->referenceToDelete ?? Reference::findOrFail($id);
        $reference->delete();
        
        session()->flash('message', 'Reference deleted successfully!');
        $this->closeDeleteModal();
    }

    public function confirmDelete($id)
    {
        $this->referenceToDelete = Reference::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->referenceToDelete = null;
    }

    public function resetForm()
    {
        $this->reset([
            'ReferenceId', 'code', 'name', 'description', 'type', 'giver_discount', 'applyer_discount',
            'minimum_amount', 'maximum_discount', 'usage_limit', 'user_usage_limit',
            'starts_at', 'expires_at', 'status', 'applicable_to', 'user_types'
        ]);
        $this->editMode = false;
        $this->type = 'percentage';
        $this->status = 'active';
        $this->applicable_to = [];
        $this->assignmentType = [];
        $this->starts_at = now()->format('Y-m-d\TH:i');
        $this->expires_at = now()->addDays(30)->format('Y-m-d\TH:i');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function openAssignModal($ReferenceId)
    {
        $this->selectedReference = Reference::findOrFail($ReferenceId);
        $this->showAssignModal = true;
        $this->selectedItems = [];
        $this->searchItems = '';
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedReference = null;
        $this->selectedItems = [];
        $this->assignmentType = [];
        $this->searchItems = '';
        $this->assignmentError = '';
    }

    public function getAssignableItems()
    {
        if (empty($this->assignmentType)) {
            return collect();
        }

        $query = null;
        
        // Check if specific user assignment types are selected
        if (in_array('specific_shop_user', $this->assignmentType)) {
            $query = User::where('role', 'Shop Owner');
            if ($this->searchItems) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->searchItems . '%')
                      ->orWhere('email', 'like', '%' . $this->searchItems . '%');
                });
            }
        } elseif (in_array('specific_gym', $this->assignmentType)) {
            $query = User::whereIn('role', ['Gym Owner/Trainer/Influencer/Dietitian']);
            if ($this->searchItems) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->searchItems . '%')
                      ->orWhere('email', 'like', '%' . $this->searchItems . '%');
                });
            }
        }

        return $query ? $query->limit(20)->get() : collect();
    }

    public function validateSpecificSelection()
    {
        // Clear any previous error
        $this->assignmentError = '';
        
        // Check if both specific checkboxes are selected
        if (in_array('specific_shop_user', $this->assignmentType) && in_array('specific_gym', $this->assignmentType)) {
            $this->assignmentError = 'Cannot select both specific shop users and specific gym users at the same time. Please choose only one specific assignment type.';
            
            // Remove the last selected to prevent both being selected
            $lastIndex = array_search('specific_gym', $this->assignmentType);
            if ($lastIndex !== false) {
                unset($this->assignmentType[$lastIndex]);
                $this->assignmentType = array_values($this->assignmentType); // Reindex array
            }
        }
    }

    public function updated($property)
    {
        // Clear assignment error when assignment type changes
        if ($property === 'assignmentType') {
            $this->assignmentError = '';
            $this->validateGlobalSpecificConflict();
            $this->validateSpecificSelection();
        }
    }

    public function validateGlobalSpecificConflict()
    {
        $hasGlobal = in_array('all_gym', $this->assignmentType) || in_array('all_shop', $this->assignmentType);
        $hasSpecific = in_array('specific_shop_user', $this->assignmentType) || in_array('specific_gym', $this->assignmentType);
        
        // If both global and specific are selected, clear the conflicting ones
        if ($hasGlobal && $hasSpecific) {
            // Remove specific assignments if global ones are selected
            $this->assignmentType = array_filter($this->assignmentType, function($type) {
                return !in_array($type, ['specific_shop_user', 'specific_gym']);
            });
            $this->assignmentType = array_values($this->assignmentType); // Reindex array
            
            // Clear selected items since specific assignments were removed
            $this->selectedItems = [];
            
            $this->assignmentError = 'Global assignments selected. Specific assignments have been cleared to avoid conflicts.';
        }
    }

    public function assignReference()
    {
        if (!$this->selectedReference) {
            session()->flash('error', 'No reference selected.');
            return;
        }

        if (empty($this->assignmentType)) {
            session()->flash('error', 'Please select at least one assignment type.');
            return;
        }

        // Update reference applicable_to field with multiple values
        $this->selectedReference->update(['applicable_to' => $this->assignmentType]);

        // Handle specific user assignments if needed
        if (in_array('specific_shop_user', $this->assignmentType) || in_array('specific_gym', $this->assignmentType)) {
            if (empty($this->selectedItems)) {
                session()->flash('error', 'Please select specific users for individual assignments.');
                return;
            }

            $assignedCount = 0;
            foreach ($this->selectedItems as $itemId) {
                // Check if assignment already exists
                $existingAssignment = ReferenceAssign::where('reference_id', $this->selectedReference->id)
                    ->where('assignable_type', 'user')
                    ->where('assignable_id', $itemId)
                    ->first();

                if (!$existingAssignment) {
                    ReferenceAssign::create([
                        'reference_id' => $this->selectedReference->id,
                        'assignable_type' => 'user',
                        'assignable_id' => $itemId,
                        'assigned_at' => now()
                    ]);
                    $assignedCount++;
                }
            }

            if ($assignedCount > 0) {
                session()->flash('message', "Reference assigned successfully! ({$assignedCount} specific assignment(s) created)");
            } else {
                session()->flash('message', "Reference assignment already exists.");
            }
        } else {
            session()->flash('message', "Reference assigned successfully to selected user groups!");
        }
        
        $this->closeAssignModal();
    }

    public function removeAssignment($assignmentId)
    {
        $assignment = ReferenceAssign::find($assignmentId);
        
        if ($assignment) {
            $assignment->delete();
            session()->flash('message', 'Assignment removed successfully!');
        } else {
            session()->flash('error', 'Assignment not found!');
        }
    }

    public function removeGlobalAssignment($applicableType)
    {
        if (!$this->selectedReference) {
            session()->flash('error', 'No reference selected.');
            return;
        }

        $currentApplicableTo = is_array($this->selectedReference->applicable_to) 
            ? $this->selectedReference->applicable_to 
            : [$this->selectedReference->applicable_to];

        // Remove the specific type from the array
        $newApplicableTo = array_filter($currentApplicableTo, function($type) use ($applicableType) {
            return $type !== $applicableType;
        });

        // Update the reference
        $this->selectedReference->update(['applicable_to' => array_values($newApplicableTo)]);
        
        // Refresh to show updated assignments
        $this->selectedReference = $this->selectedReference->fresh();
        
        session()->flash('message', 'Global assignment removed successfully!');
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
        
        // Get references based on filters
        $referencesQuery = Reference::query();
        
        if ($this->reportReferenceId) {
            $referencesQuery->where('id', $this->reportReferenceId);
        }
        
        $references = $referencesQuery->withCount([
            'assignments',
            'usages' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        ])->with(['usages' => function($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])->get();
        
        // Calculate totals
        $totalUsage = $references->sum('usages_count');
        $totalGiverEarnings = $references->sum(function($reference) {
            return $reference->usages->sum('giver_earning_amount') ?? 0;
        });
        $totalApplyerDiscounts = $references->sum(function($reference) {
            return $reference->usages->sum('applyer_discount_amount') ?? 0;
        });
        $totalDiscountAmount = $references->sum(function($reference) {
            return $reference->usages->sum('total_discount_amount') ?? 0;
        });
        
        $this->reportData = [
            'total_references' => $references->count(),
            'total_usage' => $totalUsage,
            'total_giver_earnings' => $totalGiverEarnings,
            'total_applyer_discounts' => $totalApplyerDiscounts,
            'total_discount_amount' => $totalDiscountAmount,
            'total_assignments' => $references->sum('assignments_count'),
            'references' => $references->map(function($reference) {
                $giverEarnings = $reference->usages->sum('giver_earning_amount') ?? 0;
                $applyerDiscounts = $reference->usages->sum('applyer_discount_amount') ?? 0;
                $totalDiscountAmount = $reference->usages->sum('total_discount_amount') ?? 0;
                
                return (object)[
                    'id' => $reference->id,
                    'name' => $reference->name,
                    'code' => $reference->code,
                    'type' => $reference->type,
                    'giver_discount' => $reference->giver_discount,
                    'applyer_discount' => $reference->applyer_discount,
                    'status' => $reference->status,
                    'usage_limit' => $reference->usage_limit,
                    'used_count' => $reference->usages_count ?? 0,
                    'assignments_count' => $reference->assignments_count ?? 0,
                    'total_giver_earnings' => $giverEarnings,
                    'total_applyer_discounts' => $applyerDiscounts,
                    'total_discount_amount' => $totalDiscountAmount,
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
        $query = Reference::query();
        
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
        
        return view('livewire.reference.manage-reference', [
            'references' => $query->latest()->paginate(10),
            'allReferences' => Reference::select('id', 'name', 'code')->where('status', 'active')->get(),
            'totalReference' => Reference::count(),
            'activeReference' => Reference::where('status', 'active')->count(),
            'expiredReference' => Reference::where('expires_at', '<', now())->count(),
            'upcomingReference' => Reference::where('starts_at', '>', now())->count(),
            'availableUserTypes' => [
                'User' => 'Regular User',
                'Gym Owner/Trainer/Influencer/Dietitian' => 'Gym Owner/Trainer/Influencer/Dietitian',
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
