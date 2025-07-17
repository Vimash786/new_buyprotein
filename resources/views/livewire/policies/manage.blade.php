<?php

use App\Models\Policy;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    
    // Modal states
    public $showModal = false;
    public $showViewModal = false;
    public $editMode = false;
    
    // Form fields
    public $policyId = null;
    public $type = '';
    public $title = '';
    public $content = '';
    public $is_active = true;
    public $meta_title = '';
    public $meta_description = '';
    
    // View modal
    public $selectedPolicy = null;

    protected function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];

        // Only validate type when creating new policy
        if (!$this->editMode) {
            $rules['type'] = 'required|in:about-us,terms-conditions,shipping-policy,privacy-policy,return-policy|unique:policies,type';
        }

        return $rules;
    }

    protected $messages = [
        'type.unique' => 'A policy of this type already exists. Please edit the existing policy instead.',
    ];

    public function with()
    {
        $query = Policy::query()->with('updatedBy');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('type', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter);
        }

        $policies = $query->latest()->paginate(10);

        return [
            'policies' => $policies,
            'totalPolicies' => Policy::count(),
            'activePolicies' => Policy::where('is_active', true)->count(),
            'inactivePolicies' => Policy::where('is_active', false)->count(),
            'policyTypes' => Policy::TYPES,
        ];
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
        $this->dispatch('modalOpened');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('modalClosed');
    }

    public function openViewModal($id)
    {
        $this->selectedPolicy = Policy::with('updatedBy')->findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedPolicy = null;
    }

    public function edit($id)
    {
        $policy = Policy::findOrFail($id);
        
        $this->policyId = $policy->id;
        $this->type = $policy->type;
        $this->title = $policy->title;
        $this->content = $policy->content;
        $this->is_active = $policy->is_active;
        $this->meta_title = $policy->meta_title;
        $this->meta_description = $policy->meta_description;
        
        $this->editMode = true;
        $this->showModal = true;
        $this->dispatch('modalOpened');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'is_active' => $this->is_active,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'updated_by' => auth()->id(),
        ];

        if ($this->editMode) {
            // Update existing policy
            $policy = Policy::findOrFail($this->policyId);
            $policy->update($data);
            $message = 'Policy updated successfully!';
        } else {
            // Create new policy
            $data['type'] = $this->type;
            Policy::create($data);
            $message = 'Policy created successfully!';
        }

        session()->flash('message', $message);
        $this->closeModal();
    }

    public function delete($id)
    {
        $policy = Policy::findOrFail($id);
        $policy->delete();

        session()->flash('message', 'Policy deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $policy = Policy::findOrFail($id);
        $policy->update([
            'is_active' => !$policy->is_active,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('message', 'Policy status updated successfully!');
    }

    public function resetForm()
    {
        $this->policyId = null;
        $this->type = '';
        $this->title = '';
        $this->content = '';
        $this->is_active = true;
        $this->meta_title = '';
        $this->meta_description = '';
        $this->resetValidation();
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
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Policy Management</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage website policies and legal pages</p>
                </div>
                <button 
                    wire:click="openModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Policy
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Total Policies</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ $totalPolicies }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Active Policies</h3>
                        <p class="text-2xl font-bold text-green-600">{{ $activePolicies }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Inactive Policies</h3>
                        <p class="text-2xl font-bold text-red-600">{{ $inactivePolicies }}</p>
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
                                placeholder="Search policies..."
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
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
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
                        Add Policy
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

        <!-- Policies Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Policy Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Updated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Updated By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($policies as $policy)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $policy->formatted_type }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $policy->type }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $policy->title }}</div>
                                    @if($policy->meta_title)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Meta: {{ Str::limit($policy->meta_title, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="toggleStatus({{ $policy->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer
                                            {{ $policy->is_active 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}"
                                    >
                                        {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $policy->updated_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $policy->updatedBy->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="openViewModal({{ $policy->id }})"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="View Policy"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $policy->id }})"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Edit Policy"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="delete({{ $policy->id }})"
                                            wire:confirm="Are you sure you want to delete this policy? This action cannot be undone."
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            title="Delete Policy"
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
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No policies found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $policies->links() }}
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Policy' : 'Create New Policy' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-6">
                        <!-- Policy Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Policy Type <span class="text-red-500">*</span>
                            </label>
                            @if($editMode)
                                <div class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-white">
                                    {{ $type ?: 'No type selected' }}
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Policy type cannot be changed after creation</p>
                            @else
                                <select 
                                    wire:model="type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                    <option value="">Select policy type</option>
                                    @foreach($policyTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('type') <span class="text-red-500 text-sm mt-1">{{ $errors->first('type') }}</span> @enderror
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                wire:model="title"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter policy title"
                            >
                            @error('title') <span class="text-red-500 text-sm mt-1">{{ $errors->first('title') }}</span> @enderror
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <div class="mb-4">
                                <div id="editor" class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 min-h-[300px]">
                                </div>
                            </div>
                            <input type="hidden" wire:model="content" id="content-input" value="{{ $content }}">
                            @error('content') <span class="text-red-500 text-sm mt-1 block">{{ $errors->first('content') }}</span> @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use the toolbar above to format your content</p>
                        </div>

                        <!-- SEO Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Settings</h3>
                            
                            <!-- Meta Title -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Meta Title
                                </label>
                                <input 
                                    type="text"
                                    wire:model="meta_title"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="Enter meta title for SEO"
                                >
                                @error('meta_title') <span class="text-red-500 text-sm mt-1">{{ $errors->first('meta_title') }}</span> @enderror
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Meta Description
                                </label>
                                <textarea 
                                    wire:model="meta_description"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    placeholder="Enter meta description for SEO"
                                ></textarea>
                                @error('meta_description') <span class="text-red-500 text-sm mt-1">{{ $errors->first('meta_description') }}</span> @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="flex items-center">
                                <input 
                                    type="checkbox"
                                    wire:model="is_active"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active (visible on website)</span>
                            </label>
                            @error('is_active') <span class="text-red-500 text-sm mt-1">{{ $errors->first('is_active') }}</span> @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button 
                                type="submit"
                                onclick="syncQuillContent()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"
                            >
                                {{ $editMode ? 'Update Policy' : 'Create Policy' }}
                            </button>
                            <button 
                                type="button"
                                wire:click="closeModal"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium"
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
    @if($showViewModal && $selectedPolicy)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $selectedPolicy->title }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Policy Info -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Policy Type:</span>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $policyTypes[$selectedPolicy->type] ?? $selectedPolicy->type }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status:</span>
                                    <p class="text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $selectedPolicy->is_active 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                            {{ $selectedPolicy->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated:</span>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedPolicy->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated By:</span>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $selectedPolicy->updatedBy->name ?? 'System' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Content</h3>
                            <div class="prose dark:prose-invert max-w-none bg-white dark:bg-zinc-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                {!! $selectedPolicy->content !!}
                            </div>
                        </div>

                        <!-- SEO Info -->
                        @if($selectedPolicy->meta_title || $selectedPolicy->meta_description)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Information</h3>
                                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 space-y-3">
                                    @if($selectedPolicy->meta_title)
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta Title:</span>
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedPolicy->meta_title }}</p>
                                        </div>
                                    @endif
                                    @if($selectedPolicy->meta_description)
                                        <div>
                                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta Description:</span>
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $selectedPolicy->meta_description }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button 
                                wire:click="edit({{ $selectedPolicy->id }})"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"
                            >
                                Edit Policy
                            </button>
                            <button 
                                wire:click="closeViewModal"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<!-- Initialize Quill editor -->
