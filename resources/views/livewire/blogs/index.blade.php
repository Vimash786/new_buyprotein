<?php

use App\Models\Blog;
use App\Models\Category;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';

    public function with()
    {
        $query = Blog::with(['author', 'category'])
                    ->published()
                    ->latest('published_at');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->categoryFilter) {
            $query->byCategory($this->categoryFilter);
        }

        return [
            'blogs' => $query->paginate(9),
            'categories' => Category::whereHas('blogs', function ($q) {
                $q->published();
            })->get(),
            'featuredBlogs' => Blog::published()->featured()->latest('published_at')->limit(3)->get(),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800">
    <!-- Hero Section with Featured Blogs -->
    @if($featuredBlogs->count() > 0)
        <div class="bg-white dark:bg-zinc-900 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">BuyProtein Blog</h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300">Discover the latest insights on nutrition, fitness, and wellness</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach($featuredBlogs as $blog)
                        <article class="bg-gray-50 dark:bg-zinc-800 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                            @if($blog->featured_image)
                                <img src="{{ $blog->featured_image_url }}" alt="{{ $blog->title }}" class="w-full h-48 object-cover">
                            @endif
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                        Featured
                                    </span>
                                    @if($blog->category)
                                        <span class="text-sm text-blue-600 dark:text-blue-400">{{ $blog->category->name }}</span>
                                    @endif
                                </div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ $blog->title }}</h2>
                                <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $blog->excerpt }}</p>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $blog->author->name }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $blog->published_at->format('M d, Y') }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $blog->reading_time }}</span>
                                    </div>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Blog Listing Section -->
    <div class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Search and Filter -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Latest Articles</h2>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="search"
                                placeholder="Search articles..."
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <select wire:model.live="categoryFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Blog Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($blogs as $blog)
                    <article class="bg-white dark:bg-zinc-900 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                        @if($blog->featured_image)
                            <img src="{{ $blog->featured_image_url }}" alt="{{ $blog->title }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        @endif
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                @if($blog->category)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                        {{ $blog->category->name }}
                                    </span>
                                @endif
                                <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $blog->views_count }}
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        {{ $blog->likes_count }}
                                    </span>
                                </div>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ $blog->title }}</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">{{ Str::limit($blog->excerpt, 120) }}</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $blog->author->name }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ $blog->published_at->format('M d, Y') }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ $blog->reading_time }}</span>
                                </div>
                                <a href="#" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full text-center py-12">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No articles found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($blogs->hasPages())
                <div class="mt-12">
                    {{ $blogs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
