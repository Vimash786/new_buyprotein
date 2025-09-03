@extends('layouts.app')

@section('content')
<style>
    @media only screen and (min-width: 200px) and (max-width: 768px) {
        .rts-section-gap {
            padding: 30px 0;
        }
    }
    
    /* Enhanced Category Section Styles */
    .rts-caregory-area-one {
        padding: 40px 0 !important;
    }
    
    .category-area-main-wrapper-one .swiper-slide {
        height: auto;
    }
    
    /* Reduce space between category items for better utilization */
    .swiper[data-swiper*='"slidesPerView":6'] .swiper-slide {
        padding: 0 8px;
    }
    
    /* Category title adjustments */
    .title-area-between h2.title-left {
        margin-bottom: 25px !important;
        font-size: 28px;
        font-weight: 700;
    }
    
    /* Mobile responsiveness for categories */
    @media (max-width: 768px) {
        .rts-caregory-area-one {
            padding: 30px 0 !important;
        }
        
        .title-area-between h2.title-left {
            font-size: 24px;
            margin-bottom: 20px !important;
        }
        
        .swiper[data-swiper*='"slidesPerView":6'] .swiper-slide {
            padding: 0 6px;
        }
    }
    
    @media (max-width: 480px) {
        .swiper[data-swiper*='"slidesPerView":6'] .swiper-slide {
            padding: 0 4px;
        }
    }
    
    /* Price area modifications */
    .price-area {
        display: flex;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 2px;
    }
    
    .price-area .current {
        font-weight: 700;
        color: #009ec9;
        font-size: 16px;
        order: 1;
    }
    
    .price-area .previous {
        text-decoration: line-through;
        color: #999;
        font-size: 12px;
        order: 2;
        margin-top: 2px;
    }
    
    /* Navigation Arrows Styling */
    .product-swiper-container {
        position: relative;
        padding: 0 60px;
    }
    
    .swiper-button-next,
    .swiper-button-prev {
        width: 40px !important;
        height: 40px !important;
        background: #009ec9 !important;
        border-radius: 50% !important;
        color: white !important;
        font-size: 14px !important;
        margin-top: -20px !important;
        box-shadow: 0 2px 8px #009ec9 !important;
        transition: all 0.3s ease !important;
        z-index: 10 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .swiper-button-next {
        right: 5px !important;
    }
    
    .swiper-button-prev {
        left: 5px !important;
    }
    
    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        background: #009ec9 !important;
        transform: scale(1.1) !important;
        box-shadow: 0 4px 12px #009ec9 !important;
    }
    
    .swiper-button-next::after,
    .swiper-button-prev::after {
        font-size: 16px !important;
        font-weight: bold !important;
        color: white !important;
    }

    /* Pagination Dots Styling */
    .swiper-pagination {
        position: relative !important;
        margin-top: 20px !important;
        text-align: center !important;
    }
    
    .swiper-pagination-bullet {
        width: 12px !important;
        height: 12px !important;
        background: #ddd !important;
        border-radius: 50% !important;
        margin: 0 6px !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
    }
    
    .swiper-pagination-bullet-active {
        background: #009ec9 !important;
        transform: scale(1.2) !important;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4) !important;
    }
    
    /* Mobile responsiveness for navigation */
    @media (max-width: 768px) {
        .product-swiper-container {
            padding: 0 50px;
        }
        
        .swiper-button-next,
        .swiper-button-prev {
            width: 35px !important;
            height: 35px !important;
            margin-top: -17px !important;
        }
        
        .swiper-button-next {
            right: 5px !important;
        }
        
        .swiper-button-prev {
            left: 5px !important;
        }
        
        .swiper-button-next::after,
        .swiper-button-prev::after {
            font-size: 14px !important;
        }
        
        .swiper-pagination-bullet {
            width: 10px !important;
            height: 10px !important;
            margin: 0 4px !important;
        }
    }
    
    /* Ensure arrows are always visible */
    .swiper-button-disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
    
    .swiper-button-disabled:hover {
        transform: none !important;
    }
