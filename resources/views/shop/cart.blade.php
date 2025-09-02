@extends('layouts.app')

@section('content')
    <div class="rts-navigation-area-breadcrumb bg_light-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navigator-breadcrumb-wrapper">
                        <a href="index.html">Home</a>
                        <i class="fa-regular fa-chevron-right"></i>
                        <a class="current" href="index.html">Cart</a>
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
                <div class="col-xl-9 col-lg-12 col-md-12 col-12 order-1 order-xl-1 order-lg-2 order-md-1 order-sm-1">
                    <div class="rts-cart-list-area">
                    @if($cartData->isNotEmpty())
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
                        </div>
                        @php
                            $totalPrice = 0;
                        @endphp
                        @foreach ($cartData as $listData)
                            @php
                                $totalPrice += $listData->quantity * $listData->price;
                            @endphp
                            <div class="single-cart-area-list main  item-parent wishlist-item"
                                id="wishlist-item-{{ $listData->id }}">
                                <div class="product-main-cart">
                                    <div class="close section-activation remove-wishlist-item" data-id="{{ $listData->id }}"
                                        data-price="{{ $listData->price }}" data-quantity="{{ $listData->quantity }}">
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
                                        <input type="text" class="input quantity-input"
                                            value="{{ $listData->quantity }}" readonly>
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
                            </div>
                        @endforeach
                    @else
                        <!-- Empty Cart State -->
                        <div class="empty-cart-state text-center py-5">
                            <div class="empty-cart-icon mb-4">
                                <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="60" cy="60" r="60" fill="#f8f9fa"/>
                                    <path d="M45 35h30c2.761 0 5 2.239 5 5v5H40v-5c0-2.761 2.239-5 5-5z" fill="#dee2e6"/>
                                    <path d="M35 45h50c2.761 0 5 2.239 5 5v30c0 2.761-2.239 5-5 5H35c-2.761 0-5-2.239-5-5V50c0-2.761 2.239-5 5-5z" fill="#e9ecef"/>
                                    <path d="M50 55v20m20-20v20" stroke="#6c757d" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3 class="empty-cart-title mb-3" style="color: #6c757d; font-weight: 600;">Your Cart is Empty</h3>
                            <p class="empty-cart-message mb-4" style="color: #868e96; font-size: 16px;">
                                Looks like you haven't added any items to your cart yet.<br>
                                Start shopping to fill it up with your favorite products!
                            </p>
                            <div class="empty-cart-actions">
                                <a href="{{ route('shop') }}" class="rts-btn btn-primary" style="display:unset; padding: 12px 30px; font-weight: 500;">
                                    <i class="fa-regular fa-shopping-bag me-2"></i>
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    @endif
                    </div>
                </div>
                <div class="col-xl-3 col-lg-12 col-md-12 col-12 order-2 order-xl-2 order-lg-1 order-md-2 order-sm-2">
                    @if($cartData->isNotEmpty())
                        <div class="cart-total-area-start-right">
                            <h5 class="title">Cart Totals</h5>

                            <div class="bottom">
                                <div class="wrapper total justify-content-between">
                                    <h6 class="">Item Price</h6>
                                    <h6 class="totalPrice">₹{{ no_tax_price($totalPrice) }}</h6>
                                </div>
                                <div class="wrapper total justify-content-between">
                                    <h6 class="">GST (18%)</h6>
                                    <h6 class="totalPrice">₹{{ number_format($totalPrice * 0.18, 2) }}</h6>
                                </div>
                                 <div class="wrapper total justify-content-between">
                                    <h6 class="">Shipping Charge</h6>
                                    <div>
                                        <span class="price" style="text-decoration: line-through;">Flat rate: ₹100.00</span>
                                        <span class="price" style="color: #28a745; margin-left: 10px;">Free</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="wrapper total justify-content-between">
                                    
                                    <h3 class="">Subtotal</h3>
                                    <h3 class="totalPrice">₹{{ $totalPrice }}</h3>
                                </div>
                                <div class="wrapper shipping">
                                    <p>including 18% GST</p>
                                </div>
                                <div class="button-area">
                                    <a href="{{ route('user.checkout') }}" class="rts-btn btn-primary">Proceed To Checkout</a>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Empty Cart Sidebar -->
                        <div class="empty-cart-sidebar">
                            <div class="empty-cart-sidebar-content p-4 text-center" style="background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                                <div class="empty-sidebar-icon mb-3">
                                    <i class="fa-regular fa-shopping-cart" style="font-size: 48px; color: #dee2e6;"></i>
                                </div>
                                <h5 style="color: #6c757d; margin-bottom: 15px;">Cart Summary</h5>
                                <p style="color: #868e96; font-size: 14px; margin-bottom: 20px;">
                                    Your cart totals will appear here when you add items.
                                </p>
                                <div class="empty-cart-stats" style="font-size: 14px; color: #6c757d;">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Items:</span>
                                        <span>0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>₹0.00</span>
                                    </div>
                                    <hr style="margin: 15px 0;">
                                    <div class="d-flex justify-content-between" style="font-weight: 600;">
                                        <span>Total:</span>
                                        <span>₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
            const $button = $(this);

            // Determine if increment or decrement
            if ($(this).hasClass('increment')) {
                currentQty += 1;
            } else if (currentQty > 1) {
                currentQty -= 1;
            } else {
                return; // Don't allow quantity less than 1
            }

            // AJAX update
            $.ajax({
                url: '{{ route('wishlist.updateQuantity') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: itemId,
                    quantity: currentQty,
                    cart: 'cart'
                },
                beforeSend: function() {
                    $button.prop('disabled', true);
                },
                success: function(res) {
                    console.log('Update response:', res); // Debug log
                    if (res.success) {
                        $input.val(res.quantity);
                        $(`p[data-subtotal-id="${res.item_id}"]`).text(
                            `₹${res.subtotal}`);
                        $('.totalPrice').text(`₹${res.total}`);

                        Toastify({
                            text: "Quantity updated successfully.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#009ec9",
                        }).showToast();
                    } else {
                        Toastify({
                            text: res.message || "Failed to update quantity.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.responseText); // Debug log
                    Toastify({
                        text: "Failed to update quantity. Try again.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $('.remove-wishlist-item').click(function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $item = $btn.closest('.item-parent');
            const id = $(this).data('id');
            const price = parseFloat($btn.data('price'));
            const quantity = parseInt($btn.data('quantity'), 10);
            const itemTotal = price * quantity;

            console.log('Removing item with ID:', id); // Debug log
            console.log('Button data:', {
                id: id,
                price: price,
                quantity: quantity
            });

            // Show confirmation before removing
            if (!confirm('Are you sure you want to remove this item from cart?')) {
                return;
            }

            $.ajax({
                url: '{{ route('wishlist.remove') }}',
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    cart: 'cart'
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                    console.log('Sending AJAX request to remove item');
                },
                success: function(res) {
                    console.log('Remove response:', res); // Debug log
                    if (res.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if cart is empty
                            if ($('.single-cart-area-list.main').length === 0) {
                                // Replace the entire cart area with empty state
                                $('.rts-cart-list-area').html(`
                                    <div class="empty-cart-state text-center py-5">
                                        <div class="empty-cart-icon mb-4">
                                            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="60" cy="60" r="60" fill="#f8f9fa"/>
                                                <path d="M45 35h30c2.761 0 5 2.239 5 5v5H40v-5c0-2.761 2.239-5 5-5z" fill="#dee2e6"/>
                                                <path d="M35 45h50c2.761 0 5 2.239 5 5v30c0 2.761-2.239 5-5 5H35c-2.761 0-5-2.239-5-5V50c0-2.761 2.239-5 5-5z" fill="#e9ecef"/>
                                                <path d="M50 55v20m20-20v20" stroke="#6c757d" stroke-width="3" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                        <h3 class="empty-cart-title mb-3" style="color: #6c757d; font-weight: 600;">Your Cart is Empty</h3>
                                        <p class="empty-cart-message mb-4" style="color: #868e96; font-size: 16px;">
                                            Looks like you haven't added any items to your cart yet.<br>
                                            Start shopping to fill it up with your favorite products!
                                        </p>
                                        <div class="empty-cart-actions">
                                            <a href="{{ route('shop') }}" class="rts-btn btn-primary" style="padding: 12px 30px; font-weight: 500;">
                                                <i class="fa-regular fa-shopping-bag me-2"></i>
                                                Continue Shopping
                                            </a>
                                        </div>
                                    </div>
                                `);
                                
                                // Replace cart totals with empty state
                                $('.cart-total-area-start-right, .empty-cart-sidebar').replaceWith(`
                                    <div class="empty-cart-sidebar">
                                        <div class="empty-cart-sidebar-content p-4 text-center" style="background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                                            <div class="empty-sidebar-icon mb-3">
                                                <i class="fa-regular fa-shopping-cart" style="font-size: 48px; color: #dee2e6;"></i>
                                            </div>
                                            <h5 style="color: #6c757d; margin-bottom: 15px;">Cart Summary</h5>
                                            <p style="color: #868e96; font-size: 14px; margin-bottom: 20px;">
                                                Your cart totals will appear here when you add items.
                                            </p>
                                            <div class="empty-cart-stats" style="font-size: 14px; color: #6c757d;">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Items:</span>
                                                    <span>0</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Subtotal:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                                <hr style="margin: 15px 0;">
                                                <div class="d-flex justify-content-between" style="font-weight: 600;">
                                                    <span>Total:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            }
                        });

                        // Update total price
                        $(".totalPrice").text('₹' + (res.totalPrice || 0));

                        Toastify({
                            text: "Item removed from Cart.",
                            duration: 2000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#e74c3c"
                        }).showToast();

                        // Update counters if elements exist
                        if ($(".cartCount").length) {
                            $(".cartCount").text(res.cartCounter || 0);
                        }
                        if ($(".wishlistCount").length) {
                            $(".wishlistCount").text(res.wishlistCount || 0);
                        }
                    } else {
                        Toastify({
                            text: res.message || "Failed to remove item.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545",
                        }).showToast();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    Toastify({
                        text: "Please try again. Error: " + error,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545",
                    }).showToast();
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
