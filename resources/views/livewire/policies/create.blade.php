<?php

use App\Models\Policy;
use Livewire\Volt\Component;

new class extends Component
{
    public $policyId = null;
    public $type = '';
    public $title = '';
    public $content = '';
    public $is_active = true;
    public $meta_title = '';
    public $meta_description = '';

    public function mount($id = null, Policy $policy = null)
    {
        // Handle both route patterns: /policies/create and /policies/{policy}/edit
        if ($policy && $policy->exists) {
            $this->policyId = $policy->id;
            $this->type = $policy->type;
            $this->title = $policy->title;
            $this->content = $policy->content;
            $this->is_active = $policy->is_active;
            $this->meta_title = $policy->meta_title;
            $this->meta_description = $policy->meta_description;
        } elseif ($id) {
            $this->policyId = $id;
            $policy = Policy::findOrFail($id);
            
            $this->type = $policy->type;
            $this->title = $policy->title;
            $this->content = $policy->content;
            $this->is_active = $policy->is_active;
            $this->meta_title = $policy->meta_title;
            $this->meta_description = $policy->meta_description;
        }
    }

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
        if (!$this->policyId) {
            $rules['type'] = 'required|in:about-us,terms-conditions,shipping-policy,privacy-policy,return-policy|unique:policies,type';
        }

        return $rules;
    }

    protected $messages = [
        'type.unique' => 'A policy of this type already exists. Please edit the existing policy instead.',
    ];

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

        if ($this->policyId) {
            // Update existing policy (don't update type)
            $policy = Policy::findOrFail($this->policyId);
            $policy->update($data);
            $message = 'Policy updated successfully!';
        } else {
            // Create new policy (include type)
            $data['type'] = $this->type;
            Policy::create($data);
            $message = 'Policy created successfully!';
        }

        session()->flash('message', $message);
        
        return redirect()->route('policies.manage');
    }

    public function cancel()
    {
        return redirect()->route('policies.manage');
    }

    public function with()
    {
        return [
            'policyTypes' => Policy::TYPES,
            'isEditing' => $this->policyId !== null,
            'currentPolicy' => $this->policyId ? Policy::find($this->policyId) : null,
        ];
    }
}; ?>




