@extends('layouts.app')

@section('content')
    <div class="rts-navigation-area-breadcrumb bg_light-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navigator-breadcrumb-wrapper">
                        <a href="index.html">Home</a>
                        <i class="fa-regular fa-chevron-right"></i>
                        <a class="current" href="index.html">Wishlist</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-seperator bg_light-1">
        <div class="container">
            <hr class="section-seperator">
        </div>
    </div>



    <!-- rts cart area start -->
    <div class="rts-cart-area rts-section-gap bg_light-1">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-12">
                    <div class="rts-cart-list-area wishlist">
                        @if($wishlistData->isNotEmpty())
                            <div class="single-cart-area-list head">
                                <div class="product-main">
                                    <P>Products</P>
                                </div>
                                <div class="price">
                                    <p>Price</p>
                                </div>
                                <div class="quantity">
                                    <p>Quantity</p>
                                </div>
                                <div class="subtotal">
                                    <p>SubTotal</p>
                                </div>
                                <div class="button-area">

                                </div>
                            </div>
                            @foreach ($wishlistData as $listData)
                                <div class="single-cart-area-list main  item-parent wishlist-item"
                                    id="wishlist-item-{{ $listData->id }}">
                                    <div class="product-main-cart">
                                        <div class="close section-activation remove-wishlist-item"
                                            data-id="{{ $listData->id }}">
                                            <img src="assets/images/shop/01.png" alt="shop">
                                        </div>
                                        <div class="thumbnail">
                                            @if ($listData->product->has_variants == 1)
                                                @php
                                                    $variantImage = $listData->getVariantImage();
                                                @endphp

                                                @if ($variantImage)
                                                    <img src="{{ asset('storage/' . $variantImage->image_path) }}"
                                                        alt="Variant Image">
                                                @else
                                                    <img src="{{ asset('storage/' . $listData->product->thumbnail_image) }}"
                                                        alt="Default Product Image">
                                                @endif
                                            @else
                                                <img src="{{ asset('storage/' . $listData->product->thumbnail_image) }}"
                                                    alt="shop">
                                            @endif
                                        </div>
                                        <div class="information">
                                            <h6 class="title">{{ $listData->product->name }}</h6>
                                        </div>
                                    </div>
                                    <div class="price">
                                        <p>₹{{ $listData->price }}</p>
                                    </div>
                                    <div class="quantity">
                                        <div class="quantity-edit" data-id="{{ $listData->id }}">
                                            <input type="text" class="input quantity-input" value="{{ $listData->quantity }}"
                                                readonly>
                                            <div class="button-wrapper-action">
                                                <button class="button decrement"><i
                                                        class="fa-regular fa-chevron-down"></i></button>
                                                <button class="button increment">+<i
                                                        class="fa-regular fa-chevron-up"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="subtotal">
                                        <p data-subtotal-id="{{ $listData->id }}">
                                            ₹{{ $listData->price * $listData->quantity }}
                                        </p>
                                    </div>
                                    <div class="button-area">
                                        <a href="#" class="rts-btn btn-primary radious-sm with-icon move-to-cart"
                                            data-id="{{ $listData->id }}">
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
                            @endforeach
                        @else
                            <!-- Empty Wishlist State -->
                            <div class="empty-wishlist-state text-center py-5">
                                <div class="empty-wishlist-icon mb-4">
                                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="60" cy="60" r="60" fill="#f8f9fa"/>
                                        <path d="M60 35c-8.5 0-15 6.5-15 15 0 15 15 25 15 25s15-10 15-25c0-8.5-6.5-15-15-15z" fill="#dee2e6"/>
                                        <circle cx="60" cy="47" r="6" fill="#adb5bd"/>
                                        <path d="M45 75h30v5H45zm5-5h20v3H50z" fill="#e9ecef"/>
                                    </svg>
                                </div>
                                <h3 class="empty-wishlist-title mb-3" style="color: #6c757d; font-weight: 600;">Your Wishlist is Empty</h3>
                                <p class="empty-wishlist-message mb-4" style="color: #868e96; font-size: 16px;">
                                    You haven't saved any items to your wishlist yet.<br>
                                    Browse our products and save your favorites for later!
                                </p>
                                <div class="empty-wishlist-actions">
                                    <a href="{{ route('shop') }}" class="rts-btn btn-primary my-2"  style="display:unset; padding: 12px 30px; font-weight: 500;">
                                        <i class="fa-regular fa-shopping-bag me-2"></i>
                                         Browse Products
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rts cart area end -->
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        $('.increment, .decrement').click(function() {
            const $wrapper = $(this).closest('.quantity-edit');
            const itemId = $wrapper.data('id');
            const $input = $wrapper.find('.quantity-input');
            let currentQty = parseInt($input.val());

            // Determine if increment or decrement
            if ($(this).hasClass('increment')) {
                currentQty += 1;
            } else if (currentQty > 1) {
                currentQty -= 1;
            }

            // AJAX update
            $.ajax({
                url: '{{ route('wishlist.updateQuantity') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: itemId,
                    quantity: currentQty
                },
                success: function(res) {
                    if (res.success) {
                        $input.val(res.quantity);
                        $(`p[data-subtotal-id="${res.item_id}"]`).text(
                            `₹${res.subtotal.toFixed(2)}`);

                        Toastify({
                            text: "Quantity is updated successfully.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#009ec9",
                        }).showToast();
                    }
                },
                error: function() {
                    Toastify({
                        text: "Failed to update quantity. Try again.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                }
            });
        });

        $('.remove-wishlist-item').click(function() {
            const $item = $(this).closest('.item-parent');
            const id = $(this).data('id');

            $.ajax({
                url: '{{ route('wishlist.remove') }}',
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id
                },
                success: function(res) {
                    if (res.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if wishlist is empty
                            if ($('.single-cart-area-list.main').length === 0) {
                                $('.rts-cart-list-area.wishlist').html(`
                                    <div class="empty-wishlist-state text-center py-5">
                                        <div class="empty-wishlist-icon mb-4">
                                            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="60" cy="60" r="60" fill="#f8f9fa"/>
                                                <path d="M60 35c-8.5 0-15 6.5-15 15 0 15 15 25 15 25s15-10 15-25c0-8.5-6.5-15-15-15z" fill="#dee2e6"/>
                                                <circle cx="60" cy="47" r="6" fill="#adb5bd"/>
                                                <path d="M45 75h30v5H45zm5-5h20v3H50z" fill="#e9ecef"/>
                                            </svg>
                                        </div>
                                        <h3 class="empty-wishlist-title mb-3" style="color: #6c757d; font-weight: 600;">Your Wishlist is Empty</h3>
                                        <p class="empty-wishlist-message mb-4" style="color: #868e96; font-size: 16px;">
                                            You haven't saved any items to your wishlist yet.<br>
                                            Browse our products and save your favorites for later!
                                        </p>
                                        <div class="empty-wishlist-actions">
                                            <a href="{{ route('shop') }}" class="rts-btn btn-primary me-3" style="padding: 12px 30px; font-weight: 500;">
                                                <i class="fa-regular fa-shopping-bag me-2"></i>
                                                Browse Products
                                            </a>
                                            <a href="{{ route('shop', ['type' => 'categories']) }}" class="rts-btn btn-outline-primary" style="padding: 12px 30px; font-weight: 500;">
                                                <i class="fa-regular fa-list me-2"></i>
                                                View Categories
                                            </a>
                                        </div>
                                        
                                    </div>
                                `);
                            }
                        });
                        
                        Toastify({
                            text: "Item removed from wishlist",
                            duration: 2000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#e74c3c"
                        }).showToast();

                        $(".cartCount").text(res.cartCounter);
                        $(".wishlistCount").text(res.wishlistCount);
                    }
                },
                error: function() {
                    Toastify({
                        text: "Please try again.",
                        duration: 2000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                }
            });
        });

        $('.move-to-cart').on('click', function(e) {
            e.preventDefault();

            var itemId = $(this).data('id');

            $.ajax({
                url: '{{ route('wishlist.to.cart') }}',
                type: 'POST',
                data: {
                    item_id: itemId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Toastify({
                            text: "Item moved to cart from wishlist.",
                            duration: 2000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#e74c3c"
                        }).showToast();
                        
                        $('#wishlist-item-' + itemId).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if wishlist is empty
                            if ($('.single-cart-area-list.main').length === 0) {
                                $('.rts-cart-list-area.wishlist').html(`
                                    <div class="empty-wishlist-state text-center py-5">
                                        <div class="empty-wishlist-icon mb-4">
                                            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="60" cy="60" r="60" fill="#f8f9fa"/>
                                                <path d="M60 35c-8.5 0-15 6.5-15 15 0 15 15 25 15 25s15-10 15-25c0-8.5-6.5-15-15-15z" fill="#dee2e6"/>
                                                <circle cx="60" cy="47" r="6" fill="#adb5bd"/>
                                                <path d="M45 75h30v5H45zm5-5h20v3H50z" fill="#e9ecef"/>
                                            </svg>
                                        </div>
                                        <h3 class="empty-wishlist-title mb-3" style="color: #6c757d; font-weight: 600;">Your Wishlist is Empty</h3>
                                        <p class="empty-wishlist-message mb-4" style="color: #868e96; font-size: 16px;">
                                            You haven't saved any items to your wishlist yet.<br>
                                            Browse our products and save your favorites for later!
                                        </p>
                                        <div class="empty-wishlist-actions">
                                            <a href="{{ route('shop') }}" class="rts-btn btn-primary me-3" style="padding: 12px 30px; font-weight: 500;">
                                                <i class="fa-regular fa-shopping-bag me-2"></i>
                                                Browse Products
                                            </a>
                                            <a href="{{ route('shop', ['type' => 'categories']) }}" class="rts-btn btn-outline-primary" style="padding: 12px 30px; font-weight: 500;">
                                                <i class="fa-regular fa-list me-2"></i>
                                                View Categories
                                            </a>
                                        </div>
                                    </div>
                                `);
                            }
                        });
                    } else {
                        Toastify({
                            text: "Failed to move item, Please try again.",
                            duration: 2000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                },
                error: function(xhr) {
                    Toastify({
                        text: "Something went wrong. Please try again.",
                        duration: 2000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                }
            });
        });
    });
</script>
