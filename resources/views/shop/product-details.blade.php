@extends('layouts.app')

@section('content')
    <div class="section-seperator bg_light-1">
        <div class="container">
            <hr class="section-seperator">
        </div>
    </div>
    @push('styles')
    <style>
        .variant-container {
            margin-top: 1.5rem;
            width: 100%;
        }
        .variant-group {
            margin-bottom: 1.5rem;
        }
        .variant-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.75rem;
            margin-left: 0.25rem;
        }
        .variant-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .variant-option-label {
            cursor: pointer;
            position: relative;
            margin-bottom: 0;
        }
        /* Completely hide the radio input */
        .variant-radio-input {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            position: absolute;
            z-index: -1;
        }
        .variant-chip {
            padding: 0.75rem 1.5rem; /* Increased padding */
            border-radius: 0.5rem;   /* Slightly more rounded */
            border: 2px solid #e5e7eb; /* Thicker border for better visibility */
            background-color: #fff;
            color: #4b5563;
            font-size: 1rem;         /* Increased font size */
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: inline-block;
            min-width: 3.5rem;       /* Ensure minimum width */
            text-align: center;
        }
        /* Hover effect */
        .variant-option-label:hover .variant-chip {
            border-color: #009ec9;
            color: #009ec9;
            background-color: #f0f9ff;
        }
        /* Checked state */
        .variant-radio-input:checked + .variant-chip {
            border-color: #009ec9;
            background-color: #009ec9; /* Solid background for checked state often looks better/stronger */
            color: #ffffff; /* White text on selected */
            box-shadow: 0 4px 6px -1px rgba(0, 158, 201, 0.4), 0 2px 4px -1px rgba(0, 158, 201, 0.2);
        }
        .no-variants-text {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
    @endpush

    {{-- @php
        $imagesArray = [$product->thumbnail_image];

        foreach ($product->images as $image) {
            $imagesArray[] = $image->image_path;
        }
        
    @endphp --}}
    @php
        $defaultImages = [$product->thumbnail_image];
        foreach ($product->images as $image) {
            if (!$image->variant_combination_id) {
                $defaultImages[] = $image->image_path;
            }
        }
        
        $variantImages = [];
        foreach ($product->images as $image) {
            if ($image->variant_combination_id) {
                $variantImages[$image->variant_combination_id][] = $image->image_path;
            }
        }
        $allData = collect();
    @endphp

    <div class="rts-chop-details-area rts-section-gap bg_light-1">
        <div class="container">
            <div class="shopdetails-style-1-wrapper" style="width: 100%; max-width: 100%; overflow-x: hidden;">
                <div class="d-flex justify-content-between d-lg-none" style="text-align: center;">
                     <h2 class="product-title mt-0">{{ $product->name }}</h2>
                     <div class="rating-stars-group mb-4">
                        <span>{{ isset($totalReviews) ? $totalReviews : '' }} Reviews</span>
                    </div>
                </div>
                
                <div class="row g-3 g-md-5">
                    <div class="col-lg-12">
                        <div class="product-details-popup-wrapper in-shopdetails">
                            <div
                                class="rts-product-details-section rts-product-details-section2 product-details-popup-section">
                                <div class="product-details-popup">
                                    <div class="details-product-area" style="width: 100%; max-width: 100%;">
                                        @php
                                            $classNames = [
                                                'one',
                                                'two',
                                                'three',
                                                'four',
                                                'five',
                                                'six',
                                                'seven',
                                                'eight',
                                                'nine',
                                                'ten',
                                            ];
                                        @endphp


                                        <div class="show-product-area-details">
                                            {{-- Default Thumbnail Images --}}
                                            <div id="default-images" class="product-thumb-filter-group left">
                                                @foreach ($defaultImages as $index => $img)
                                                    @php $classNames["default-$index"] = "default-image-$index"; @endphp
                                                    <div class="thumb-filter filter-btn {{ $index === 0 ? 'active' : '' }}"
                                                        data-show=".{{ $classNames["default-$index"] }}">
                                                        <img src="{{ asset('storage/' . $img) }}" class="product-image" />
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Variant Thumbnail Images --}}
                                            <div id="variant-images" class="product-thumb-filter-group left">
                                                @foreach ($variantImages as $combinationId => $images)
                                                    @foreach ($images as $index => $img)
                                                        {{-- @if ($index === 0)
                                                            @continue
                                                        @endif --}}
                                                        @php
                                                            $key = "variant-{$combinationId}-{$index}";
                                                            $classNames[
                                                                $key
                                                            ] = "variant-image-{$combinationId}-{$index}";
                                                        @endphp
                                                        <div class="thumb-filter filter-btn {{ $index === 0 ? 'active' : '' }}"
                                                            data-combination-id="{{ $combinationId }}"
                                                            data-show=".{{ $classNames[$key] }}" style="display: none;">
                                                            <img src="{{ asset('storage/' . $img) }}"
                                                                class="product-image" />
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>

                                            {{-- Image Display Area --}}

                                            {{-- Main Image Preview --}}
                                            <div class="product-thumb-area">
                                                <div class="cursor"></div>

                                                @foreach ($defaultImages as $index => $img)
                                                    @php
                                                        $key = "default-{$index}";
                                                        $wrapperClass = $classNames[$key];
                                                    @endphp
                                                    <div
                                                        class="thumb-wrapper {{ $wrapperClass }} filterd-items {{ $index === 0 ? '' : 'hide' }}">
                                                        <div class="product-thumb zoom" onmousemove="zoom(event)"
                                                            onmouseleave="resetZoom(event)"
                                                            style="background-image: url('{{ asset('storage/' . $img) }}'); background-size: 200%; background-repeat: no-repeat; background-position: center;">
                                                            <img src="{{ asset('storage/' . $img) }}" alt="product-thumb">
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @foreach ($variantImages as $combinationId => $images)
                                                    @foreach ($images as $index => $img)
                                                        {{-- @if ($index === 0)
                                                            @continue
                                                        @endif --}}
                                                        @php
                                                            $key = "variant-{$combinationId}-{$index}";
                                                            $wrapperClass = $classNames[$key];
                                                        @endphp
                                                        <div class="thumb-wrapper {{ $wrapperClass }} filterd-items hide"
                                                            data-combination-id="{{ $combinationId }}">
                                                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                                                onmouseleave="resetZoom(event)"
                                                                style="background-image: url('{{ asset('storage/' . $img) }}'); background-size: 200%; background-repeat: no-repeat; background-position: center;">
                                                                <img src="{{ asset('storage/' . $img) }}"
                                                                    alt="product-thumb">
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="contents">
                                            <div class="product-status">
                                                <span
                                                    class="product-catagory">{{ isset($product->category) ? $product->category->name : '' }}</span>
                                                <div class="rating-stars-group">
                                                    <span>{{ isset($totalReviews) ? $totalReviews : '' }} Reviews</span>
                                                </div>
                                            </div>
                                            <h2 class="product-title" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{{ $product->name }}</h2>
                                            <div class="mt--20 mb--20  d-md-block d-sm-none" style="word-break: break-word; overflow-wrap: break-word;">
                                                {!! $product->description !!}
                                            </div>
                                            <span class="product-price mb--15 d-block"
                                                style="color: #DC2626; font-weight: 600;" id="product-price">
                                                {{ format_price($product->id) }}<span
                                                    class="old-price ml--15">{{ format_price($product->id, 'actual') }}</span></span>
                                            @if (Auth::user() && Auth::user()->role == 'Gym Owner/Trainer/Influencer/Dietitian')
                                                <a class="mb-4" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                    Bulk Order
                                                </a>
                                            @endif
                                            <div class="product-bottom-action mt-4 d-block d-lg-flex">
                                                <div class="cart-edits">
                                                    <div class="quantity-edit action-item">
                                                        <button class="button"><i class="fal fa-minus minus"></i></button>
                                                        <input type="text" class="input quantity-input" name="quantity"
                                                            id="quantity" value="1" />
                                                        <button class="button plus">+<i
                                                                class="fal fa-plus plus"></i></button>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="javascript:void(0);"
                                                        class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn my-2 w-100 w-md-auto"
                                                        data-product-id="{{ $product->id }}">
                                                        <div class="btn-text">
                                                            Add To Cart
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i class="fa-regular fa-cart-shopping"></i>
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i class="fa-regular fa-cart-shopping"></i>
                                                        </div>
                                                    </a>
                                                    <a href="javascript:void(0);" data-wish-product-id="{{ $product->id }}"
                                                        class="rts-btn btn-primary radious-sm with-icon add-to-wishlist my-2 w-100 w-md-auto"
                                                        >
                                                        <div class="btn-text">
                                                            Add To Wishlist
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i
                                                            class="fa-light fa-heart"></i>
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i
                                                            class="fa-light fa-heart"></i>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>

                                            @if ($product->variants && $product->variants->count() > 0)
                                                <div class="variant-container">
                                                    <div class="shop-sidebar">
                                                        @foreach ($product->variants as $variant)
                                                            <div class="variant-group">
                                                                <h6 class="variant-title">{{ $variant->name }}</h6>

                                                                @php
                                                                    $allData = isset($product->variantCombinations)
                                                                        ? $product->variantCombinations
                                                                        : collect();
                                                                    $allOptions = collect();

                                                                    if (
                                                                        $product->variantCombinations &&
                                                                        $product->variantCombinations->count()
                                                                    ) {
                                                                        foreach (
                                                                            $product->variantCombinations
                                                                            as $combination
                                                                        ) {
                                                                            $optionIds = is_array(
                                                                                $combination->variant_options,
                                                                            )
                                                                                ? $combination->variant_options
                                                                                : json_decode(
                                                                                    $combination->variant_options,
                                                                                    true,
                                                                                );

                                                                            $options = \App\Models\ProductVariantOption::whereIn(
                                                                                'id',
                                                                                $optionIds,
                                                                            )
                                                                                ->where(
                                                                                    'product_variant_id',
                                                                                    $variant->id,
                                                                                )
                                                                                ->get();

                                                                            $allOptions = $allOptions->merge($options);
                                                                        }

                                                                        $uniqueOptions = $allOptions->unique('id');
                                                                    }
                                                                @endphp

                                                                @if (!empty($uniqueOptions) && $uniqueOptions->count())
                                                                    <div class="variant-options">
                                                                        @foreach ($uniqueOptions as $index => $option)
                                                                            <label class="variant-option-label">
                                                                                <input type="radio"
                                                                                    name="variant_{{ $variant->id }}"
                                                                                    value="{{ $option->id }}"
                                                                                    data-product-id="{{ $product->id }}"
                                                                                    class="variant-radio-input"
                                                                                    {{ $loop->first ? 'checked' : '' }}>
                                                                                <div class="variant-chip">
                                                                                    {{ $option->value }}
                                                                                </div>
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <p class="no-variants-text">No combinations available</p>
                                                                @endif
                                                            </div>
                                                        @endforeach

                                                    </div>
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="product-discription-tab-shop mt--50">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
                                        data-bs-target="#home-tab-pane" type="button" role="tab"
                                        aria-controls="home-tab-pane" aria-selected="true">Product Details</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab"
                                        data-bs-target="#profile-tab-pane" type="button" role="tab"
                                        aria-controls="profile-tab-pane" aria-selected="false">Additional
                                        Information</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tabt" data-bs-toggle="tab"
                                        data-bs-target="#profile-tab-panes" type="button" role="tab"
                                        aria-controls="profile-tab-panes" aria-selected="false">Customer Reviews</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="review-tab" data-bs-toggle="tab"
                                        data-bs-target="#review" type="button" role="tab" aria-controls="review"
                                        aria-selected="false">
                                        Reviews
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade   show active" id="home-tab-pane" role="tabpanel"
                                    aria-labelledby="home-tab" tabindex="0">
                                    <div class="single-tab-content-shop-details">
                                        <div class="details-row-2">
                                            <div class="container">
                                                <h4 class="title">{{ $product->name }}</h4>
                                                <p class="mb--25" style="word-break: break-word; overflow-wrap: break-word;">
                                                    {!! $product->description !!}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel"
                                    aria-labelledby="profile-tab" tabindex="0">
                                    <div class="single-tab-content-shop-details">
                                        <p class="disc" style="word-break: break-word; overflow-wrap: break-word;">
                                            {!! $product->description !!}
                                        </p>
                                        <div class="table-responsive table-shop-details-pd">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>{{ $product->category->name }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Sub Category</td>
                                                        <td>{{ isset($product->subCategory) ? $product->subCategory->name : '' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Brand</td>
                                                        <td>{{ isset($product->seller) ? $product->seller->brand : '' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        @php
                                                            $rawCategory = $product->section_category;

                                                            // Make sure we always have an array
                                                            $categories = is_string($rawCategory)
                                                                ? json_decode($rawCategory, true)
                                                                : (is_array($rawCategory)
                                                                    ? $rawCategory
                                                                    : []);
                                                        @endphp
                                                        <td>section category</td>
                                                        <td>
                                                            @if (!empty($categories))
                                                                {{ implode(', ', array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), $categories)) }}
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="cansellation mt--20">
                                            <span> Return/cancellation:</span> No change will be applicable which are
                                            already delivered to customer. If product quality or quantity problem found then
                                            customer can return/cancel their order on delivery time with presence of
                                            delivery person.
                                        </p>
                                        <p class="note">
                                            <span>Note:</span> Product delivery duration may vary due to product
                                            availability in stock.
                                        </p>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="profile-tab-panes" role="tabpanel"
                                    aria-labelledby="profile-tabt" tabindex="0">
                                    <div class="single-tab-content-shop-details">
                                        <div class="product-details-review-product-style">
                                            <div class="average-stars-area-left">
                                                <div class="top-stars-wrapper">
                                                    <h4 class="review">{{ number_format($averageRating, 1) }}</h4>
                                                    <div class="rating-disc">
                                                        <span>Average Rating</span>
                                                        <div class="stars">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i
                                                                    class="{{ $i <= round($averageRating) ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                                                            @endfor
                                                            <span>({{ $totalReviews }} Reviews)</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="review-charts-details">
                                                    @foreach (array_reverse(range(1, 5)) as $star)
                                                        @php
                                                            $count = $ratingCounts[$star];
                                                            $percent =
                                                                $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                                                        @endphp

                                                        <div class="single-review">
                                                            <div class="stars">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    <i
                                                                        class="{{ $i <= $star ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                                                                @endfor
                                                            </div>
                                                            <div class="single-progress-area-incard">
                                                                <div class="progress">
                                                                    <div class="progress-bar wow fadeInLeft"
                                                                        role="progressbar"
                                                                        style="width: {{ $percent }}%"
                                                                        aria-valuenow="{{ $percent }}"
                                                                        aria-valuemin="0" aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <span class="pac">{{ round($percent) }}%</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="submit-review-area">
                                                {{-- Success/Error Messages --}}
                                                @if (session('success'))
                                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                        {{ session('success') }}
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    </div>
                                                @endif

                                                @if (session('error'))
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        {{ session('error') }}
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    </div>
                                                @endif

                                                @if ($errors->any())
                                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                        <ul class="mb-0">
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                    </div>
                                                @endif

                                                <form action="{{ route('review.store') }}" method="POST"
                                                    class="submit-review-area">
                                                    @csrf
                                                    <h5 class="title">Submit Your Review <small class="text-muted">(No login required)</small></h5>

                                                    <div class="your-rating">
                                                        <span>Your Rating Of This Product :</span>
                                                        <div class="stars" id="rating-stars">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i class="fa-regular fa-star star"
                                                                    data-value="{{ $i }}"></i>
                                                            @endfor
                                                            <input type="hidden" name="rating" id="rating-value"
                                                                value="0">
                                                            <input type="hidden" name="productId"
                                                                value="{{ $product->id }}">
                                                        </div>
                                                    </div>

                                                    <div class="half-input-wrapper">
                                                        <div class="half-input">
                                                            <input type="text" name="name" placeholder="Your Name*"
                                                                value="{{ old('name') }}" required>
                                                        </div>
                                                    </div>

                                                    <textarea name="review" placeholder="Write Your Review" required>{{ old('review') }}</textarea>
                                                    <button class="rts-btn btn-primary" type="submit">SUBMIT
                                                        REVIEW</button>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="review" role="tabpanel" aria-labelledby="review-tab"
                                    tabindex="0">

                                    <div class="single-tab-content-shop-details">
                                        <div class="product-details-review-product-style">
                                            @if ($reviews->count())
                                                @foreach ($reviews as $review)
                                                    <div class="card mb-3 shadow-sm border-0">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <h6 class="mb-0">{{ $review->name }}</h6>
                                                                <div class="text-warning">
                                                                    @for ($i = 1; $i <= 5; $i++)
                                                                        <i class="{{ $i <= $review->rating ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                                                                    @endfor
                                                                </div>
                                                            </div>
                                                            <p class="mb-1 text-muted" style="font-size: 14px;">
                                                                {{ $review->created_at->format('F d, Y') }}
                                                            </p>
                                                            <p class="mb-0">{{ $review->review }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="alert alert-info text-center mb-0">No review yet</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-3" id="exampleModalLabel">Bulk Order</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bulk.order') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity:</label>
                            <input type="number" name="quantity" placeholder="Enter Product Quantity" value="50"
                                min="50" required>
                            <input type="hidden" value="{{ $product->id }}" name="product">
                        </div>
                        @if ($product->variants && $product->variants->count() > 0)
                            <div class="rts-item p-4">
                                <div class="shop-sidebar">
                                    @foreach ($product->variants as $variant)
                                        <div class="variant-group">
                                            <h6 class="variant-title">{{ $variant->name }}</h6>
                                            @php
                                                $allData = isset($product->variantCombinations)
                                                    ? $product->variantCombinations
                                                    : collect();
                                                $allOptions = collect();

                                                if (
                                                    $product->variantCombinations &&
                                                    $product->variantCombinations->count()
                                                ) {
                                                    foreach ($product->variantCombinations as $combination) {
                                                        $optionIds = is_array($combination->variant_options)
                                                            ? $combination->variant_options
                                                            : json_decode($combination->variant_options, true);

                                                        $options = \App\Models\ProductVariantOption::whereIn(
                                                            'id',
                                                            $optionIds,
                                                        )
                                                            ->where('product_variant_id', $variant->id)
                                                            ->get();

                                                        $allOptions = $allOptions->merge($options);
                                                    }

                                                    $uniqueOptions = $allOptions->unique('id');
                                                }
                                            @endphp

                                            @if (!empty($uniqueOptions) && $uniqueOptions->count())
                                                <div class="variant-options">
                                                    @foreach ($uniqueOptions as $index => $option)
                                                        <label class="variant-option-label">
                                                            <input type="radio" 
                                                                name="variant_{{ $variant->id }}"
                                                                value="{{ $option->id }}" 
                                                                class="variant-radio-input"
                                                                {{ $loop->first ? 'checked' : '' }}>
                                                            <div class="variant-chip" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                                {{ $option->value }}
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="no-variants-text">No combinations available</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary float-end me-auto">
                                Bulk Order
                            </button>
                            <button type="button" class="btn p-2 m-0 btn-light float-end" data-bs-dismiss="modal"
                                aria-label="Close">
                                Close
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
    <!-- rts grocery feature area start -->
    <div class="rts-grocery-feature-area rts-section-gap bg_light-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-area-between">
                        <h2 class="title-left">
                            Related Product
                        </h2>
                        <div class="next-prev-swiper-wrapper">
                            <div class="swiper-button-prev"><i class="fa-regular fa-chevron-left"></i></div>
                            <div class="swiper-button-next"><i class="fa-regular fa-chevron-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="category-area-main-wrapper-one">
                        <div class="swiper mySwiper-category-1 swiper-data"
                            data-swiper='{
                            "spaceBetween":16,
                            "slidesPerView":6,
                            "loop": true,
                            "speed": 700,
                            "navigation":{
                                "nextEl":".swiper-button-next",
                                "prevEl":".swiper-button-prev"
                            },
                            "breakpoints":{
                            "0":{
                                "slidesPerView":1,
                                "spaceBetween": 12},
                            "380":{
                                "slidesPerView":2,
                                "spaceBetween":12},
                            "480":{
                                "slidesPerView":2,
                                "spaceBetween":12},
                            "640":{
                                "slidesPerView":3,
                                "spaceBetween":16},
                            "840":{
                                "slidesPerView":4,
                                "spaceBetween":16},
                            "1540":{
                                "slidesPerView":6,
                                "spaceBetween":16}
                            }
                        }'>
                            <div class="swiper-wrapper">
                                @foreach ($relatedProducts as $product)
                                    <div class="swiper-slide">
                                        <div class="single-shopping-card-one">
                                            <!-- iamge and sction area start -->
                                            <div class="image-and-action-area-wrapper">
                                                <a href="{{ route('product.details', Crypt::encrypt($product->id)) }}"
                                                    class="thumbnail-preview">
                                                    @if ($product->discount_percentage > 0)
                                                        <div class="badge">
                                                            <span>
                                                                {{ rtrim(rtrim(number_format($product->discount_percentage, 2), '0'), '.') }}% <br>
                                                                Off
                                                            </span>

                                                            <i class="fa-solid fa-bookmark"></i>
                                                        </div>
                                                    @endif
                                                    @php
                                                        $productImage = $product->thumbnail_image;
                                                        if (empty($productImage) && $product->has_variants) {
                                                            $variant = $product->variantCombinations->sortByDesc('is_active')->first();
                                                            if ($variant && $variant->thumbnailImage) {
                                                                $productImage = $variant->thumbnailImage->image_path;
                                                            }
                                                        }
                                                    @endphp
                                                    <img src="{{ asset('storage/' . $productImage) }}"
                                                        alt="product">
                                                </a>
                                            </div>
                                            <!-- iamge and sction area start -->

                                            <div class="body-content">
                                                <a href="{{ route('product.details', Crypt::encrypt($product->id)) }}">
                                                    <h4 class="title">{{ $product->name }}</h4>
                                                </a>
                                                <span class="availability">500g Pack</span>
                                                <div class="price-area">
                                                    <span class="current">{{ format_price($product->id) }}</span>
                                                    <div class="previous">{{ format_price($product->id, 'actual') }}</div>
                                                </div>
                                                <div class="cart-counter-action">
                                                    <div class="quantity-edit">
                                                        <input type="text" class="input quantity-input"
                                                            value="1">
                                                        <div class="button-wrapper-action">
                                                            <button class="button"><i
                                                                    class="fa-regular fa-chevron-down"></i></button>
                                                            <button class="button plus">+<i
                                                                    class="fa-regular fa-chevron-up"></i></button>
                                                        </div>
                                                    </div>
                                                    <a href="#"
                                                        class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn-other"
                                                        data-product-id="{{ $product->id }}">
                                                        <div class="btn-text">
                                                            Add To Cart
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i class="fa-regular fa-cart-shopping"></i>
                                                        </div>
                                                        <div class="arrow-icon">
                                                            <i class="fa-regular fa-cart-shopping"></i>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rts grocery feature area end -->
    @endif

    <div class="product-details-popup-wrapper">
        <div class="rts-product-details-section rts-product-details-section2 product-details-popup-section">
            <div class="product-details-popup">
                <button class="product-details-close-btn"><i class="fal fa-times"></i></button>
                <div class="details-product-area">
                    <div class="product-thumb-area">
                        <div class="cursor"></div>
                        <div class="thumb-wrapper one filterd-items figure">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url(assets/images/products/product-details.jpg)"><img
                                    src="assets/images/products/product-details.jpg" alt="product-thumb">
                            </div>
                        </div>
                        <div class="thumb-wrapper two filterd-items hide">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url(assets/images/products/product-filt2.jpg)"><img
                                    src="assets/images/products/product-filt2.jpg" alt="product-thumb">
                            </div>
                        </div>
                        <div class="thumb-wrapper three filterd-items hide">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url(assets/images/products/product-filt3.jpg)"><img
                                    src="assets/images/products/product-filt3.jpg" alt="product-thumb">
                            </div>
                        </div>
                        <div class="product-thumb-filter-group">
                            <div class="thumb-filter filter-btn active" data-show=".one"><img
                                    src="assets/images/products/product-filt1.jpg" alt="product-thumb-filter"></div>
                            <div class="thumb-filter filter-btn" data-show=".two"><img
                                    src="assets/images/products/product-filt2.jpg" alt="product-thumb-filter"></div>
                            <div class="thumb-filter filter-btn" data-show=".three"><img
                                    src="assets/images/products/product-filt3.jpg" alt="product-thumb-filter"></div>
                        </div>
                    </div>
                    <div class="contents">
                        <div class="product-status">
                            <span class="product-catagory">Dress</span>
                            <div class="rating-stars-group">
                                <div class="rating-star"><i class="fas fa-star"></i></div>
                                <div class="rating-star"><i class="fas fa-star"></i></div>
                                <div class="rating-star"><i class="fas fa-star-half-alt"></i></div>
                                <span>10 Reviews</span>
                            </div>
                        </div>
                        <h2 class="product-title">Wide Cotton Tunic Dress <span class="stock">In Stock</span></h2>
                        <span class="product-price"><span class="old-price">$9.35</span> $7.25</span>
                        <p>
                            Priyoshop has brought to you the Hijab 3 Pieces Combo Pack PS23. It is a
                            completely modern design and you feel comfortable to put on this hijab.
                            Buy it at the best price.
                        </p>
                        <div class="product-bottom-action">
                            <div class="cart-edit">
                                <div class="quantity-edit action-item">
                                    <button class="button"><i class="fal fa-minus minus"></i></button>
                                    <input type="text" class="input" value="1" />
                                    <button class="button plus">+<i class="fal fa-plus plus"></i></button>
                                </div>
                            </div>
                            <a href="#" class="rts-btn btn-primary radious-sm with-icon">
                                <div class="btn-text">
                                    Add To Cart
                                </div>
                                <div class="arrow-icon">
                                    <i class="fa-regular fa-cart-shopping"></i>
                                </div>
                                <div class="arrow-icon">
                                    <i class="fa-regular fa-cart-shopping"></i>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="rts-btn btn-primary ml--20"><i
                                    class="fa-light fa-heart"></i></a>
                        </div>
                        <div class="product-uniques">
                            <span class="sku product-unipue"><span>SKU: </span> BO1D0MX8SJ</span>
                            <span class="catagorys product-unipue"><span>Categories: </span> T-Shirts, Tops, Mens</span>
                            <span class="tags product-unipue"><span>Tags: </span> fashion, t-shirts, Men</span>
                        </div>
                        <div class="share-social">
                            <span>Share:</span>
                            <a class="platform" href="http://facebook.com/" target="_blank"><i
                                    class="fab fa-facebook-f"></i></a>
                            <a class="platform" href="http://twitter.com/" target="_blank"><i
                                    class="fab fa-twitter"></i></a>
                            <a class="platform" href="http://behance.com/" target="_blank"><i
                                    class="fab fa-behance"></i></a>
                            <a class="platform" href="http://youtube.com/" target="_blank"><i
                                    class="fab fa-youtube"></i></a>
                            <a class="platform" href="http://linkedin.com/" target="_blank"><i
                                    class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- successfully add in wishlist -->
    <div class="successfully-addedin-wishlist">
        <div class="d-flex" style="align-items: center; gap: 15px;">
            <i class="fa-regular fa-check"></i>
            <p>Your item has already added in wishlist successfully</p>
        </div>
    </div>
    <!-- successfully add in wishlist end -->
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const variantCombinations = @json($allData);
        const totalVariants = {{ $product->variants->count() }};
        const selectedOptions = {};

        function updateImageBasedOnSelection(combinationId) {
            const defaultImages = document.getElementById('default-images');
            const variantImages = document.getElementById('variant-images');

            document.querySelectorAll('#variant-images .thumb-filter').forEach(el => el.style.display = 'none');

            if (combinationId) {
                const visibleThumbs = Array.from(document.querySelectorAll(
                    `#variant-images .thumb-filter[data-combination-id="${combinationId}"]`));
                if (visibleThumbs.length > 0) {
                    defaultImages.style.display = 'none';

                    visibleThumbs.forEach((thumb, i) => {
                        thumb.style.display = 'inline-block';
                        if (i === 0) thumb.click();
                    });
                    return;
                }
            }

            variantImages.style.display = 'none';
            const firstDefault = document.querySelector('#default-images .thumb-filter');
            if (firstDefault) firstDefault.click();
        }

        function updatePriceBasedOnSelection() {
            if (!Array.isArray(variantCombinations) || variantCombinations.length === 0) {
                return;
            }

            const selectedOptionIds = Object.values(selectedOptions).map(Number).sort((a, b) => a - b);

            const matchingCombination = variantCombinations.find(comb => {
                if (!Array.isArray(comb.variant_options)) return false;
                const combOptionIds = comb.variant_options.map(Number);
                return selectedOptionIds.every(id => combOptionIds.includes(id));
            });

            const userType = @json(Auth::check() ? Auth::user()->role : null);

            const priceElement = document.getElementById('product-price');
            if (matchingCombination) {
                if (userType == 'User') {
                    if (matchingCombination.regular_user_discount > 0) {
                        priceElement.textContent = "₹" + matchingCombination.regular_user_final_price;
                    } else {
                        priceElement.textContent = "₹" + matchingCombination.regular_user_price;
                    }
                } else if (userType == 'Gym Owner/Trainer/Influencer/Dietitian') {
                    if (matchingCombination.gym_owner_discount > 0) {
                        priceElement.textContent = "₹" + matchingCombination.gym_owner_final_price;
                    } else {
                        priceElement.textContent = "₹" + matchingCombination.gym_owner_price;
                    }
                } else if (userType == 'Shop Owner') {
                    if (matchingCombination.shop_owner_discount > 0) {
                        priceElement.textContent = "₹" + matchingCombination.shop_owner_final_price;
                    } else {
                        priceElement.textContent = "₹" + matchingCombination.shop_owner_price;
                    }
                } else {
                    if (matchingCombination.regular_user_discount > 0) {
                        priceElement.textContent = "₹" + matchingCombination.regular_user_final_price;
                    } else {
                        priceElement.textContent = "₹" + matchingCombination.regular_user_price;
                    }
                }
                updateImageBasedOnSelection(matchingCombination.id);
            } else {
                priceElement.textContent = "₹{{ $product->regular_user_price }}";
                updateImageBasedOnSelection(null);
            }
        }

        // On change
        document.querySelectorAll('input[type=radio][name^="variant_"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const variantId = this.name.split('_')[1];
                const optionId = parseInt(this.value);
                selectedOptions[variantId] = optionId;
                updatePriceBasedOnSelection();
            });

            // ✅ On load: prefill if already checked
            if (radio.checked) {
                const variantId = radio.name.split('_')[1];
                const optionId = parseInt(radio.value);
                selectedOptions[variantId] = optionId;
            }
        });

        // ✅ Trigger once on page load
        updatePriceBasedOnSelection();
    });
