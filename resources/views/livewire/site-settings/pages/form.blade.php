<?php

use App\Models\SitePage;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $pageId = null;
    public $page = null;
    public $editMode = false;
    
    // Form fields
    public $page_type = '';
    public $title = '';
    public $slug = '';
    public $content = '';
    public $meta_title = '';
    public $meta_description = '';
    public $meta_keywords = '';
    public $status = true;

    protected $rules = [
        'page_type' => 'required|string|in:about-us,terms-conditions,shipping-policy,privacy-policy,return-policy',
        'title' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'content' => 'required|string',
        'meta_title' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:500',
        'meta_keywords' => 'nullable|string|max:500',
        'status' => 'boolean',
    ];

    protected $messages = [
        'page_type.required' => 'Please select a page type.',
        'page_type.in' => 'Invalid page type selected.',
        'title.required' => 'Page title is required.',
        'slug.required' => 'Page slug is required.',
        'content.required' => 'Page content is required.',
    ];

    public function mount($id = null, $type = null)
    {
        if ($id) {
            $this->pageId = $id;
            $this->page = SitePage::findOrFail($id);
            $this->editMode = true;
            $this->loadPageData();
        } else {
            $this->editMode = false;
            $this->page_type = $type ?? '';
            $this->status = true;
        }
    }

    public function hydrate()
    {
        if ($this->editMode && $this->content) {
            $this->dispatch('contentUpdated', $this->content);
        }
    }

    private function loadPageData()
    {
        $this->page_type = $this->page->page_type;
        $this->title = $this->page->title;
        $this->slug = $this->page->slug;
        $this->content = $this->page->content;
        $this->meta_title = $this->page->meta_title;
        $this->meta_description = $this->page->meta_description;
        $this->meta_keywords = $this->page->meta_keywords;
        $this->status = $this->page->status;
        
        // Force Livewire to refresh the component after loading data
        $this->dispatch('$refresh');
    }

    public function with()
    {
        return [
            'pageTypes' => SitePage::PAGE_TYPES,
        ];
    }

    public function save()
    {
        // Update validation rules for edit mode
        if ($this->editMode) {
            $this->rules['slug'] = 'required|string|max:255|unique:site_pages,slug,' . $this->pageId;
            $this->rules['page_type'] = 'required|string|in:about-us,terms-conditions,shipping-policy,privacy-policy,return-policy|unique:site_pages,page_type,' . $this->pageId;
        } else {
            $this->rules['page_type'] = 'required|string|in:about-us,terms-conditions,shipping-policy,privacy-policy,return-policy|unique:site_pages,page_type';
            $this->rules['slug'] = 'required|string|max:255|unique:site_pages,slug';
        }

        $this->validate();

        // Generate slug if empty
        if (empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }

        $data = [
            'page_type' => $this->page_type,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'status' => $this->status,
        ];

        if ($this->editMode) {
            $data['updated_by'] = Auth::id();
            $this->page->update($data);
            session()->flash('message', 'Page updated successfully!');
        } else {
            $data['created_by'] = Auth::id();
            SitePage::create($data);
            session()->flash('message', 'Page created successfully!');
        }

        return redirect()->route('site-pages.manage');
    }

    public function cancel()
    {
        return redirect()->route('site-pages.manage');
    }

    public function updatedTitle()
    {
        if (!$this->editMode) {
            $this->slug = Str::slug($this->title);
        }
    }
}; ?>

