@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)

@section('meta')
@if($page->meta_description)
    <meta name="description" content="{{ $page->meta_description }}">
@endif
@if($page->meta_keywords)
    <meta name="keywords" content="{{ $page->meta_keywords }}">
@endif
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                {{ $page->title }}
            </h1>
            @if($page->meta_description)
                <p class="text-lg text-gray-600">
                    {{ $page->meta_description }}
                </p>
            @endif
        </div>

        <!-- Page Content -->
        <div class="bg-white rounded-lg shadow-sm p-6 md:p-8">
            <div class="prose prose-lg prose-gray max-w-none
                       prose-headings:text-gray-900 prose-headings:font-semibold
                       prose-h1:text-3xl prose-h1:mb-6
                       prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4
                       prose-h3:text-xl prose-h3:mt-6 prose-h3:mb-3
                       prose-p:text-gray-700 prose-p:leading-relaxed prose-p:mb-4
                       prose-ul:text-gray-700 prose-ol:text-gray-700
                       prose-li:my-1
                       prose-strong:text-gray-900 prose-strong:font-semibold
                       prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline">
                {!! $page->content !!}
            </div>
        </div>

        <!-- Last Updated -->
        @if($page->updated_at)
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500">
                    Last updated: {{ $page->updated_at->format('F j, Y') }}
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