</script>


<script>
    $(document).ready(function() {
        $('.add-to-cart-btn').on('click', function(e) {
            e.preventDefault();

            let productId = $(this).data('product-id');
            let quantity = $('.quantity-input').val();
            let selectedVariants = {};

            $('input[type=radio][data-product-id="' + productId + '"]:checked').each(function() {
                let variantId = $(this).attr('name').split('_')[1];
                selectedVariants[variantId] = parseInt($(this).val());
            });

            $.ajax({
                url: '{{ route('cart.add') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId,
                    quantity: quantity,
                    variant_option_ids: selectedVariants
                },
                success: function(response) {
                    Toastify({
                        text: "Product added to cart!",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#009ec9",
                    }).showToast();

                    $(".cartCount").text(response.cartCount);
                },
                error: function(xhr) {
                    if (xhr.status == 401) {
                        Toastify({
                            text: "Please Login to add product to cart.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    } else {
                        Toastify({
                            text: "Failed to add product to cart. Please try again.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                }
            });
        });

        $('.add-to-cart-btn-other').on('click', function(e) {
            e.preventDefault();

            const productId = $(this).data('product-id');
            const quantity = $(this).closest('.cart-counter-action').find('.quantity-input').val() || 1;

            $.ajax({
                url: '{{ route('cart.add') }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status == 'success') {
                        Toastify({
                            text: "Product added to cart!",
                            duration: 1500,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#009ec9",
                        }).showToast();

                        $(".cartCount").text(response.cartCount);
                    }
                },
                error: function(xhr) {
                    if (xhr.status == 401) {
                        Toastify({
                            text: "Please Login to add product to cart.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    } else {
                        Toastify({
                            text: "Failed to add product to cart. Please try again.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('.add-to-wishlist').on('click', function(e) {
            e.preventDefault();

            let productId = $(this).data('wish-product-id');
            let quantity = $('.quantity-input').val();
            let selectedVariants = {};

            $('input[type=radio][data-product-id="' + productId + '"]:checked').each(function() {
                let variantId = $(this).attr('name').split('_')[1];
                selectedVariants[variantId] = parseInt($(this).val());
            });

            $.ajax({
                url: '{{ route('wishlist.add') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId,
                    quantity: quantity,
                    variant_option_ids: selectedVariants
                },
                success: function(response) {
                    let message = response.message || "Product added to wishlist!";
                    let backgroundColor = "#009ec9";
                    
                    if (response.status === 'info') {
                        backgroundColor = "#ffc107";
                    }
                    
                    Toastify({
                        text: message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: backgroundColor,
                    }).showToast();
                    
                    $(".wishlistCount").text(response.wishlistCount);
                },
                error: function(xhr) {
                    let errorMessage = "Failed to add product to wishlist. Please try again.";
                    
                    if (xhr.status === 401) {
                        errorMessage = "Please login to add items to wishlist, or continue as guest.";
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Toastify({
                        text: errorMessage,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                }
            });
        });
    });
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    integrity="sha512-..." crossorigin="anonymous" />

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating-value');
        const reviewForm = document.querySelector('form.submit-review-area');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const selectedRating = parseInt(this.getAttribute('data-value'));
                ratingInput.value = selectedRating;

                stars.forEach(s => {
                    const val = parseInt(s.getAttribute('data-value'));
                    if (val <= selectedRating) {
                        s.classList.remove('fa-regular');
                        s.classList.add('fa-solid', 'filled');
                    } else {
                        s.classList.remove('fa-solid', 'filled');
                        s.classList.add('fa-regular');
                    }
                });
            });
        });

        // Add form validation
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                const rating = ratingInput.value;
                if (!rating || rating === '0') {
                    e.preventDefault();
                    alert('Please select a rating before submitting your review.');
                    return false;
                }
            });
        }

        // Handle variant selection to show corresponding images
        const variantRadios = document.querySelectorAll('input[type="radio"][name^="variant_"]');
        const variantImages = document.querySelectorAll('.variant-display-image');

        variantRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    // Get all selected variant option IDs
                    const selectedOptions = [];
                    const allVariantRadios = document.querySelectorAll(
                        'input[type="radio"][name^="variant_"]:checked');

                    allVariantRadios.forEach(selectedRadio => {
                        selectedOptions.push(selectedRadio.value);
                    });

                    // Hide all variant images first
                    variantImages.forEach(img => {
                        img.style.display = 'none';
                    });

                    // Find and show the matching combination image
                    // This is a simplified approach - you might need to adjust based on your combination logic
                    const combinationId = this.closest('.shop-sidevbar').getAttribute(
                        'data-combination-id') || '1';
                    const matchingImage = document.querySelector(
                        `.variant-display-image[data-combination-id="${combinationId}"]`);

                    if (matchingImage) {
                        matchingImage.style.display = 'block';
                    } else if (variantImages.length > 0) {
                        // Fallback to show first variant image if no exact match
                        variantImages[0].style.display = 'block';
                    }
                }
            });
        });
    });
</script>

