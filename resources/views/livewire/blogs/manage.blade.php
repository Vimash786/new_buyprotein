<?php

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $statusFilter = '';
    public $categoryFilter = '';
    public $showModal = false;
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $showStatusModal = false;
    public $showFeaturedModal = false;
    public $editMode = false;
    public $blogId = null;
    public $viewingBlog = null;
    public $blogToDelete = null;
    public $blogToToggleStatus = null;
    public $blogToToggleFeatured = null;
    
    // Form fields
    public $title = '';
    public $slug = '';
    public $excerpt = '';
    public $content = '';
    public $featured_image = '';
    public $featured_image_file = null;
    public $meta_title = '';
    public $meta_description = '';
    public $tags = [];
    public $tagInput = '';
    public $is_featured = false;
    public $status = 'draft';
    public $published_at = '';
    public $category_id = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:blogs,slug',
        'excerpt' => 'required|string|max:1000',
        'content' => 'required|string',
        'featured_image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        'meta_title' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:500',
        'status' => 'required|in:draft,published,archived',
        'published_at' => 'nullable|date',
        'category_id' => 'nullable|exists:categories,id',
    ];

    public function with()
    {
        $query = Blog::with(['author', 'category']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('excerpt', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        return [
            'blogs' => $query->latest()->paginate(10),
            'categories' => Category::all(),
            'totalBlogs' => Blog::count(),
            'publishedBlogs' => Blog::where('status', 'published')->count(),
            'draftBlogs' => Blog::where('status', 'draft')->count(),
            'featuredBlogs' => Blog::where('is_featured', true)->count(),
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
        $blog = Blog::with(['author', 'category'])->findOrFail($id);
        $this->viewingBlog = $blog;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingBlog = null;
    }

    public function confirmDelete($id)
    {
        $this->blogToDelete = Blog::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->blogToDelete = null;
    }

    public function confirmStatusToggle($id)
    {
        $this->blogToToggleStatus = Blog::findOrFail($id);
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->blogToToggleStatus = null;
    }

    public function confirmFeaturedToggle($id)
    {
        $this->blogToToggleFeatured = Blog::findOrFail($id);
        $this->showFeaturedModal = true;
    }

    public function closeFeaturedModal()
    {
        $this->showFeaturedModal = false;
        $this->blogToToggleFeatured = null;
    }

    public function resetForm()
    {
        $this->blogId = null;
        $this->title = '';
        $this->slug = '';
        $this->excerpt = '';
        $this->content = '';
        $this->featured_image = '';
        $this->featured_image_file = null;
        $this->meta_title = '';
        $this->meta_description = '';
        $this->tags = [];
        $this->tagInput = '';
        $this->is_featured = false;
        $this->status = 'draft';
        $this->published_at = '';
        $this->category_id = '';
        $this->resetValidation();
    }

    public function updatedTitle()
    {
        if (!$this->editMode) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function addTag()
    {
        if (!empty($this->tagInput)) {
            $tag = trim($this->tagInput);
            if (!in_array($tag, $this->tags)) {
                $this->tags[] = $tag;
            }
            $this->tagInput = '';
        }
    }

    public function removeTag($index)
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save()
    {
        $rules = $this->rules;
        
        // Modify slug validation for edit mode
        if ($this->editMode) {
            $rules['slug'] = 'required|string|max:255|unique:blogs,slug,' . $this->blogId;
        }

        $this->validate($rules);

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'tags' => $this->tags,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'category_id' => $this->category_id ?: null,
        ];

        // Handle published_at
        if ($this->status === 'published' && !$this->published_at) {
            $data['published_at'] = now();
        } elseif ($this->status === 'published' && $this->published_at) {
            $data['published_at'] = $this->published_at;
        } elseif ($this->status === 'draft') {
            $data['published_at'] = null;
        }

        // Handle featured image upload
        if ($this->featured_image_file) {
            // Delete old image if editing
            if ($this->editMode && $this->featured_image) {
                Storage::disk('public')->delete($this->featured_image);
            }
            
            $fileName = time() . '_' . $this->featured_image_file->getClientOriginalName();
            $filePath = $this->featured_image_file->storeAs('blogs', $fileName, 'public');
            $data['featured_image'] = $filePath;
        }

        if ($this->editMode) {
            $blog = Blog::findOrFail($this->blogId);
            $blog->update($data);
            session()->flash('message', 'Blog updated successfully!');
        } else {
            $data['author_id'] = auth()->id();
            Blog::create($data);
            session()->flash('message', 'Blog created successfully!');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        
        $this->blogId = $blog->id;
        $this->title = $blog->title;
        $this->slug = $blog->slug;
        $this->excerpt = $blog->excerpt;
        $this->content = $blog->content;
        $this->featured_image = $blog->featured_image;
        $this->featured_image_file = null;
        $this->meta_title = $blog->meta_title;
        $this->meta_description = $blog->meta_description;
        $this->tags = $blog->tags ?? [];
        $this->is_featured = $blog->is_featured;
        $this->status = $blog->status;
        $this->published_at = $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '';
        $this->category_id = $blog->category_id;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function delete($id = null)
    {
        $blog = $this->blogToDelete ?? Blog::findOrFail($id);
        
        // Delete associated image
        if ($blog->featured_image) {
            Storage::disk('public')->delete($blog->featured_image);
        }
        
        $blog->delete();
        session()->flash('message', 'Blog deleted successfully!');
        
        $this->closeDeleteModal();
    }

    public function toggleStatus($id = null)
    {
        $blog = $this->blogToToggleStatus ?? Blog::findOrFail($id);
        
        if ($blog->status === 'published') {
            $blog->update(['status' => 'draft', 'published_at' => null]);
        } else {
            $blog->update(['status' => 'published', 'published_at' => now()]);
        }
        
        session()->flash('message', 'Blog status updated successfully!');
        
        $this->closeStatusModal();
    }

    public function toggleFeatured($id = null)
    {
        $blog = $this->blogToToggleFeatured ?? Blog::findOrFail($id);
        $blog->update(['is_featured' => !$blog->is_featured]);
        
        session()->flash('message', 'Blog featured status updated successfully!');
        
        $this->closeFeaturedModal();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Blog Management</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Create and manage blog posts and articles</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Blogs</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalBlogs }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Published</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $publishedBlogs }}</p>
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Drafts</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $draftBlogs }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Featured</h3>
                        <p class="text-3xl font-bold text-purple-600">{{ $featuredBlogs }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
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
                                placeholder="Search blogs..."
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
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>

                        <!-- Category Filter -->
                        <select wire:model.live="categoryFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
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
                        Add Blog
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

        <!-- Blogs Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Blog</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stats</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Published</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($blogs as $blog)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-20 h-12">
                                            @if($blog->featured_image)
                                                <img src="{{ Storage::url($blog->featured_image) }}" alt="{{ $blog->title }}" class="w-20 h-12 rounded object-cover">
                                            @else
                                                <div class="w-20 h-12 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($blog->title, 50) }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($blog->excerpt, 80) }}</div>
                                            @if($blog->is_featured)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 mt-1">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                    </svg>
                                                    Featured
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $blog->author->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $blog->category->name ?? 'Uncategorized' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="confirmStatusToggle({{ $blog->id }})"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                               {{ $blog->status === 'published' 
                                                  ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/70' 
                                                  : ($blog->status === 'draft' 
                                                     ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/70'
                                                     : 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-900/70') }}"
                                    >
                                        {{ ucfirst($blog->status) }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                {{ $blog->views_count }}
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                                {{ $blog->likes_count }}
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                                {{ $blog->comments_count }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $blog->published_at ? $blog->published_at->format('M d, Y') : 'Not published' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button 
                                            wire:click="openViewModal({{ $blog->id }})"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="edit({{ $blog->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            wire:click="confirmFeaturedToggle({{ $blog->id }})"
                                            class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                                            title="Toggle Featured"
                                        >
                                            @if($blog->is_featured)
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            @endif
                                        </button>
                                        <button 
                                            wire:click="confirmDelete({{ $blog->id }})"
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
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No blogs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $blogs->links() }}
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
                            {{ $editMode ? 'Edit Blog' : 'Create New Blog' }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                                    <input 
                                        type="text" 
                                        wire:model="title"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="Enter blog title"
                                    >
                                    @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Slug -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug</label>
                                    <input 
                                        type="text" 
                                        wire:model="slug"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="blog-url-slug"
                                    >
                                    @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                    <select 
                                        wire:model="category_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    >
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                                    <select 
                                        wire:model="status"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Published At -->
                                @if($status === 'published')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Published At</label>
                                        <input 
                                            type="datetime-local" 
                                            wire:model="published_at"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        >
                                        @error('published_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <!-- Featured -->
                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="is_featured"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        >
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Blog</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <!-- Featured Image -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Featured Image</label>
                                    
                                    @if($editMode && $featured_image)
                                        <div class="mb-3 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <img src="{{ Storage::url($featured_image) }}" alt="Current image" class="w-20 h-12 rounded object-cover">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">Current featured image</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <input 
                                        type="file" 
                                        wire:model="featured_image_file"
                                        accept="image/*"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                    >
                                    @error('featured_image_file') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    
                                    @if($featured_image_file)
                                        <div class="mt-2">
                                            <img src="{{ $featured_image_file->temporaryUrl() }}" alt="Preview" class="w-32 h-20 rounded object-cover">
                                        </div>
                                    @endif
                                </div>

                                <!-- Tags -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($tags as $index => $tag)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                {{ $tag }}
                                                <button type="button" wire:click="removeTag({{ $index }})" class="ml-1 text-blue-600 hover:text-blue-800">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="flex">
                                        <input 
                                            type="text" 
                                            wire:model="tagInput"
                                            wire:keydown.enter.prevent="addTag"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                            placeholder="Add a tag"
                                        >
                                        <button type="button" wire:click="addTag" class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">
                                            Add
                                        </button>
                                    </div>
                                </div>

                                <!-- Meta Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Title</label>
                                    <input 
                                        type="text" 
                                        wire:model="meta_title"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="SEO meta title"
                                    >
                                    @error('meta_title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Meta Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Description</label>
                                    <textarea 
                                        wire:model="meta_description"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="SEO meta description"
                                    ></textarea>
                                    @error('meta_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Excerpt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Excerpt</label>
                            <textarea 
                                wire:model="excerpt"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Brief description of the blog post"
                            ></textarea>
                            @error('excerpt') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</label>
                            <textarea 
                                wire:model="content"
                                rows="10"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                placeholder="Write your blog content here..."
                            ></textarea>
                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end gap-3 pt-4">
                            <button 
                                type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                            >
                                {{ $editMode ? 'Update Blog' : 'Create Blog' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- View Modal -->
    @if($showViewModal && $viewingBlog)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $viewingBlog->title }}
                        </h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Blog Image -->
                    @if($viewingBlog->featured_image)
                        <div class="mb-6">
                            <img src="{{ Storage::url($viewingBlog->featured_image) }}" alt="{{ $viewingBlog->title }}" class="w-full h-64 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif

                    <!-- Blog Meta -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Blog Information</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Author</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingBlog->author->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Category</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingBlog->category->name ?? 'Uncategorized' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</label>
                                    <p class="text-gray-900 dark:text-white">{{ ucfirst($viewingBlog->status) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Published At</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingBlog->published_at ? $viewingBlog->published_at->format('M d, Y g:i A') : 'Not published' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistics</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Views</label>
                                    <p class="text-gray-900 dark:text-white">{{ number_format($viewingBlog->views_count) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Likes</label>
                                    <p class="text-gray-900 dark:text-white">{{ number_format($viewingBlog->likes_count) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Comments</label>
                                    <p class="text-gray-900 dark:text-white">{{ number_format($viewingBlog->comments_count) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Reading Time</label>
                                    <p class="text-gray-900 dark:text-white">{{ $viewingBlog->reading_time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    @if($viewingBlog->tags && count($viewingBlog->tags) > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($viewingBlog->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Excerpt -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Excerpt</h3>
                        <p class="text-gray-700 dark:text-gray-300">{{ $viewingBlog->excerpt }}</p>
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Content</h3>
                        <div class="prose dark:prose-invert max-w-none">
                            {!! nl2br(e($viewingBlog->content)) !!}
                        </div>
                    </div>

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
    @if($showDeleteModal && $blogToDelete)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">Delete Blog Post</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-6">
                        Are you sure you want to delete "<strong>{{ $blogToDelete->title }}</strong>"? This action cannot be undone and will permanently remove the blog post and its associated image.
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Author:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $blogToDelete->author->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($blogToDelete->status) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Views:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($blogToDelete->views_count) }}</span>
                            </div>
                        </div>
                    </div>
                    
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
                            Delete Blog
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status Change Confirmation Modal -->
    @if($showStatusModal && $blogToToggleStatus)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto 
                                {{ $blogToToggleStatus->status === 'published' ? 'bg-yellow-100 dark:bg-yellow-900/50' : 'bg-green-100 dark:bg-green-900/50' }} 
                                rounded-full mb-4">
                        @if($blogToToggleStatus->status === 'published')
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        {{ $blogToToggleStatus->status === 'published' ? 'Change to Draft' : 'Publish Blog' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-4">
                        Are you sure you want to {{ $blogToToggleStatus->status === 'published' ? 'change to draft' : 'publish' }} 
                        "<strong>{{ $blogToToggleStatus->title }}</strong>"?
                        @if($blogToToggleStatus->status !== 'published')
                            <br><span class="text-sm text-gray-500">This will make the blog visible to readers.</span>
                        @endif
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Author:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $blogToToggleStatus->author->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Category:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $blogToToggleStatus->category->name ?? 'Uncategorized' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Current Status:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($blogToToggleStatus->status) }}</span>
                            </div>
                        </div>
                    </div>
                    
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
                                   {{ $blogToToggleStatus->status === 'published' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} 
                                   rounded-lg"
                        >
                            {{ $blogToToggleStatus->status === 'published' ? 'Change to Draft' : 'Publish Blog' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Featured Toggle Confirmation Modal -->
    @if($showFeaturedModal && $blogToToggleFeatured)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto 
                                {{ $blogToToggleFeatured->is_featured ? 'bg-gray-100 dark:bg-gray-900/50' : 'bg-yellow-100 dark:bg-yellow-900/50' }} 
                                rounded-full mb-4">
                        @if($blogToToggleFeatured->is_featured)
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-yellow-600 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                        {{ $blogToToggleFeatured->is_featured ? 'Remove from Featured' : 'Mark as Featured' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-center mb-4">
                        Are you sure you want to {{ $blogToToggleFeatured->is_featured ? 'remove' : 'mark' }} 
                        "<strong>{{ $blogToToggleFeatured->title }}</strong>" {{ $blogToToggleFeatured->is_featured ? 'from featured blogs' : 'as a featured blog' }}?
                        @if(!$blogToToggleFeatured->is_featured)
                            <br><span class="text-sm text-gray-500">Featured blogs get special highlighting and visibility.</span>
                        @endif
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Author:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $blogToToggleFeatured->author->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($blogToToggleFeatured->status) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Views:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ number_format($blogToToggleFeatured->views_count) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="closeFeaturedModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-zinc-700 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="toggleFeatured"
                            class="px-4 py-2 text-sm font-medium text-white 
                                   {{ $blogToToggleFeatured->is_featured ? 'bg-gray-600 hover:bg-gray-700' : 'bg-yellow-600 hover:bg-yellow-700' }} 
                                   rounded-lg"
                        >
                            {{ $blogToToggleFeatured->is_featured ? 'Remove Featured' : 'Mark as Featured' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
