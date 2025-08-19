@extends('layouts.app')

@section('content')
    <div class="section-seperator bg_light-1">
        <div class="container">
            <hr class="section-seperator">
        </div>
    </div>

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
            <div class="shopdetails-style-1-wrapper">
                <div class="row g-5">
                    <div class="col-lg-12">
                        <div class="product-details-popup-wrapper in-shopdetails">
                            <div
                                class="rts-product-details-section rts-product-details-section2 product-details-popup-section">
                                <div class="product-details-popup">
                                    <div class="details-product-area">
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

                                        {{-- <div class="show-product-area-details">
                                            <div id="default-images" class="product-thumb-filter-group left">
                                                @foreach ($defaultImages as $index => $img)
                                                    <div class="thumb-filter filter-btn {{ $index === 0 ? 'active' : '' }}"
                                                        data-show=".{{ $classNames[$index] }}">
                                                        <img src="{{ asset('storage/' . $img) }}" class="product-image" />
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div id="variant-images" class="product-thumb-filter-group left"
                                                style="display: none;">
                                                @foreach ($variantImages as $combinationId => $images)
                                                    <div class="variant-image-group thumb-filter filter-btn {{ $index === 0 ? 'active' : '' }}"
                                                        data-combination-id="{{ $combinationId }}"
                                                        data-show=".{{ $classNames[$index] }}" style="display: none;">
                                                        @foreach ($images as $img)
                                                            <img src="{{ asset('storage/' . $img) }}"
                                                                class="product-image" />
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                            
                                            <div class="product-thumb-area">
                                                <div class="cursor"></div>
                                                
                                                @foreach ($defaultImages as $index => $image)
                                                    @php $classNames["default-$index"] = "default-image-$index"; @endphp
                                                    <div
                                                        class="thumb-wrapper {{ $classNames["default-$index"] }} filterd-items {{ $index === 0 ? '' : 'hide' }}">
                                                        <div class="product-thumb zoom" onmousemove="zoom(event)"
                                                            onmouseleave="resetZoom(event)"
                                                            style="background-image: url('{{ asset('storage/' . $image) }}'); background-size: 200%; background-repeat: no-repeat; background-position: center;">
                                                            <img src="{{ asset('storage/' . $image) }}"
                                                                alt="product-thumb">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div> --}}

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
                                            <h2 class="product-title">{{ $product->name }}</h2>
                                            <p class="mt--20 mb--20">
                                                {!! $product->description !!}
                                            </p>
                                            <span class="product-price mb--15 d-block"
                                                style="color: #DC2626; font-weight: 600;" id="product-price">
                                                {{ format_price($product->id) }}<span
                                                    class="old-price ml--15">{{ format_price($product->id, 'actual') }}</span></span>
                                            @if (Auth::user() && Auth::user()->role == 'Gym Owner/Trainer/Influencer/Dietitian')
                                                <a class="mb-4" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                    Bulk Order
                                                </a>
                                            @endif
                                            <div class="product-bottom-action mt-4">
                                                <div class="cart-edits">
                                                    <div class="quantity-edit action-item">
                                                        <button class="button"><i class="fal fa-minus minus"></i></button>
                                                        <input type="text" class="input quantity-input" name="quantity"
                                                            id="quantity" value="1" />
                                                        <button class="button plus">+<i
                                                                class="fal fa-plus plus"></i></button>
                                                    </div>
                                                </div>
                                                <a href="javascript:void(0);"
                                                    class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
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
                                                    class="rts-btn btn-primary add-to-wishlist ml--20"><i
                                                        class="fa-light fa-heart"></i></a>
                                            </div>

                                            @if ($product->variants && $product->variants->count() > 0)
                                                <div class="col-lg-3  rts-item">
                                                    <div class="">
                                                        <div class="shop-sidevbar">
                                                            <h6 class="title">Varients</h6>
                                                            @foreach ($product->variants as $variant)
                                                                <h4>{{ $variant->name }}</h4>

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
                                                                    @foreach ($uniqueOptions as $index => $option)
                                                                        <label class="variant-radio mb-2 mx-2">
                                                                            <input type="radio"
                                                                                name="variant_{{ $variant->id }}"
                                                                                value="{{ $option->id }}"
                                                                                data-product-id="{{ $product->id }}"
                                                                                {{ $loop->first ? 'checked' : '' }}>
                                                                            {{ $option->value }}
                                                                        </label>
                                                                    @endforeach
                                                                @else
                                                                    <p>No combinations available</p>
                                                                @endif
                                                            @endforeach

                                                        </div>
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
                                            <div class="right">
                                                <h4 class="title">{{ $product->name }}</h4>
                                                <p class="mb--25">
                                                    {!! $product->description !!}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel"
                                    aria-labelledby="profile-tab" tabindex="0">
                                    <div class="single-tab-content-shop-details">
                                        <p class="disc">
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
                                <div class="shop-sidevbar">
                                    <h6 class="title">Varients</h6>
                                    @foreach ($product->variants as $variant)
                                        <h4>{{ $variant->name }}</h4>
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
                                            @foreach ($uniqueOptions as $index => $option)
                                                <label class="variant-radio mb-2">
                                                    <input type="radio" name="variant_{{ $variant->id }}"
                                                        value="{{ $option->id }}" {{ $loop->first ? 'checked' : '' }}>
                                                    {{ $option->value }}
                                                </label>
                                            @endforeach
                                        @else
                                            <p>No combinations available</p>
                                        @endif
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
                                                            <span>{{ $product->discount_percentage }}% <br>
                                                                Off
                                                            </span>
                                                            <i class="fa-solid fa-bookmark"></i>
                                                        </div>
                                                    @endif
                                                    <img src="{{ asset('storage/' . $product->thumbnail_image) }}"
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

    <div class="rts-shorts-service-area rts-section-gap bg_primary">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M55.7029 25.2971C51.642 21.2363 46.2429 19 40.5 19C34.7571 19 29.358 21.2363 25.2971 25.2971C21.2364 29.358 19 34.7571 19 40.5C19 46.2429 21.2364 51.642 25.2971 55.7029C29.358 59.7637 34.7571 62 40.5 62C46.2429 62 51.642 59.7637 55.7029 55.7029C59.7636 51.642 62 46.2429 62 40.5C62 34.7571 59.7636 29.358 55.7029 25.2971ZM40.5 59.4805C30.0341 59.4805 21.5195 50.9659 21.5195 40.5C21.5195 30.0341 30.0341 21.5195 40.5 21.5195C50.9659 21.5195 59.4805 30.0341 59.4805 40.5C59.4805 50.9659 50.9659 59.4805 40.5 59.4805Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M41.8494 39.2402H39.1506C37.6131 39.2402 36.3623 37.9895 36.3623 36.452C36.3623 34.9145 37.6132 33.6638 39.1506 33.6638H44.548C45.2438 33.6638 45.8078 33.0997 45.8078 32.404C45.8078 31.7083 45.2438 31.1442 44.548 31.1442H41.7598V28.3559C41.7598 27.6602 41.1957 27.0962 40.5 27.0962C39.8043 27.0962 39.2402 27.6602 39.2402 28.3559V31.1442H39.1507C36.2239 31.1442 33.8429 33.5253 33.8429 36.452C33.8429 39.3787 36.224 41.7598 39.1507 41.7598H41.8495C43.3869 41.7598 44.6377 43.0106 44.6377 44.548C44.6377 46.0855 43.3869 47.3363 41.8495 47.3363H36.452C35.7563 47.3363 35.1923 47.9004 35.1923 48.5961C35.1923 49.2918 35.7563 49.8559 36.452 49.8559H39.2402V52.6442C39.2402 53.34 39.8043 53.904 40.5 53.904C41.1957 53.904 41.7598 53.34 41.7598 52.6442V49.8559H41.8494C44.7761 49.8559 47.1571 47.4747 47.1571 44.548C47.1571 41.6214 44.7761 39.2402 41.8494 39.2402Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">Best Prices &amp; Offers</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M55.5564 24.4436C51.4012 20.2884 45.8763 18 40 18C34.1237 18 28.5988 20.2884 24.4436 24.4436C20.2884 28.5988 18 34.1237 18 40C18 45.8763 20.2884 51.4012 24.4436 55.5564C28.5988 59.7116 34.1237 62 40 62C45.8763 62 51.4012 59.7116 55.5564 55.5564C59.7116 51.4012 62 45.8763 62 40C62 34.1237 59.7116 28.5988 55.5564 24.4436ZM40 59.4219C29.2907 59.4219 20.5781 50.7093 20.5781 40C20.5781 29.2907 29.2907 20.5781 40 20.5781C50.7093 20.5781 59.4219 29.2907 59.4219 40C59.4219 50.7093 50.7093 59.4219 40 59.4219Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M42.4009 34.7622H35.0294L36.295 33.4966C36.7982 32.9934 36.7982 32.177 36.295 31.6738C35.7914 31.1703 34.9753 31.1703 34.4718 31.6738L31.0058 35.1398C30.5022 35.6434 30.5022 36.4594 31.0058 36.9626L34.4718 40.429C34.7236 40.6808 35.0536 40.8067 35.3832 40.8067C35.7132 40.8067 36.0432 40.6808 36.295 40.429C36.7982 39.9255 36.7982 39.1094 36.295 38.6059L35.0291 37.3403H42.4009C44.8229 37.3403 46.7934 39.3108 46.7934 41.7328C46.7934 44.1549 44.8229 46.1254 42.4009 46.1254H37.8925C37.1805 46.1254 36.6035 46.7028 36.6035 47.4145C36.6035 48.1265 37.1805 48.7035 37.8925 48.7035H42.4009C46.2446 48.7035 49.3716 45.5765 49.3716 41.7328C49.3716 37.8892 46.2446 34.7622 42.4009 34.7622Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">100% Return Policy</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M26.2667 26.2667C29.935 22.5983 34.8122 20.5781 40 20.5781C43.9672 20.5781 47.8028 21.7849 51.0284 24.0128L48.5382 24.2682L48.8013 26.8328L55.5044 26.1453L54.8169 19.4422L52.2522 19.7053L52.4751 21.8787C48.8247 19.3627 44.4866 18 40 18C34.1236 18 28.5989 20.2884 24.4437 24.4437C20.2884 28.5989 18 34.1236 18 40C18 44.3993 19.2946 48.6457 21.7437 52.28L23.8816 50.8393C23.852 50.7952 23.8232 50.7508 23.7939 50.7065C21.69 47.5289 20.5781 43.8307 20.5781 40C20.5781 34.8123 22.5983 29.935 26.2667 26.2667Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M58.2564 27.72L56.1184 29.1607C56.148 29.2047 56.1768 29.2493 56.2061 29.2935C58.3099 32.4711 59.4219 36.1693 59.4219 40C59.4219 45.1878 57.4017 50.065 53.7333 53.7333C50.0651 57.4017 45.1879 59.4219 40 59.4219C36.0328 59.4219 32.1972 58.2151 28.9716 55.9872L31.4618 55.7318L31.1987 53.1672L24.4956 53.8547L25.1831 60.5578L27.7478 60.2947L27.5249 58.1213C31.1754 60.6373 35.5135 62 40 62C45.8764 62 51.4011 59.7116 55.5564 55.5563C59.7117 51.4011 62 45.8764 62 40C62 35.6007 60.7055 31.3543 58.2564 27.72Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M28.7407 42.7057L30.4096 41.1632C31.6739 40 31.9142 39.2161 31.9142 38.3564C31.9142 36.7127 30.5108 35.6633 28.4753 35.6633C26.7305 35.6633 25.4788 36.3966 24.8087 37.5093L26.6673 38.546C27.0213 37.9771 27.6029 37.6863 28.2477 37.6863C29.0063 37.6863 29.3856 38.0276 29.3856 38.5966C29.3856 38.9633 29.2845 39.3679 28.5764 40.0254L25.2639 43.123V44.6907H32.1544V42.7057L28.7407 42.7057Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M40.1076 42.9965H41.4224V41.0115H40.1076V39.507H37.7433V41.0115H35.948L39.5512 35.8404H36.9594L32.9894 41.3655V42.9965H37.6674V44.6906H40.1076V42.9965Z"
                                    fill="#009ec9"></path>
                                <path d="M43.6986 45.955L47.8708 34.045H45.7341L41.5618 45.955H43.6986Z" fill="#009ec9">
                                </path>
                                <path
                                    d="M49.995 39.1908V37.8254H52.3213L49.3375 44.6906H52.0685L55.1913 37.4081V35.8404H47.8582V39.1908H49.995Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">Support 24/7</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M57.0347 37.5029C54.0518 29.3353 48.6248 23.7668 48.3952 23.5339L46.2276 21.3333V29.6016C46.2276 30.3124 45.658 30.8906 44.9578 30.8906C44.2577 30.8906 43.688 30.3124 43.688 29.6016C43.688 23.2045 38.5614 18 32.26 18H30.9902V19.2891C30.9902 25.3093 27.0988 29.646 24.1414 35.2212C21.1581 40.8449 21.3008 47.7349 24.5138 53.2021C27.7234 58.6637 33.5291 62 39.7786 62H40.3686C46.1822 62 51.6369 59.1045 54.9597 54.2545C58.2819 49.4054 59.056 43.0371 57.0347 37.5029ZM52.8748 52.7824C50.0265 56.9398 45.3513 59.4219 40.3686 59.4219H39.7786C34.4416 59.4219 29.4281 56.5325 26.6947 51.8813C23.9369 47.1886 23.8153 41.2733 26.3773 36.4436C29.1752 31.1691 32.9752 26.8193 33.4744 20.662C37.803 21.265 41.1483 25.0441 41.1483 29.6015C41.1483 31.7338 42.8572 33.4687 44.9577 33.4687C47.0581 33.4687 48.767 31.7338 48.767 29.6015V27.9161C50.54 30.2131 53.0138 33.9094 54.6534 38.399C56.3856 43.1416 55.704 48.653 52.8748 52.7824Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M38.6089 40C38.6089 37.8676 36.9 36.1328 34.7996 36.1328C32.6991 36.1328 30.9902 37.8676 30.9902 40C30.9902 42.1324 32.6991 43.8672 34.7996 43.8672C36.9 43.8672 38.6089 42.1324 38.6089 40ZM33.5298 40C33.5298 39.2892 34.0994 38.7109 34.7996 38.7109C35.4997 38.7109 36.0693 39.2892 36.0693 40C36.0693 40.7108 35.4997 41.2891 34.7996 41.2891C34.0994 41.2891 33.5298 40.7108 33.5298 40Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M44.9578 46.4453C42.8573 46.4453 41.1485 48.1801 41.1485 50.3125C41.1485 52.4449 42.8573 54.1797 44.9578 54.1797C47.0583 54.1797 48.7672 52.4449 48.7672 50.3125C48.7672 48.1801 47.0583 46.4453 44.9578 46.4453ZM44.9578 51.6016C44.2577 51.6016 43.688 51.0233 43.688 50.3125C43.688 49.6017 44.2577 49.0234 44.9578 49.0234C45.658 49.0234 46.2276 49.6017 46.2276 50.3125C46.2276 51.0233 45.658 51.6016 44.9578 51.6016Z"
                                    fill="#009ec9"></path>
                                <path d="M32.5466 52.0632L45.2407 36.599L47.1911 38.249L34.4969 53.7132L32.5466 52.0632Z"
                                    fill="#009ec9"></path>
                            </svg>
                        </div>
                        <div class="information">
                            <h4 class="title">Great Offer Daily Deal</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
            </div>
        </div>
    </div>

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
                    if (matchingCombination.gym_owner_discount > 0) {
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