<!-- Include Quill CSS and JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $editMode ? 'Edit Page' : 'Create New Page' }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ $editMode ? 'Update the details of your site page' : 'Add a new content page to your website' }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <button 
                        wire:click="cancel"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-600"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="save"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        {{ $editMode ? 'Update Page' : 'Create Page' }}
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

        <!-- Debug Info (temporary) -->
        @if($editMode)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                <strong>Debug Info:</strong> 
                Edit Mode: {{ $editMode ? 'Yes' : 'No' }} | 
                Page ID: {{ $pageId }} | 
                Title: "{{ $title }}" | 
                Content Length: {{ strlen($content) }}
            </div>
        @endif

        <!-- Form -->
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information Card -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
                            
                            <div class="space-y-4">
                                <!-- Page Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Page Type <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        wire:model="page_type"
                                        wire:key="page_type_{{ $editMode ? $pageId : 'new' }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        @if($editMode) disabled @endif
                                    >
                                        <option value="">Select Page Type</option>
                                        @foreach($pageTypes as $key => $name)
                                            <option value="{{ $key }}" {{ $page_type == $key ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('page_type') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                </div>

                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Title <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model.live="title"
                                        wire:key="title_{{ $editMode ? $pageId : 'new' }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="Enter page title"
                                        value="{{ $title }}"
                                    >
                                    @error('title') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                </div>

                                <!-- Slug -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Slug <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="slug"
                                        wire:key="slug_{{ $editMode ? $pageId : 'new' }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="page-slug"
                                        value="{{ $slug }}"
                                    >
                                    @error('slug') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL-friendly version of the title</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Card -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Content</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Page Content <span class="text-red-500">*</span>
                                </label>
                                
                                <!-- Hidden input to store the content -->
                                <input type="hidden" wire:model="content" id="content-input">
                                
                                <!-- Quill Editor Container -->
                                <div wire:ignore>
                                    <div id="quill-editor" style="height: 400px;" class="bg-white dark:bg-zinc-800"></div>
                                </div>
                                
                                @error('content') 
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                @enderror
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use the rich text editor to format your content</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Publish Status -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Status</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="status"
                                            wire:key="status_{{ $editMode ? $pageId : 'new' }}"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            {{ $status ? 'checked' : '' }}
                                        >
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">When active, this page will be visible on the website</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Settings</h3>
                            
                            <div class="space-y-4">
                                <!-- Meta Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Meta Title
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="meta_title"
                                        wire:key="meta_title_{{ $editMode ? $pageId : 'new' }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="SEO meta title"
                                        maxlength="60"
                                        value="{{ $meta_title }}"
                                    >
                                    @error('meta_title') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recommended: 50-60 characters</p>
                                </div>

                                <!-- Meta Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Meta Description
                                    </label>
                                    <textarea 
                                        wire:model="meta_description"
                                        wire:key="meta_description_{{ $editMode ? $pageId : 'new' }}"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="SEO meta description"
                                        maxlength="160"
                                    >{{ $meta_description }}</textarea>
                                    @error('meta_description') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recommended: 150-160 characters</p>
                                </div>

                                <!-- Meta Keywords -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Meta Keywords
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="meta_keywords"
                                        wire:key="meta_keywords_{{ $editMode ? $pageId : 'new' }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                        placeholder="keyword1, keyword2, keyword3"
                                        value="{{ $meta_keywords }}"
                                    >
                                    @error('meta_keywords') 
                                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span> 
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Separate keywords with commas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($editMode && $page)
                    <!-- Page Info -->
                    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Page Information</h3>
                            
                            <div class="space-y-3 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Created:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $page->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Updated:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $page->updated_at->format('M d, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Created by:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $page->creator->name ?? 'N/A' }}</span>
                                </div>
                                @if($page->updater)
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Updated by:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $page->updater->name }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                <button 
                    type="button"
                    wire:click="cancel"
                    class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-600"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ $editMode ? 'Update Page' : 'Create Page' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let quill;
    let isInitialized = false;
    
    // Initialize Quill editor
    function initializeQuill() {
        if (isInitialized) return;
        
        quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'Enter your page content here...'
        });

        isInitialized = true;
        
        // Set initial content
        loadInitialContent();

        // Update Livewire when Quill content changes
        quill.on('text-change', function() {
            var content = quill.root.innerHTML;
            @this.set('content', content);
            document.getElementById('content-input').value = content;
        });
    }

    // Function to load initial content
    function loadInitialContent() {
        // Use a delay to ensure Livewire has fully loaded
        setTimeout(function() {
            var initialContent = @json($content ?? '');
            console.log('Loading initial content:', initialContent.substring(0, 100) + '...');
            
            if (initialContent && quill) {
                quill.root.innerHTML = initialContent;
            }
        }, 100);
    }

    // Initialize the editor
    initializeQuill();

    // Listen for Livewire updates
    if (typeof Livewire !== 'undefined') {
        Livewire.on('contentUpdated', (content) => {
            console.log('Content updated via Livewire:', content.substring(0, 100) + '...');
            if (quill && quill.root.innerHTML !== content) {
                quill.root.innerHTML = content;
            }
        });

        // Also listen for the refresh event
        Livewire.on('$refresh', () => {
            setTimeout(loadInitialContent, 50);
        });
    }
});
</script>
