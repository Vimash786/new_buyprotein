<?php

use App\Models\Category;
use App\Models\SubCategory;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $showModal = false;
    public $showSubCategoryModal = false;
    public $showDeleteModal = false;
    public $editMode = false;
    public $categoryId = null;
    public $subCategoryId = null;
    public $activeTab = 'categories'; // 'categories' or 'subcategories'
    public $deleteType = ''; // 'category' or 'subcategory'
    public $deleteId = null;
    public $deleteName = '';
    
    // Category form fields
    public $name = '';
    public $description = '';
    public $image = null;
    public $current_image = null;
    public $is_active = true;
    public $sort_order = 0;
    
    // SubCategory form fields
    public $category_id = '';
    public $sub_name = '';
    public $sub_description = '';
    public $sub_image = null;
    public $current_sub_image = null;
    public $sub_is_active = true;
    public $sub_sort_order = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:400',
        'is_active' => 'boolean',
        'sort_order' => 'integer|min:0',
        
        'category_id' => 'required|exists:categories,id',
        'sub_name' => 'required|string|max:255',
        'sub_description' => 'nullable|string',
        'sub_image' => 'nullable|image|max:400',
        'sub_is_active' => 'boolean',
        'sub_sort_order' => 'integer|min:0',
    ];

    public function with()
    {
        $categoriesQuery = Category::query()
            ->withCount('subCategories')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->ordered();

        $subCategoriesQuery = SubCategory::query()
            ->with('category')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('category', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->ordered();

        return [
            'categories' => $categoriesQuery->paginate(10, ['*'], 'categories'),
            'subCategories' => $subCategoriesQuery->paginate(10, ['*'], 'subcategories'),
            'totalCategories' => Category::count(),
            'totalSubCategories' => SubCategory::count(),
            'activeCategories' => Category::where('is_active', true)->count(),
            'activeSubCategories' => SubCategory::where('is_active', true)->count(),
            'allCategories' => Category::active()->ordered()->get(),
        ];
    }

    public function openModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openSubCategoryModal()
    {
        $this->resetSubCategoryForm();
        $this->editMode = false;
        $this->showSubCategoryModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeSubCategoryModal()
    {
        $this->showSubCategoryModal = false;
        $this->resetSubCategoryForm();
    }
    
    public function resetForm()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->description = '';
        $this->image = null;
        $this->current_image = null;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetErrorBag();
    }

    public function resetSubCategoryForm()
    {
        $this->subCategoryId = null;
        $this->category_id = '';
        $this->sub_name = '';
        $this->sub_description = '';
        $this->sub_image = null;
        $this->current_sub_image = null;
        $this->sub_is_active = true;
        $this->sub_sort_order = 0;
        $this->resetErrorBag();
    }
    
    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => $this->editMode ? 'nullable|image|max:400' : 'nullable|image|max:400',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];

        $this->validate($rules);

        $categoryData = [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        // Handle image upload
        if ($this->image) {
            $imagePath = $this->image->store('categories', 'public');
            $categoryData['image'] = $imagePath;
        }

        if ($this->editMode) {
            $category = Category::findOrFail($this->categoryId);
            
            // Delete old image if new one is uploaded
            if ($this->image && $category->image) {
                Storage::disk('public')->delete($category->image);
            }
            
            $category->update($categoryData);
            session()->flash('message', 'Category updated successfully!');
        } else {
            Category::create($categoryData);
            session()->flash('message', 'Category created successfully!');
        }

        $this->closeModal();
    }
    
    public function saveSubCategory()
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'sub_name' => 'required|string|max:255',
            'sub_description' => 'nullable|string',
            'sub_image' => $this->editMode ? 'nullable|image|max:400' : 'nullable|image|max:400',
            'sub_is_active' => 'boolean',
            'sub_sort_order' => 'integer|min:0',
        ];

        $this->validate($rules);

        $subCategoryData = [
            'category_id' => $this->category_id,
            'name' => $this->sub_name,
            'description' => $this->sub_description,
            'is_active' => $this->sub_is_active,
            'sort_order' => $this->sub_sort_order,
        ];

        // Handle image upload
        if ($this->sub_image) {
            $imagePath = $this->sub_image->store('subcategories', 'public');
            $subCategoryData['image'] = $imagePath;
        }

        if ($this->editMode) {
            $subCategory = SubCategory::findOrFail($this->subCategoryId);
            
            // Delete old image if new one is uploaded
            if ($this->sub_image && $subCategory->image) {
                Storage::disk('public')->delete($subCategory->image);
            }
            
            $subCategory->update($subCategoryData);
            session()->flash('message', 'Sub-category updated successfully!');
        } else {
            SubCategory::create($subCategoryData);
            session()->flash('message', 'Sub-category created successfully!');
        }

        $this->closeSubCategoryModal();
    }
    
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->current_image = $category->image;
        $this->is_active = $category->is_active;
        $this->sort_order = $category->sort_order;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function editSubCategory($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        
        $this->subCategoryId = $subCategory->id;
        $this->category_id = $subCategory->category_id;
        $this->sub_name = $subCategory->name;
        $this->sub_description = $subCategory->description;
        $this->current_sub_image = $subCategory->image;
        $this->sub_is_active = $subCategory->is_active;
        $this->sub_sort_order = $subCategory->sort_order;
        
        $this->editMode = true;
        $this->showSubCategoryModal = true;
    }

    public function confirmDelete($id, $type = 'category')
    {
        if ($type === 'category') {
            $category = Category::findOrFail($id);
            $this->deleteName = $category->name;
        } else {
            $subCategory = SubCategory::findOrFail($id);
            $this->deleteName = $subCategory->name;
        }
        
        $this->deleteId = $id;
        $this->deleteType = $type;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteType = '';
        $this->deleteName = '';
    }

    public function confirmDeleteAction()
    {
        if ($this->deleteType === 'category') {
            $this->delete($this->deleteId);
        } else {
            $this->deleteSubCategory($this->deleteId);
        }
        
        $this->cancelDelete();
    }
    
    public function delete($id)
    {
        $category = Category::findOrFail($id);
        
        // Delete image if exists
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        
        $category->delete();
        session()->flash('message', 'Category deleted successfully!');
    }

    public function deleteSubCategory($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        
        // Delete image if exists
        if ($subCategory->image) {
            Storage::disk('public')->delete($subCategory->image);
        }
        
        $subCategory->delete();
        session()->flash('message', 'Sub-category deleted successfully!');
    }
    
    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        
        session()->flash('message', 'Category status updated successfully!');
    }

    public function toggleSubCategoryStatus($id)
    {
        $subCategory = SubCategory::findOrFail($id);
        $subCategory->update(['is_active' => !$subCategory->is_active]);
        
        session()->flash('message', 'Sub-category status updated successfully!');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7x2 mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Categories Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Manage product categories and sub-categories</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" hidden>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Categories</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalCategories }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Sub-Categories</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $totalSubCategories }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Categories</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $activeCategories }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Sub-Categories</h3>
                        <p class="text-3xl font-bold text-orange-600">{{ $activeSubCategories }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex">
                    <button 
                        wire:click="setActiveTab('categories')"
                        class="py-4 px-6 border-b-2 font-medium text-sm {{ $activeTab === 'categories' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Categories
                    </button>
                    <button 
                        wire:click="setActiveTab('subcategories')"
                        class="py-4 px-6 border-b-2 font-medium text-sm {{ $activeTab === 'subcategories' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Sub-Categories
                    </button>
                </nav>
            </div>

            <!-- Filters and Add Button -->
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4 flex-1">
                        <!-- Search -->
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="search"
                                placeholder="Search {{ $activeTab }}..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Add Button -->
                    @if($activeTab === 'categories')
                        <button 
                            wire:click="openModal"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Category
                        </button>
                    @else
                        <button 
                            wire:click="openSubCategoryModal"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Sub-Category
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Content Tables -->
        @if($activeTab === 'categories')
            <!-- Categories Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sub-Categories</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sort Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($categories as $category)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($category->image)
                                                <img class="h-10 w-10 rounded-lg object-cover mr-3" 
                                                     src="{{ Storage::url($category->image) }}" 
                                                     alt="{{ $category->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</div>
                                                @if($category->description)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($category->description, 50) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $category->sub_categories_count }} sub-categories
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ $category->sort_order }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button 
                                            wire:click="toggleStatus({{ $category->id }})"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                                   {{ $category->is_active 
                                                      ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                                      : 'bg-red-100 text-red-800 hover:bg-red-200' }}"
                                        >
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <button 
                                                wire:click="edit({{ $category->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="confirmDelete({{ $category->id }}, 'category')"
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
                                        No categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $categories->links() }}
                </div>
            </div>
        @else
            <!-- Sub-Categories Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sub-Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parent Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sort Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($subCategories as $subCategory)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($subCategory->image)
                                                <img class="h-10 w-10 rounded-lg object-cover mr-3" 
                                                     src="{{ Storage::url($subCategory->image) }}" 
                                                     alt="{{ $subCategory->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $subCategory->name }}</div>
                                                @if($subCategory->description)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($subCategory->description, 50) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $subCategory->category->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ $subCategory->sort_order }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button 
                                            wire:click="toggleSubCategoryStatus({{ $subCategory->id }})"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                                   {{ $subCategory->is_active 
                                                      ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                                      : 'bg-red-100 text-red-800 hover:bg-red-200' }}"
                                        >
                                            {{ $subCategory->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <button 
                                                wire:click="editSubCategory({{ $subCategory->id }})"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:click="confirmDelete({{ $subCategory->id }}, 'subcategory')"
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
                                        No sub-categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $subCategories->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Category Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Category' : 'Add New Category' }}
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
                                placeholder="Enter category name"
                            >
                            @error('name') <span class="text-red-500 text-sm">{{ $errors->first('name') }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                            <textarea 
                                wire:model="description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter category description"
                            ></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $errors->first('description') }}</span> @enderror
                        </div>

                        <!-- Image -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Image</label>
                            
                            <!-- Show current image if in edit mode -->
                            @if($editMode && $current_image)
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current Image:</p>
                                    <img src="{{ Storage::url($current_image) }}" 
                                         alt="Current image" 
                                         class="w-20 h-20 object-cover rounded-lg border border-gray-300">
                                </div>
                            @endif
                            
                            <input 
                                type="file" 
                                wire:model="image"
                                accept="image/*"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <p class="text-xs text-gray-500 mt-1">Upload image files (max 400KB)</p>
                            @error('image') <span class="text-red-500 text-sm">{{ $errors->first('image') }}</span> @enderror
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort Order</label>
                            <input 
                                type="number" 
                                wire:model="sort_order"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="0"
                            >
                            @error('sort_order') <span class="text-red-500 text-sm">{{ $errors->first('sort_order') }}</span> @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="is_active"
                                id="is_active"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                Active
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                {{ $editMode ? 'Update Category' : 'Create Category' }}
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

    <!-- Sub-Category Modal -->
    @if($showSubCategoryModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Sub-Category' : 'Add New Sub-Category' }}
                        </h2>
                        <button wire:click="closeSubCategoryModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="saveSubCategory" class="space-y-4">
                        <!-- Parent Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parent Category</label>
                            <select 
                                wire:model="category_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Select Parent Category</option>
                                @foreach($allCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-red-500 text-sm">{{ $errors->first('category_id') }}</span> @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                            <input 
                                type="text" 
                                wire:model="sub_name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter sub-category name"
                            >
                            @error('sub_name') <span class="text-red-500 text-sm">{{ $errors->first('sub_name') }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                            <textarea 
                                wire:model="sub_description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Enter sub-category description"
                            ></textarea>
                            @error('sub_description') <span class="text-red-500 text-sm">{{ $errors->first('sub_description') }}</span> @enderror
                        </div>

                        <!-- Image -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Image</label>
                            
                            <!-- Show current image if in edit mode -->
                            @if($editMode && $current_sub_image)
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current Image:</p>
                                    <img src="{{ Storage::url($current_sub_image) }}" 
                                         alt="Current image" 
                                         class="w-20 h-20 object-cover rounded-lg border border-gray-300">
                                </div>
                            @endif
                            
                            <input 
                                type="file" 
                                wire:model="sub_image"
                                accept="image/*"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <p class="text-xs text-gray-500 mt-1">Upload image files (max 400KB)</p>
                            @error('sub_image') <span class="text-red-500 text-sm">{{ $errors->first('sub_image') }}</span> @enderror
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort Order</label>
                            <input 
                                type="number" 
                                wire:model="sub_sort_order"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="0"
                            >
                            @error('sub_sort_order') <span class="text-red-500 text-sm">{{ $errors->first('sub_sort_order') }}</span> @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="sub_is_active"
                                id="sub_is_active"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="sub_is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                Active
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button 
                                type="submit"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium"
                            >
                                {{ $editMode ? 'Update Sub-Category' : 'Create Sub-Category' }}
                            </button>
                            <button 
                                type="button"
                                wire:click="closeSubCategoryModal"
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

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            Delete {{ $deleteType === 'category' ? 'Category' : 'Sub-Category' }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Are you sure you want to delete "{{ $deleteName }}"? 
                            @if($deleteType === 'category')
                                This action cannot be undone and will remove all associated sub-categories.
                            @else
                                This action cannot be undone and will remove all associated data.
                            @endif
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            wire:click="cancelDelete"
                            class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="confirmDeleteAction"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium"
                        >
                            Delete {{ $deleteType === 'category' ? 'Category' : 'Sub-Category' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
