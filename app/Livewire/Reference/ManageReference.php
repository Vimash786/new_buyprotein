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
    public $assignmentType = 'all_users'; // 'users', 'products', 'sellers'
    public $selectedItems = [];
    public $searchItems = '';

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
        
        Reference::create($validatedData);
        
        session()->flash('message', 'Reference created successfully!');
        $this->closeModal();
    }

    public function edit($id)
    {
        $Reference = Reference::findOrFail($id);
        $this->ReferenceId = $Reference->id;
        $this->code = $Reference->code;
        $this->name = $Reference->name;
        $this->description = $Reference->description;
        $this->type = $Reference->type;
        $this->value = $Reference->value;
        $this->minimum_amount = $Reference->minimum_amount;
        $this->maximum_discount = $Reference->maximum_discount;
        $this->usage_limit = $Reference->usage_limit;
        $this->user_usage_limit = $Reference->user_usage_limit;
        $this->starts_at = $Reference->starts_at->format('Y-m-d\TH:i');
        $this->expires_at = $Reference->expires_at->format('Y-m-d\TH:i');
        $this->status = $Reference->status;
        $this->applicable_to = $Reference->applicable_to;
        $this->user_types = $Reference->user_types ? json_decode($Reference->user_types, true) : [];
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function update()
    {
        $validatedData = $this->validate();
        
        // Convert user_types array to JSON if needed
        $validatedData['user_types'] = !empty($this->user_types) ? json_encode($this->user_types) : null;
        
        $Reference = Reference::findOrFail($this->ReferenceId);
        $Reference->update($validatedData);
        
        session()->flash('message', 'Reference updated successfully!');
        $this->closeModal();
    }

    public function delete($id = null)
    {
        $Reference = $this->ReferenceToDelete ?? Reference::findOrFail($id);
        $Reference->delete();
        
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
            'ReferenceId', 'code', 'name', 'description', 'type', 'value', 
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
        $this->searchItems = '';
    }

    public function getAssignableItems()
    {
        if (!$this->assignmentType) {
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

    public function assignReference()
    {
        if (!$this->selectedReference || empty($this->selectedItems)) {
            session()->flash('error', 'Please select items to assign the Reference to.');
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
                $existingAssignment = ReferenceAssign::where('Reference_id', $this->selectedReference->id)
                    ->where('assignable_type', $assignableType)
                    ->where('assignable_id', $itemId)
                    ->first();

                if (!$existingAssignment) {
                    ReferenceAssign::create([
                        'Reference_id' => $this->selectedReference->id,
                        'assignable_type' => $assignableType,
                        'assignable_id' => $itemId,
                        'assigned_at' => now()
                    ]);
                    $assignedCount++;
                }
            }
        }

        session()->flash('message', "Reference assigned to {$assignedCount} items successfully!");
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
        
        // Get References based on filters
        $ReferencesQuery = Reference::query();
        
        if ($this->reportReferenceId) {
            $ReferencesQuery->where('id', $this->reportReferenceId);
        }
        
        $References = $ReferencesQuery->withCount([
            'assignments',
            'usages' => function($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        ])->get();
        
        // Calculate totals
        $totalUsage = $References->sum('usages_count');
        $totalDiscount = $References->sum(function($Reference) {
            return $Reference->usages->sum('discount_amount') ?? 0;
        });
        
        $this->reportData = [
            'total_References' => $References->count(),
            'total_usage' => $totalUsage,
            'total_discount' => $totalDiscount,
            'total_assignments' => $References->sum('assignments_count'),
            'References' => $References->map(function($Reference) {
                return (object)[
                    'id' => $Reference->id,
                    'name' => $Reference->name,
                    'code' => $Reference->code,
                    'type' => $Reference->type,
                    'value' => $Reference->value,
                    'status' => $Reference->status,
                    'usage_limit' => $Reference->usage_limit,
                    'used_count' => $Reference->usages_count ?? 0,
                    'assignments_count' => $Reference->assignments_count ?? 0,
                    'total_discount_amount' => $Reference->usages->sum('discount_amount') ?? 0,
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
        
        return view('livewire.Reference.manage-reference', [
            'references' => $query->latest()->paginate(10),
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
