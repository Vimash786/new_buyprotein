<?php

use App\Models\Policy;
use Livewire\Volt\Component;

new class extends Component
{
    public Policy $policy;
    
    public $type = '';
    public $title = '';
    public $content = '';
    public $is_active = true;
    public $meta_title = '';
    public $meta_description = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'is_active' => 'boolean',
        'meta_title' => 'nullable|string|max:255',
        'meta_description' => 'nullable|string|max:500',
    ];

    public function mount(Policy $policy)
    {
        $this->policy = $policy;
        $this->type = $policy->type;
        $this->title = $policy->title;
        $this->content = $policy->content;
        $this->is_active = $policy->is_active;
        $this->meta_title = $policy->meta_title;
        $this->meta_description = $policy->meta_description;
    }

    public function save()
    {
        $this->validate();

        $this->policy->update([
            'title' => $this->title,
            'content' => $this->content,
            'is_active' => $this->is_active,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('message', 'Policy updated successfully!');
        
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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Policy</h1>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">Update {{ $policyTypes[$policy->type] ?? $policy->type }} policy</p>
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
                <!-- Policy Type (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Policy Type
                    </label>
                    <div class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-white">
                        {{ $policyTypes[$policy->type] ?? $policy->type }}
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Policy type cannot be changed after creation</p>
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
                    <textarea 
                        wire:model="content"
                        rows="15"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                        placeholder="Enter policy content (HTML supported)"
                    >{{ $content }}</textarea>
                    @error('content') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">You can use HTML tags for formatting</p>
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

                <!-- Last Updated Info -->
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <div>Last updated: {{ $policy->updated_at->format('M d, Y H:i') }}</div>
                        @if($policy->updatedBy)
                            <div>Updated by: {{ $policy->updatedBy->name }}</div>
                        @endif
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"
                    >
                        Update Policy
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