<script>
  let quill;
  
  // Initialize Quill when modal opens
  document.addEventListener('livewire:init', function () {
    Livewire.on('modalOpened', () => {
      setTimeout(() => {
        if (!quill && document.getElementById('editor')) {
          quill = new Quill('#editor', {
            theme: 'snow'
          });

          // Function to load content into Quill
          function loadContentIntoQuill() {
            const hiddenInput = document.getElementById('content-input');
            if (hiddenInput && hiddenInput.value && hiddenInput.value.trim() !== '') {
              quill.root.innerHTML = hiddenInput.value;
            }
          }

          // Load initial content
          loadContentIntoQuill();

          // Only update hidden input on text change (no Livewire sync during typing)
          quill.on('text-change', function() {
            const content = quill.root.innerHTML;
            document.getElementById('content-input').value = content;
            
            // Hide validation error immediately when typing
            const errorElement = document.querySelector('.text-red-500');
            if (errorElement && errorElement.textContent.includes('content field is required')) {
              errorElement.style.display = 'none';
            }
          });
        }
      }, 100);
    });

    Livewire.on('modalClosed', () => {
      if (quill) {
        quill = null;
      }
    });
  });

  // Function to sync content before form submission
  function syncQuillContent() {
    if (quill) {
      const content = quill.root.innerHTML;
      document.getElementById('content-input').value = content;
      @this.set('content', content);
    }
  }
</script>
