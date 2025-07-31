@extends('layouts.app')

@section('content')
    <!-- shop[ grid sidebar wrapper -->

    <div class="shop-grid-sidebar-area rts-section-gap">
        <div class="container">
            <div class="row">
                <div class="col text-end filter-toggle-button">
                    <button class="filter-toggle border border-info rounded p-2 me-4 mt-2 mb-2 w-auto">
                        <i class="fa-solid fa-filter" style="color: #009ec9;"></i>
                    </button>
                </div>
            </div>
            <div class="row g-0">
                <div class="col-xl-3 col-lg-12 pr--70 pr_lg--10 pr_sm--10 pr_md--5 rts-sticky-column-item filters">
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
                                        @if ($product && isset($product->seller) && $product->seller->status === 'approved')
                                            <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                                                <div class="single-shopping-card-one">
                                                    <!-- Image -->
                                                    <div class="image-and-action-area-wrapper">
                                                        <a href="{{ route('product.details', Crypt::encrypt($product->id)) }}"
                                                            class="thumbnail-preview">
                                                            @if ($product->discount_percentage > 0)
                                                                <div class="badge">
                                                                    <span>{{ $product->discount_percentage }}%
                                                                        <br>Off</span>
                                                                    <i class="fa-solid fa-bookmark"></i>
                                                                </div>
                                                            @endif

                                                            @php
                                                                $variantThumbnail = $product->images->first(
                                                                    fn($img) => $img->image_type ===
                                                                        'variant_thumbnail',
                                                                );
                                                            @endphp

                                                            <img src="{{ asset('storage/' . ($variantThumbnail->image_path ?? $product->thumbnail_image)) }}"
                                                                alt="{{ $product->name }}" loading="lazy">
                                                        </a>
                                                    </div>

                                                    <!-- Content -->
                                                    <div class="body-content">
                                                        <a
                                                            href="{{ route('product.details', Crypt::encrypt($product->id)) }}">
                                                            <h4 class="title">{{ $product->name }}</h4>
                                                        </a>
                                                        <div class="price-area">
                                                            <span class="current">{{ format_price($product->id) }}</span>
                                                            <div class="previous">
                                                                {{ format_price($product->id, 'actual') }}</div>
                                                        </div>

                                                        <!-- Cart -->
                                                        <div class="cart-counter-action">
                                                            <div class="quantity-edit">
                                                                <input type="number" class="input quantity-input"
                                                                    value="1" min="1">
                                                                <div class="button-wrapper-action">
                                                                    <button class="button"><i
                                                                            class="fa-regular fa-chevron-down"></i></button>
                                                                    <button class="button plus">+<i
                                                                            class="fa-regular fa-chevron-up"></i></button>
                                                                </div>
                                                            </div>
                                                            <a href="#"
                                                                class="rts-btn btn-primary radious-sm with-icon add-to-cart-btn"
                                                                data-product-id="{{ $product->id }}">
                                                                <div class="btn-text">Add To Cart</div>
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
                                @endif
                            </div>
                        </div>
                    </div>


                    @if (isset($products))
                        <div class="d-flex justify-content-center">
                            <div class="my-pagination">
                                {{ $products->links() }}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
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
                data: {
                    product_id: productId,
                    quantity: quantity,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status == 'success') {
                        Toastify({
                            text: "Product added to cart!",
                            duration: 1000,
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

        $('.filter-toggle').click(function() {
            if (window.innerWidth <= 1024) {
                $('.filters').slideToggle(200);
            }
        });
    });
</script>