<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a 
                    href="{{ route('policies.manage') }}"
                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $isEditing ? 'Edit Policy' : 'Create New Policy' }}
                </h1>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $isEditing ? 'Update the policy information below' : 'Create a new policy page for your website' }}
            </p>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Form -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
            <form wire:submit="save" class="p-6 space-y-6">

                <!-- Policy Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Policy Type <span class="text-red-500">*</span>
                    </label>
                    @if($isEditing)
                        <div class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-white">
                            {{ $currentPolicy ? ($policyTypes[$currentPolicy->type] ?? $currentPolicy->type) : $type }}
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
                    @error('type') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
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
                    @error('title') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Content <span class="text-red-500">*</span>
                    </label>

                    <div class="mb-4">
                        <div id="editor" class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                            
                        </div>
                    </div>  

                    
                    
                    <!-- Hidden input for Livewire -->
                    <input type="hidden" wire:model="content" id="content-input" value="{{ $content }}">
                    
                    @error('content') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
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
                        @error('meta_title') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
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
                        @error('meta_description') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
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
                    @error('is_active') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Last Updated Info (Edit Mode Only) -->
                @if($isEditing && $currentPolicy)
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div>Last updated: {{ $currentPolicy->updated_at->format('M d, Y H:i') }}</div>
                            @if($currentPolicy->updatedBy)
                                <div>Updated by: {{ $currentPolicy->updatedBy->name }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Buttons -->
                <div class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        type="submit"
                        onclick="syncQuillContent()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"
                    >
                        {{ $isEditing ? 'Update Policy' : 'Create Policy' }}
                    </button>
                    <button 
                        type="button"
                        wire:click="cancel"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quill Editor CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<!-- Custom Quill Dark Mode CSS -->
<style>
  /* Dark mode styles for Quill editor */
  .dark .ql-toolbar.ql-snow {
    border-color: #4b5563;
    background-color: #374151;
  }
  
  .dark .ql-toolbar.ql-snow .ql-stroke {
    stroke: #d1d5db;
  }
  
  .dark .ql-toolbar.ql-snow .ql-fill {
    fill: #d1d5db;
  }
  
  .dark .ql-toolbar.ql-snow .ql-picker-label {
    color: #d1d5db;
  }
  
  .dark .ql-container.ql-snow {
    border-color: #4b5563;
    background-color: #1f2937;
    color: #f9fafb;
  }
  
  .dark .ql-editor {
    color: #f9fafb;
  }
  
  .dark .ql-editor::before {
    color: #9ca3af;
  }
  
  /* Hover states */
  .dark .ql-toolbar.ql-snow .ql-picker-label:hover {
    color: #ffffff;
  }
  
  .dark .ql-toolbar.ql-snow button:hover {
    color: #ffffff;
  }
  
  .dark .ql-toolbar.ql-snow button:hover .ql-stroke {
    stroke: #ffffff;
  }
  
  .dark .ql-toolbar.ql-snow button:hover .ql-fill {
    fill: #ffffff;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<!-- Initialize Quill editor -->
<script>
  let quill;
  let quillContent = '';
  
  // Function to initialize or reinitialize Quill editor
  function initializeQuillEditor() {
    const editorElement = document.getElementById('editor');
    
    if (!editorElement) {
      return;
    }
    
    // Destroy existing editor if it exists
    if (quill) {
      // Save current content before destroying
      try {
        quillContent = quill.root.innerHTML;
      } catch (e) {
        // Editor might already be destroyed
      }
      quill = null;
    }
    
    // Create new Quill instance
    quill = new Quill('#editor', {
      theme: 'snow',
      placeholder: 'Enter policy content...',
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline', 'strike'],
          ['blockquote', 'code-block'],
          [{ 'header': 1 }, { 'header': 2 }],
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          [{ 'script': 'sub'}, { 'script': 'super' }],
          [{ 'indent': '-1'}, { 'indent': '+1' }],
          [{ 'direction': 'rtl' }],
          [{ 'size': ['small', false, 'large', 'huge'] }],
          [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
          [{ 'color': [] }, { 'background': [] }],
          [{ 'font': [] }],
          [{ 'align': [] }],
          ['clean']
        ]
      }
    });

    // Function to load content into Quill
    function loadContentIntoQuill() {
      const hiddenInput = document.getElementById('content-input');
      let contentToLoad = '';
      
      // Priority: saved content > hidden input value > empty
      if (quillContent && quillContent.trim() !== '' && quillContent !== '<p><br></p>') {
        contentToLoad = quillContent;
      } else if (hiddenInput && hiddenInput.value && hiddenInput.value.trim() !== '') {
        contentToLoad = hiddenInput.value;
      }
      
      if (contentToLoad) {
        quill.root.innerHTML = contentToLoad;
      }
    }

    // Load initial content
    loadContentIntoQuill();

    // Update hidden input and save content on text change
    quill.on('text-change', function() {
      const content = quill.root.innerHTML;
      quillContent = content; // Save to global variable
      
      const hiddenInput = document.getElementById('content-input');
      if (hiddenInput) {
        hiddenInput.value = content;
      }
      
      // Hide validation error immediately when typing
      const errorElement = document.querySelector('.text-red-500');
      if (errorElement && errorElement.textContent.includes('content field is required')) {
        errorElement.style.display = 'none';
      }
    });
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill on page load
    initializeQuillEditor();
    
    // Listen for Livewire updates and reinitialize if needed
    document.addEventListener('livewire:init', function () {
      // Reinitialize after Livewire updates
      Livewire.hook('morph.updated', () => {
        setTimeout(() => {
          const editorElement = document.getElementById('editor');
          if (editorElement && (!quill || !quill.root.isConnected)) {
            initializeQuillEditor();
          }
        }, 100);
      });
    });
  });

  // Function to sync content before form submission
  function syncQuillContent() {
    if (quill) {
      const content = quill.root.innerHTML;
      quillContent = content;
      const hiddenInput = document.getElementById('content-input');
      if (hiddenInput) {
        hiddenInput.value = content;
      }
      @this.set('content', content);
    } else if (quillContent) {
      // If editor was destroyed but we have saved content
      const hiddenInput = document.getElementById('content-input');
      if (hiddenInput) {
        hiddenInput.value = quillContent;
      }
      @this.set('content', quillContent);
    }
  }
</script>
