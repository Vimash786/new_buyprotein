<?php

use App\Models\Policy;
use Livewire\Volt\Component;

new class extends Component
{
    public Policy $policy;

    public function mount(Policy $policy)
    {
        $this->policy = $policy;
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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">View Policy</h1>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $policyTypes[$policy->type] ?? $policy->type }}</p>
        </div>

        <!-- Policy Details -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow">
            <div class="p-6">
                <!-- Header Info -->
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $policy->title }}</h2>
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>Type: {{ $policyTypes[$policy->type] ?? $policy->type }}</span>
                            <span>•</span>
                            <span>Last updated: {{ $policy->updated_at->format('M d, Y H:i') }}</span>
                            @if($policy->updatedBy)
                                <span>•</span>
                                <span>Updated by: {{ $policy->updatedBy->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $policy->is_active 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                            {{ $policy->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <a 
                            href="{{ route('policies.edit', $policy) }}"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                        >
                            Edit Policy
                        </a>
                    </div>
                </div>

                <!-- SEO Information -->
                @if($policy->meta_title || $policy->meta_description)
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">SEO Information</h3>
                        @if($policy->meta_title)
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Meta Title:</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $policy->meta_title }}</p>
                            </div>
                        @endif
                        @if($policy->meta_description)
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Meta Description:</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $policy->meta_description }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Policy Content -->
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    {!! $policy->content !!}
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex gap-3">
            <a 
                href="{{ route('policies.edit', $policy) }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium"
            >
                Edit Policy
            </a>
            <a 
                href="{{ route('policies.manage') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium"
            >
                Back to Policies
            </a>
        </div>
    </div>
</div>