</style>
    {{-- Category Section Start --}}
    <div class="background-light-gray-color rts-section-gap bg_light-1">
        <!-- rts banner area start -->
        <div class="rts-banner-area-one">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="category-area-main-wrapper-one">
                            <div class="swiper mySwiper-category-1 swiper-data"
                                data-swiper='{
                                "spaceBetween":1,
                                "slidesPerView":1,
                                "loop": true,
                                "speed": 2000,
                                "autoplay":{
                                    "delay":"4000"
                                },
                                "navigation":{
                                    "nextEl":".swiper-button-next",
                                    "prevEl":".swiper-button-prev"
                                },
                                "breakpoints":{
                                "0":{
                                    "slidesPerView":1,
                                    "spaceBetween": 0},
                                "320":{
                                    "slidesPerView":1,
                                    "spaceBetween":0},
                                "480":{
                                    "slidesPerView":1,
                                    "spaceBetween":0},
                                "640":{
                                    "slidesPerView":1,
                                    "spaceBetween":0},
                                "840":{
                                    "slidesPerView":1,
                                    "spaceBetween":0},
                                "1140":{
                                    "slidesPerView":1,
                                    "spaceBetween":0}
                                }
                            }'>
                                <div class="swiper-wrapper">
                                    @foreach ($banners as $banner)
                                        <div class="swiper-slide">
                                            <a href="{{ $banner['redirect_link'] }}">
                                                <div class="banner-bg-image ptb--120 ptb_md--80 ptb_sm--60"
                                                    style="background-image: url('{{ asset('storage/' . $banner['banner_image']) }}'); background-size: cover; background-position: center;">
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                <button class="swiper-button-next"><i class="fa-regular fa-arrow-right"></i></button>
                                <button class="swiper-button-prev"><i class="fa-regular fa-arrow-left"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- rts banner area end -->
         
        <!-- rts category area satart -->
        <div class="rts-caregory-area-one ">
            <div class="container">
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <div class="title-area-between">
                            <h2 class="title-left mb--10">
                                Categories
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="category-area-main-wrapper-one">
                            <div class="swiper mySwiper-category-1 swiper-data"
                                data-swiper='{
                                "spaceBetween":8,
                                "slidesPerView":6,
                                "loop": true,
                                "speed": 2000,
                                "autoplay":{
                                    "delay":"4000"
                                },
                                "navigation":{
                                    "nextEl":".swiper-button-next",
                                    "prevEl":".swiper-button-prev"
                                },
                                "breakpoints":{
                                "0":{
                                    "slidesPerView":2,
                                    "spaceBetween": 8},
                                "468":{
                                    "slidesPerView":2,
                                    "spaceBetween":8},
                                "638":{
                                    "slidesPerView":3,
                                    "spaceBetween":8},
                                "640":{
                                    "slidesPerView":4,
                                    "spaceBetween":8},
                                "840":{
                                    "slidesPerView":5,
                                    "spaceBetween":8},
                                "1140":{
                                    "slidesPerView":6,
                                    "spaceBetween":8}
                                }
                            }'>
                                <div class="swiper-wrapper">
                                    <!-- single swiper start -->
                                    @foreach ($categories as $category)
                                        <div class="swiper-slide">
                                            <a href="{{ route('shop', ['type' => 'category', 'id' => Crypt::encrypt($category->id)]) }}"
                                                class="single-category-card">
                                                <div class="category-image-wrapper">
                                                    <img src="{{ asset('storage/' . $category->image) }}"
                                                        alt="{{ $category->name }}">
                                                </div>
                                                <div class="category-content">
                                                    <h5 class="category-title">{{ $category->name }}</h5>
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                    <!-- single swiper end -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- rts category area end -->
    </div>
    {{-- Category Section End --}}

    <!-- rts grocery feature every day products area start -->
    <div class="rts-grocery-feature-area rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 mt-5">
                    <div class="title-area-between">
                        <h2 class="title-left">
                            Everyday essential
                        </h2>
                        {{-- <div class="next-prev-swiper-wrapper">
                            <div class="swiper-button-prev"><i class="fa-regular fa-chevron-left"></i></div>
                            <div class="swiper-button-next"><i class="fa-regular fa-chevron-right"></i></div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="category-area-main-wrapper-one product-swiper-container">
                        <div class="swiper mySwiper-everyday-essentials swiper-data"
                            data-swiper='{
                            "spaceBetween":16,
                            "slidesPerView":6,
                            "loop": false,
                            "speed": 700,
                            "navigation":{
                                "nextEl":".everyday-essentials-next",
                                "prevEl":".everyday-essentials-prev"
                              },
                            "pagination":{
                                "el":".everyday-essentials-pagination",
                                "clickable": true
                              },
                            "breakpoints":{
                            "0":{
                                "slidesPerView":1,
                                "spaceBetween": 12},
                            "380":{
                                "slidesPerView":2,
                                "spaceBetween":12},
                            "320":{
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
                                {{-- Debug: Show everyday essentials count --}}
                                @if($everyDayEssentials->isEmpty())
                                    <div class="swiper-slide">
                                        <div class="alert alert-info">
                                            No everyday essentials available at the moment.
                                        </div>
                                    </div>
                                @endif
                                
                                @foreach ($everyDayEssentials as $everyDayProduct)
                                    @if (isset($everyDayProduct->seller) && $everyDayProduct->seller->status == 'approved')
                                        <div class="swiper-slide">
                                            <div class="single-shopping-card-one">
                                                <!-- iamge and sction area start -->
                                                <div class="image-and-action-area-wrapper">
                                                    <a href="{{ route('product.details', Crypt::encrypt($everyDayProduct->id)) }}"
                                                        class="thumbnail-preview">
                                                        @if (has_discount($everyDayProduct->id))
                                                            <div class="badge">
                                                                <span>{{ get_discount_percentage($everyDayProduct->id) }}% <br>
                                                                    Off
                                                                </span>
                                                                <i class="fa-solid fa-bookmark"></i>
                                                            </div>
                                                        @endif
                                                        @php
                                                            $variantThumbnail = $everyDayProduct->images->first(
                                                                function ($img) {
                                                                    return $img->image_type === 'variant_thumbnail';
                                                                },
                                                            );
                                                        @endphp

                                                        @if ($everyDayProduct->variants->count() > 0)
                                                            @if ($variantThumbnail)
                                                                <img src="{{ asset('storage/' . $variantThumbnail->image_path) }}"
                                                                    alt="product">
                                                            @endif
                                                        @else
                                                            <img src="{{ asset('storage/' . $everyDayProduct->thumbnail_image) }}"
                                                                alt="product">
                                                        @endif
                                                        {{-- <img src="assets/images/grocery/01.jpg" alt="grocery"> --}}
                                                    </a>
                                                </div>
                                                <!-- iamge and sction area start -->

                                                <div class="body-content">

                                                    <a
                                                        href="{{ route('product.details', Crypt::encrypt($everyDayProduct->id)) }}">
                                                        <h4 class="title">{{ $everyDayProduct->name }}</h4>
                                                    </a>
                                                    <div class="price-area">
                                                        <div class="current">{{ format_price($everyDayProduct->id) }}</div>
                                                        @if(has_discount($everyDayProduct->id))
                                                            <div class="previous">
                                                                {{ format_price($everyDayProduct->id, 'actual') }}
                                                            </div>
                                                        @endif
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
                                                            class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
                                                            data-product-id="{{ $everyDayProduct->id }}">
                                                            <div class="btn-text">
                                                                Add
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
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <!-- Navigation buttons -->
                        <button class="swiper-button-next everyday-essentials-next"><i class="fa-regular fa-arrow-right"></i></button>
                        <button class="swiper-button-prev everyday-essentials-prev"><i class="fa-regular fa-arrow-left"></i></button>
                        
                    </div>
                </div>
            </div>
        </div>
        <!-- Pagination dots -->
        <div class="swiper-pagination everyday-essentials-pagination"></div>
        <hr class="mt-3 mx-4">
        <div class="text-center mt-5 view-all">
            <div class=""><a href="{{ route('shop', ['type' => 'everyday-essential']) }}"
                    class="bg-light p-3">View All ></a></div>
        </div>
    </div>
    <!-- rts grocery feature area end -->

    <!-- rts grocery feature area start -->
    <div class="rts-grocery-feature-area rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-area-between">
                        <h2 class="title-left">
                            Popular Picks
                        </h2>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="category-area-main-wrapper-one product-swiper-container">
                        <div class="swiper mySwiper-popular-picks swiper-data"
                            data-swiper='{
                                "spaceBetween": 16,
                                "slidesPerView": 6,
                                "loop": false,
                                "speed": 700,
                                "navigation": {
                                    "nextEl": ".popular-picks-next",
                                    "prevEl": ".popular-picks-prev"
                                },
                                "pagination": {
                                    "el": ".popular-picks-pagination",
                                    "clickable": true
                                },
                                "breakpoints": {
                                    "0": { "slidesPerView": 1, "spaceBetween": 12 },
                                    "320": { "slidesPerView": 2, "spaceBetween": 12 },
                                    "480": { "slidesPerView": 2, "spaceBetween": 12 },
                                    "640": { "slidesPerView": 3, "spaceBetween": 16 },
                                    "840": { "slidesPerView": 4, "spaceBetween": 16 },
                                    "1540": { "slidesPerView": 6, "spaceBetween": 16 }
                                }
                            }'>
                            <div class="swiper-wrapper">
                                {{-- Debug: Show popular picks count --}}
                                @if($populerProducts->isEmpty())
                                    <div class="swiper-slide">
                                        <div class="alert alert-info">
                                            No popular picks available at the moment.
                                        </div>
                                    </div>
                                @endif
                                
                                @foreach ($populerProducts as $populerProduct)
                                    @if (isset($populerProduct->seller) && $populerProduct->seller->status == 'approved')
                                        <div class="swiper-slide">
                                            <div class="single-shopping-card-one">
                                                <!-- Image & Badge -->
                                                <div class="image-and-action-area-wrapper">
                                                    <a href="{{ route('product.details', Crypt::encrypt($populerProduct->id)) }}"
                                                        class="thumbnail-preview">
                                                        @if (has_discount($populerProduct->id))
                                                            <div class="badge">
                                                                <span>{{ get_discount_percentage($populerProduct->id) }}%<br>Off</span>
                                                                <i class="fa-solid fa-bookmark"></i>
                                                            </div>
                                                        @endif

                                                        @php
                                                            $variantThumbnail = $populerProduct->images->first(
                                                                function ($img) {
                                                                    return $img->image_type === 'variant_thumbnail';
                                                                },
                                                            );
                                                        @endphp

                                                        @if ($populerProduct->variants->count() > 0)
                                                            @if ($variantThumbnail)
                                                                <img src="{{ asset('storage/' . $variantThumbnail->image_path) }}"
                                                                    alt="product">
                                                            @endif
                                                        @else
                                                            <img src="{{ asset('storage/' . $populerProduct->thumbnail_image) }}"
                                                                alt="product">
                                                        @endif
                                                    </a>
                                                </div>

                                                <!-- Product Body -->
                                                <div class="body-content">
                                                    <a
                                                        href="{{ route('product.details', Crypt::encrypt($populerProduct->id)) }}">
                                                        <h4 class="title">{{ $populerProduct->name }}</h4>
                                                    </a>
                                                    <div class="price-area">
                                                        <div class="current">{{ format_price($populerProduct->id) }}</div>
                                                        @if(has_discount($populerProduct->id))
                                                            <div class="previous">
                                                                {{ format_price($populerProduct->id, 'actual') }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <!-- Quantity & Cart -->
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
                                                            class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
                                                            data-product-id="{{ $populerProduct->id }}">
                                                            <div class="btn-text">
                                                                Add
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
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <!-- Navigation buttons -->
                        <button class="swiper-button-next popular-picks-next"><i class="fa-regular fa-arrow-right"></i></button>
                        <button class="swiper-button-prev popular-picks-prev"><i class="fa-regular fa-arrow-left"></i></button>
                    </div>

                </div>
            </div>
        </div>
          <!-- Pagination dots -->
          <div class="swiper-pagination popular-picks-pagination"></div>
        <hr class="mt-3 mx-4">
        <div class="text-center mt-5 view-all">
             <div class=""><a href="{{ route('shop', ['type' => 'popular-picks']) }}"
                     class="bg-light p-3">View All ></a></div>
         </div>
    </div>
    <!-- rts grocery feature area end -->
    <!-- best selling groceris -->
    <div class="weekly-best-selling-area rts-section-gap bg_light-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-area-between">
                        <h2 class="title-left">
                            New Arrival
                        </h2>
                    </div>
                </div>
            </div>
            <div class="container">   
                <div class="row">
                    <div class="col-lg-12">
                        <div class="tab-content" id="myTabContent">
                            <!-- first tabs area start-->
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">
                                <div class="row g-4">
                                    <div class="category-area-main-wrapper-one product-swiper-container">
                                        <div class="swiper mySwiper-new-arrivals swiper-data"
                                            data-swiper='{
                                                "spaceBetween": 16,
                                                "slidesPerView": 6,
                                                "loop": false,
                                                "speed": 700,
                                                "navigation": {
                                                    "nextEl": ".new-arrivals-next",
                                                    "prevEl": ".new-arrivals-prev"
                                                },
                                                "pagination": {
                                                    "el": ".new-arrivals-pagination",
                                                    "clickable": true
                                                },
                                                "breakpoints": {
                                                    "0": { "slidesPerView": 1, "spaceBetween": 12 },
                                                    "320": { "slidesPerView": 2, "spaceBetween": 12 },
                                                    "480": { "slidesPerView": 2, "spaceBetween": 12 },
                                                    "640": { "slidesPerView": 3, "spaceBetween": 16 },
                                                    "840": { "slidesPerView": 4, "spaceBetween": 16 },
                                                    "1540": { "slidesPerView": 6, "spaceBetween": 16 }
                                                }
                                            }'>
                                            <div class="swiper-wrapper">
                                                {{-- Debug: Show new arrivals count --}}
                                                @if($latestProducts->isEmpty())
                                                    <div class="swiper-slide">
                                                        <div class="alert alert-info">
                                                            No new arrivals available at the moment.
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @foreach ($latestProducts as $lat_pro)
                                                    @if (isset($lat_pro->seller) && $lat_pro->seller->status == 'approved')
                                                        <div class="swiper-slide">
                                                            <div class="single-shopping-card-one">
                                                                <!-- iamge and sction area start -->
                                                                <div class="image-and-action-area-wrapper">
                                                                    <a href="{{ route('product.details', Crypt::encrypt($lat_pro->id)) }}"
                                                                        class="thumbnail-preview">
                                                                        @if (has_discount($lat_pro->id))
                                                                            <div class="badge">
                                                                                <span>{{ get_discount_percentage($lat_pro->id) }}% <br>
                                                                                    Off
                                                                                </span>
                                                                                <i class="fa-solid fa-bookmark"></i>
                                                                            </div>
                                                                        @endif
                                                                        @php
                                                                            $variantThumbnail = $lat_pro->images->first(
                                                                                function ($img) {
                                                                                    return $img->image_type ===
                                                                                        'variant_thumbnail';
                                                                                },
                                                                            );
                                                                        @endphp

                                                                        @if ($lat_pro->variants->count() > 0)
                                                                            @if ($variantThumbnail)
                                                                                <img src="{{ asset('storage/' . $variantThumbnail->image_path) }}"
                                                                                    alt="product">
                                                                            @endif
                                                                        @else
                                                                            <img src="{{ asset('storage/' . $lat_pro->thumbnail_image) }}"
                                                                                alt="product">
                                                                        @endif
                                                                        {{-- <img src="{{ asset('storage/' . $lat_pro->thumbnail_image) }}"
                                                            alt="product"> --}}
                                                                    </a>
                                                                </div>
                                                                <!-- iamge and sction area start -->
                                                                <div class="body-content">

                                                                    <a
                                                                        href="{{ route('product.details', Crypt::encrypt($lat_pro->id)) }}">
                                                                        <h4 class="title">{{ $lat_pro->name }}</h4>
                                                                    </a>
                                                                    <div class="price-area">
                                                                        <span
                                                                            class="current">{{ format_price($lat_pro->id) }}</span>
                                                                        @if(has_discount($lat_pro->id))
                                                                            <div class="previous">
                                                                                {{ format_price($lat_pro->id, 'actual') }}
                                                                            </div>
                                                                        @endif
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
                                                                            class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
                                                                            data-product-id="{{ $lat_pro->id }}">
                                                                            <div class="btn-text">
                                                                                Add
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
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <!-- Navigation buttons -->
                                        <button class="swiper-button-next new-arrivals-next"><i class="fa-regular fa-arrow-right"></i></button>
                                        <button class="swiper-button-prev new-arrivals-prev"><i class="fa-regular fa-arrow-left"></i></button>
                                        
                                    </div>
                                </div>
                                <!-- first tabs area start-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pagination dots -->
        <div class="swiper-pagination new-arrivals-pagination"></div>
        <hr class="mt-3">
            <div class="text-center mt-5 view-all">
                <div class=""><a href="{{ route('shop', ['type' => 'new-arrivals']) }}"
                        class="bg-light p-3">View All ></a></div>
            </div>
        <!-- best selling groceris end -->

        <!-- rts category feature area start -->
        <div class="category-feature-area rts-section-gapTop">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="title-area-between text-center mb-4">
                            <h2 class="title-left mb--10">Shop by Brand</h2>
                        </div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="category-area-main-wrapper-one product-swiper-container">
                        <div class="swiper mySwiper-shop-by-brand swiper-data"
                            data-swiper='{
                                "spaceBetween": 16,
                                "slidesPerView": 6,
                                "loop": false,
                                "speed": 700,
                                "navigation": {
                                    "nextEl": ".shop-by-brand-next",
                                    "prevEl": ".shop-by-brand-prev"
                                },
                                "pagination": {
                                    "el": ".shop-by-brand-pagination",
                                    "clickable": true
                                },
                                "breakpoints": {
                                    "0": { "slidesPerView": 1, "spaceBetween": 12 },
                                    "320": { "slidesPerView": 2, "spaceBetween": 12 },
                                    "480": { "slidesPerView": 2, "spaceBetween": 12 },
                                    "640": { "slidesPerView": 3, "spaceBetween": 16 },
                                    "840": { "slidesPerView": 4, "spaceBetween": 16 },
                                    "1540": { "slidesPerView": 6, "spaceBetween": 16 }
                                }
                            }'>
                            <div class="swiper-wrapper">
                               @foreach ($sellers as $seller)
                                    <div class="swiper-slide">
                                        <a href="{{ route('shop', ['type' => 'brand', 'id' => Crypt::encrypt($seller->id)]) }}"
                                        class="brand-card">
                                            <div class="brand-logo-wrapper">
                                                <img src="{{ asset('storage/' . $seller->brand_logo) }}"
                                                    alt="{{ $seller->brand }}"
                                                    class="brand-logo" />
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <!-- Navigation buttons -->
                        <button class="swiper-button-next shop-by-brand-next"><i class="fa-regular fa-arrow-right"></i></button>
                        <button class="swiper-button-prev shop-by-brand-prev"><i class="fa-regular fa-arrow-left"></i></button>
                        <!-- Pagination dots -->
                        <div class="swiper-pagination shop-by-brand-pagination"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- rts category feature area end -->

        <!-- rts top tranding product area -->
        <div class="top-tranding-product rts-section-gap">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="title-area-between">
                            <h2 class="title-left mb--10">
                                Exclusive deal & offers
                            </h2>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="container">
                    <div class="row">
                    <div class="col-lg-12">
                        <div class="category-area-main-wrapper-one product-swiper-container">
                            <div class="swiper mySwiper-exclusive-deals swiper-data"
                                data-swiper='{
                                    "spaceBetween": 16,
                                    "slidesPerView": 6,
                                    "loop": false,
                                    "speed": 700,
                                    "navigation": {
                                        "nextEl": ".exclusive-deals-next",
                                        "prevEl": ".exclusive-deals-prev"
                                    },
                                    "pagination": {
                                        "el": ".exclusive-deals-pagination",
                                        "clickable": true
                                    },
                                    "breakpoints": {
                                        "0": { "slidesPerView": 1, "spaceBetween": 12 },
                                        "320": { "slidesPerView": 2, "spaceBetween": 12 },
                                        "480": { "slidesPerView": 2, "spaceBetween": 12 },
                                        "640": { "slidesPerView": 3, "spaceBetween": 16 },
                                        "840": { "slidesPerView": 4, "spaceBetween": 16 },
                                        "1540": { "slidesPerView": 6, "spaceBetween": 16 }
                                    }
                                }'>
                                <div class="swiper-wrapper">
                                    {{-- Debug: Show offers count --}}
                                    @if($offers->isEmpty())
                                        <div class="swiper-slide">
                                            <div class="alert alert-info">
                                                No exclusive deals available at the moment.
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @foreach ($offers as $offer)
                                        <div class="swiper-slide">
                                            <div class="single-shopping-card-one">

                                                <!-- iamge and sction area start -->
                                                <div class="image-and-action-area-wrapper">
                                                    <a href="{{ route('product.details', Crypt::encrypt($offer->id)) }}"
                                                        class="thumbnail-preview">
                                                        @if (has_discount($offer->id))
                                                            <div class="badge">
                                                                <span>{{ get_discount_percentage($offer->id) }}% <br>
                                                                    Off
                                                                </span>
                                                                <i class="fa-solid fa-bookmark"></i>
                                                            </div>
                                                        @endif
                                                        @php
                                                            $variantThumbnail = $offer->images->first(function ($img) {
                                                                return $img->image_type === 'variant_thumbnail';
                                                            });
                                                        @endphp

                                                        @if ($offer->variants->count() > 0)
                                                            @if ($variantThumbnail)
                                                                <img src="{{ asset('storage/' . $variantThumbnail->image_path) }}"
                                                                    alt="product">
                                                            @endif
                                                        @else
                                                            <img src="{{ asset('storage/' . $offer->thumbnail_image) }}"
                                                                alt="product">
                                                        @endif
                                                        {{-- <img src="{{ asset('storage/' . $offer->thumbnail_image) }}" alt="product"> --}}
                                                    </a>
                                                </div>

                                                <div class="body-content">

                                                    <a href="{{ route('product.details', Crypt::encrypt($offer->id)) }}">
                                                        <h4 class="title">{{ $offer->name }}</h4>
                                                    </a>
                                                    <div class="price-area">
                                                        <span class="current">{{ format_price($offer->id) }}</span>
                                                        @if(has_discount($offer->id))
                                                            <div class="previous">
                                                                {{ format_price($offer->id, 'actual') }}
                                                            </div>
                                                        @endif
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
                                                            class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
                                                            data-product-id="{{ $offer->id }}">
                                                            <div class="btn-text">
                                                                Add
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
                            <!-- Navigation buttons -->
                             <button class="swiper-button-next exclusive-deals-next"><i class="fa-regular fa-arrow-right"></i></button>
                             <button class="swiper-button-prev exclusive-deals-prev"><i class="fa-regular fa-arrow-left"></i></button>
                           
                        </div>
                    </div>
                    </div>
            </div>
             <!-- Pagination dots -->
             <div class="swiper-pagination exclusive-deals-pagination"></div>
            <hr class="mt-3 mx-4">
              <div class="text-center mt-5 view-all">
                <div class=""><a href="{{ route('shop', ['type' => '']) }}"
                        class="bg-light p-3">View All ></a></div>
            </div>
        </div>
        <!-- rts top tranding product area end -->

    
    @endsection

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.add-to-cart-btn').on('click', function(e) {
                e.preventDefault();

                const productId = $(this).data('product-id');
                const quantity = $(this).closest('.cart-counter-action').find('.quantity-input').val() || 1;

                $.ajax({
                    url: '{{ route('cart.add') }}',
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        product_id: productId,
                        quantity: parseInt(quantity),
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

            // Handle quantity increment/decrement buttons
            
        });
    </script>
