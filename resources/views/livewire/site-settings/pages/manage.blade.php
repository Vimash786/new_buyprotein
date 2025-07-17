<?php

use App\Models\SitePage;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $pageTypeFilter = '';
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $showStatusModal = false;
    public $viewingPage = null;
    public $pageToDelete = null;
    public $pageToToggleStatus = null;

    public function with()
    {
        $query = SitePage::query();

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->pageTypeFilter) {
            $query->where('page_type', $this->pageTypeFilter);
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(10);

        return [
            'pages' => $pages,
            'totalPages' => SitePage::count(),
            'activePages' => SitePage::where('status', true)->count(),
            'inactivePages' => SitePage::where('status', false)->count(),
            'pageTypes' => SitePage::PAGE_TYPES,
        ];
    }

    public function openModal($pageType = null)
    {
        if ($pageType) {
            return redirect()->route('site-pages.create.type', $pageType);
        } else {
            return redirect()->route('site-pages.create');
        }
    }

    public function edit($id)
    {
        return redirect()->route('site-pages.edit', $id);
    }

    public function openViewModal($id)
    {
        $this->viewingPage = SitePage::with(['creator', 'updater'])->find($id);
        $this->showViewModal = true;
    }

    public function confirmDelete($id)
    {
        $this->pageToDelete = SitePage::find($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->pageToDelete) {
            $this->pageToDelete->delete();
            session()->flash('message', 'Page deleted successfully!');
        }
        
        $this->closeDeleteModal();
    }

    public function confirmStatusToggle($id)
    {
        $this->pageToToggleStatus = SitePage::find($id);
        $this->showStatusModal = true;
    }

    public function toggleStatus()
    {
        if ($this->pageToToggleStatus) {
            $this->pageToToggleStatus->update([
                'status' => !$this->pageToToggleStatus->status,
                'updated_by' => Auth::id()
            ]);
            
            $status = $this->pageToToggleStatus->status ? 'activated' : 'deactivated';
            session()->flash('message', "Page {$status} successfully!");
        }
        
        $this->closeStatusModal();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingPage = null;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->pageToDelete = null;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->pageToToggleStatus = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPageTypeFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Site Pages Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage website content pages like About Us, Terms & Conditions, Privacy Policy, etc.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Pages</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalPages }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $activePages }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Inactive</h3>
                        <p class="text-3xl font-bold text-red-600">{{ $inactivePages }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Add Buttons -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Add Pages</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach($pageTypes as $key => $name)
                        @php
                            $existingPage = $pages->where('page_type', $key)->first();
                        @endphp
                        <button 
                            wire:click="openModal('{{ $key }}')"
                            @if($existingPage) disabled @endif
                            class="p-3 text-sm rounded-lg border transition-colors
                                   @if($existingPage) 
                                       bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-600
                                   @else 
                                       bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30
                                   @endif"
                        >
                            @if($existingPage)
                                <svg class="w-4 h-4 mx-auto mb-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Created
                            @else
                                <svg class="w-4 h-4 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $name }}
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Filters and Add Button -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="search"
                                placeholder="Search pages..."
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

                        <!-- Page Type Filter -->
                        <select wire:model.live="pageTypeFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Page Types</option>
                            @foreach($pageTypes as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
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
                        Add Page
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

        <!-- Pages Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Page</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($pages as $page)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $page->slug }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $page->page_type_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="confirmStatusToggle({{ $page->id }})"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                               {{ $page->status 
                                                  ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/70' 
                                                  : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/70' }}"
                                    >
                                        @if($page->status)
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Active
                                        @else
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Inactive
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $page->creator->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $page->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="openViewModal({{ $page->id }})"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $page->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="confirmDelete({{ $page->id }})"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                            title="Delete"
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
                                    No pages found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $pages->links() }}
            </div>
        </div>
    </div>

    <!-- View Modal -->
    @if($showViewModal && $viewingPage)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $viewingPage->title }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Page Info Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Page Type</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingPage->page_type_name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Slug</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingPage->slug }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</label>
                                    <p class="text-gray-900 dark:text-white">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                               {{ $viewingPage->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $viewingPage->status_text }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Information -->
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Meta Title</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingPage->meta_title ?: 'Not set' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Meta Description</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingPage->meta_description ?: 'Not set' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Meta Keywords</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingPage->meta_keywords ?: 'Not set' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Content</h3>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <div class="prose dark:prose-invert max-w-none">
                                {!! nl2br(e($viewingPage->content)) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Metadata</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Created By</label>
                                <p class="text-gray-900 dark:text-white">{{ $viewingPage->creator->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Created Date</label>
                                <p class="text-gray-900 dark:text-white">{{ $viewingPage->created_at->format('M d, Y g:i A') }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Last Updated By</label>
                                <p class="text-gray-900 dark:text-white">{{ $viewingPage->updater->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Last Updated</label>
                                <p class="text-gray-900 dark:text-white">{{ $viewingPage->updated_at->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $pageToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">Delete Page</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        Are you sure you want to delete "<strong>{{ $pageToDelete->title }}</strong>"? This action cannot be undone.
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
                            Delete Page
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status Change Confirmation Modal -->
    @if($showStatusModal && $pageToToggleStatus)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto 
                                {{ $pageToToggleStatus->status ? 'bg-red-100 dark:bg-red-900/50' : 'bg-green-100 dark:bg-green-900/50' }} 
                                rounded-full mb-4">
                        @if($pageToToggleStatus->status)
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        {{ $pageToToggleStatus->status ? 'Deactivate' : 'Activate' }} Page
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        Are you sure you want to {{ $pageToToggleStatus->status ? 'deactivate' : 'activate' }} 
                        "<strong>{{ $pageToToggleStatus->title }}</strong>"?
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
                                   {{ $pageToToggleStatus->status ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} 
                                   rounded-lg"
                        >
                            {{ $pageToToggleStatus->status ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
