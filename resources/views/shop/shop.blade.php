@extends('layouts.app')

@section('content')
    <!-- shop[ grid sidebar wrapper -->
    <div class="shop-grid-sidebar-area rts-section-gap">
        <div class="container">
            <div class="row g-0">
                <div class="col-xl-3 col-lg-12 pr--70 pr_lg--10 pr_sm--10 pr_md--5 rts-sticky-column-item">
                    <div class="sidebar-filter-main theiaStickySidebar">
                        <div class="single-filter-box">
                            <h5 class="title">Price Filter</h5>
                            <div class="filterbox-body">
                                <form method="GET" action="{{ url()->current() }}" class="price-input-area">
                                    <input type="hidden" name="type" value="{{ request('type') }}">
                                    <input type="hidden" name="id" value="{{ request('id') }}">

                                    <div class="half-input-wrapper">
                                        <div class="single">
                                            <label for="min">Min price</label>
                                            <input id="min" name="min_price" type="number"
                                                value="{{ request('min_price', 0) }}">
                                        </div>
                                        <div class="single">
                                            <label for="max">Max price</label>
                                            <input id="max" name="max_price" type="number"
                                                value="{{ request('max_price', 1000) }}">
                                        </div>
                                    </div>

                                    <div class="filter-value-min-max">
                                        <button type="submit" class="rts-btn btn-primary mt-5 mx-auto">Filter</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                        <div class="single-filter-box">
                            <h5 class="title">Product Categories</h5>
                            <div class="filterbox-body">
                                <div class="category-wrapper">
                                    @foreach ($categories as $cat)
                                        <div class="single-category">
                                            <input type="checkbox" id="cat{{ $cat->id }}" name="category"
                                                value="{{ $cat->id }}"
                                                {{ request()->route('type') === 'category' && request()->route('id') == $cat->id ? 'checked' : '' }}
                                                onchange="window.location.href='{{ url('/shop/category') }}/{{ Crypt::encrypt($cat->id) }}'">
                                            <label for="cat{{ $cat->id }}">{{ $cat->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="single-filter-box">
                            <h5 class="title">Select Brands</h5>
                            <div class="filterbox-body">
                                <div class="category-wrapper">
                                    @foreach ($brands as $brand)
                                        <div class="single-category">
                                            <input type="checkbox" id="brand{{ $brand->id }}" name="brand"
                                                value="{{ $brand->id }}"
                                                {{ request()->route('type') === 'brand' && request()->route('id') == $brand->id ? 'checked' : '' }}
                                                onchange="window.location.href='{{ url('/shop/brand') }}/{{ Crypt::encrypt($brand->id) }}'">
                                            <label for="brand{{ $brand->id }}">{{ $brand->brand }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-9 col-lg-12">
                    <div class="tab-content" id="myTabContent">
                        <div class="product-area-wrapper-shopgrid-list mt--20 tab-pane fade show active" id="home-tab-pane"
                            role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                            <div class="row g-4">
                                @if (isset($products))
                                    @foreach ($products as $product)
                                        <div class="col-lg-20 col-lg-4 col-md-6 col-sm-6 col-12">
                                            <div class="single-shopping-card-one">
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
                                                    <div class="action-share-option">
                                                        <div class="single-action openuptip message-show-action"
                                                            data-flow="up" title="Add To Wishlist">
                                                            <i class="fa-light fa-heart"></i>
                                                        </div>
                                                        <div class="single-action openuptip" data-flow="up" title="Compare"
                                                            data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                            <i class="fa-solid fa-arrows-retweet"></i>
                                                        </div>
                                                        <div class="single-action openuptip cta-quickview product-details-popup-btn"
                                                            data-flow="up" title="Quick View">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="body-content">

                                                    <a href="{{ route('product.details', Crypt::encrypt($product->id)) }}">
                                                        <h4 class="title">{{ $product->name }}</h4>
                                                    </a>
                                                    <span class="availability">500g Pack</span>
                                                    <div class="price-area">
                                                        <span class="current">₹{{ $product->regular_user_final_price }}</span>
                                                        <div class="previous">₹{{ $product->regular_user_price }}</div>
                                                    </div>
                                                    <div class="cart-counter-action">
                                                        <div class="quantity-edit">
                                                            <input type="text" class="input" value="1">
                                                            <div class="button-wrapper-action">
                                                                <button class="button"><i
                                                                        class="fa-regular fa-chevron-down"></i></button>
                                                                <button class="button plus">+<i
                                                                        class="fa-regular fa-chevron-up"></i></button>
                                                            </div>
                                                        </div>
                                                        <a href="#"
                                                            class="rts-btn btn-primary radious-sm with-icon">
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
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (isset($products))
                        <div class="d-flex justify-content-center">
                            {{ $products->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
