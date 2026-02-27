@extends('layouts.app')

@section('content')
    <div class="rts-navigation-area-breadcrumb">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navigator-breadcrumb-wrapper">
                        <a href="index.html">Home</a>
                        <i class="fa-regular fa-chevron-right"></i>
                        <a class="#" href="index.html">Shop</a>
                        <i class="fa-regular fa-chevron-right"></i>
                        <a class="current" href="index.html">Checkout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-seperator">
        <div class="container">
            <hr class="section-seperator">
        </div>
    </div>

    <div class="checkout-area rts-section-gap">
        <div class="container">
            <div class="row">
                <div
                    class="col-lg-8 pr--40 pr_md--5 pr_sm--5 order-1 order-xl-1 order-lg-2 order-md-1 order-sm-1 mt_md--30 mt_sm--30">

                    <div class="rts-billing-details-area">
                        <h3 class="title">Billing Details</h3>
                        @guest
                            <div class="alert alert-info mb-3">
                                <small><i class="fa fa-info-circle"></i> You can checkout as a guest. No account required!</small>
                            </div>
                        @endguest
                        @auth
                            @if (!empty($billingAddress) && count($billingAddress) > 0)
                                <div class="d-flex justify-content-between mb-3">
                                    <p class="m-2">Existing Address:</p>
                                    <button type="button" class="btn btn-outline-primary w-auto" id="showNewAddressForm">
                                        + Add New Billing Address
                                    </button>
                                </div>
                                <div class="row">
                                    @foreach ($billingAddress as $billAdd)
                                        <label class="card card-body mb-4 billAddress" style="border-radius: 15px;">
                                            <input type="radio" name="billAdd" class="d-none" value="{{ $billAdd->id }}" />
                                            <div class="p-3 address-content">
                                                {{ $billAdd->billing_address }},
                                                {{ $billAdd->billing_city }},
                                                {{ $billAdd->billing_state }},
                                                {{ $billAdd->billing_postal_code }},
                                                {{ $billAdd->billing_country }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Button to show new address form -->
                            @endif
                        @endauth
                        <form action="#">
                            <div id="newAddressForm" style="display: {{ (Auth::check() && count($billingAddress) > 0) ? 'none' : 'block' }};">
                                @guest
                                <div class="half-input-wrapper">
                                    <div class="single-input">
                                        <label for="firstName">First Name*</label>
                                        <input id="billingFirstName" type="text" name="billingFirstName" required>
                                    </div>
                                    <div class="single-input">
                                        <label for="lastName">Last Name*</label>
                                        <input id="billingLastName" type="text" name="billingLastName" required>
                                    </div>
                                </div>
                                @endguest
                                <div class="single-input">
                                    <label for="email">Email Address*</label>
                                    <input id="billingEmail" type="email" name="billingEmail" 
                                           value="{{ Auth::check() ? Auth::user()->email : '' }}" required>
                                </div>
                                <div class="single-input">
                                    <label for="phone">Phone*</label>
                                    <input id="billingPhone" type="text" name="billingPhone" required>
                                </div>
                                <div class="single-input">
                                    <label for="street">Street Address*</label>
                                    <input id="billingStreet" type="text" required name="billingStreet" required>
                                </div>
                                <div class="half-input-wrapper">
                                    <div class="single-input">
                                        <label for="city">Town / City*</label>
                                        <input id="billingCity" type="text" name="billingCity" required>
                                    </div>
                                    <div class="single-input">
                                        <label for="state">State*</label>
                                        <input id="billingState" type="text" name="billingState" required>
                                    </div>
                                    <div class="single-input">
                                        <label for="zip">Zip Code*</label>
                                        <input id="billingZip" type="text" required class="billingZip" required>
                                    </div>
                                </div>
                            </div>
                            <h3>Shipping address</h3>
                            <div class="billing-address">
                                <label class="option-card">
                                    <input type="radio" id="sameYes" name="sameAsShipping" value="yes" checked />
                                    <div class="custom-radio"></div>
                                    <span>Same as billing address</span>
                                </label>

                                <label class="option-card">
                                    <input type="radio" name="sameAsShipping" id="sameNo" value="no" />
                                    <div class="custom-radio"></div>
                                    <span>Use a different Shipping address</span>
                                </label>
                            </div><br>


                            <div id="shippingFields" class="rts-billing-details-area" style="display: none;">
                                <h3 class="title">Shipping details</h3>
                                <div class="single-input">
                                    <label for="phone">Phone*</label>
                                    <input id="shippingPhone" type="text" name="shippingPhone">
                                </div>
                                <div class="single-input">
                                    <label for="street">Street Address*</label>
                                    <input id="shippingStreet" type="text" required name="shippingStreet">
                                </div>
                                <div class="half-input-wrapper">
                                    <div class="single-input">
                                        <label for="city">Town / City*</label>
                                        <input id="shippingCity" type="text" name="shippingCity">
                                    </div>
                                    <div class="single-input">
                                        <label for="state">State*</label>
                                        <input id="shippingState" type="text" name="shippingState">
                                    </div>
                                    <div class="single-input">
                                        <label for="zip">Zip Code*</label>
                                        <input id="shippingZip" type="text" required name="shippingZip">
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method Section -->
                            <div class="rts-payment-method-area mt-4">
                                <h3 class="title">Payment Method</h3>
                                <div class="payment-options">
                                    <label class="option-card">
                                        <input type="radio" name="paymentMethod" value="online" id="paymentOnline" checked />
                                        <div class="custom-radio"></div>
                                        <span>Online Payment (Razorpay)</span>
                                        <small class="payment-desc d-block mt-1">Pay securely using Credit/Debit Cards, Net Banking, UPI, or Wallets</small>
                                    </label>

                                    <label class="option-card">
                                        <input type="radio" name="paymentMethod" value="cod" id="paymentCOD" />
                                        <div class="custom-radio"></div>
                                        <span>Cash on Delivery (COD)</span>
                                        <small class="payment-desc d-block mt-1">Pay when your order is delivered to your doorstep</small>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4 order-2 order-xl-2 order-lg-1 order-md-2 order-sm-2">
                    <h3 class="title-checkout">Your Order</h3>
                    <div class="right-card-sidebar-checkout">
                        <div class="top-wrapper">
                            <div class="product">
                                Products
                            </div>
                            <div class="price">
                                Price
                            </div>
                        </div>
                        @php
                            $totalPrice = 0;
                            $allProduct = [];
                        @endphp
                        @foreach ($cartData as $item)
                            @php
                                $unitPrice = $item->price;
                                $lineTotal = $unitPrice * $item->quantity;
                                $totalPrice += $lineTotal;
                                $shipTotal = $totalPrice + 0;
                                $allProduct[] = $item->id;
                            @endphp
                            <div class="single-shop-list">
                                <div class="left-area">
                                    <a href="#" class="thumbnail">
                                        @if ($item->product->has_variants == 1)
                                            @php
                                                $variantImage = $item->getVariantImage();
                                            @endphp

                                            @if ($variantImage)
                                                <img src="{{ asset('storage/' . $variantImage->image_path) }}"
                                                    alt="Variant Image">
                                            @else
                                                <img src="{{ asset('storage/' . $item->product->thumbnail_image) }}"
                                                    alt="Default Product Image">
                                            @endif
                                        @else
                                            <img src="{{ asset('storage/' . $item->product->thumbnail_image) }}"
                                                alt="shop">
                                        @endif
                                    </a>
                                    <a href="#" class="title">
                                        {{ isset($item->product) ? $item->product->name : '' }} </br>
                                        Quantity: {{ $item->quantity }}
                                    </a>
                                </div>
                                <span class="price">₹{{couponsApply($item->id, $lineTotal)}}</span>
                            </div>
                        @endforeach

                        <div class="single-shop-list">
                            <div class="left-area">
                                <span>Subtotal</span>
                            </div>
                            <span class="price">₹{{ number_format($totalPrice, 2) }}</span>
                        </div>
                        <div class="mx-5">
                            <div class="d-flex justify-content-between mt-2">
                                <h5 style="font-weight: 600; color: #2C3C28;">Item price :</h5>
                                <span class="price">₹{{ no_tax_price($totalPrice) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <h5 style="font-weight: 600; color: #2C3C28;">GST include 18%:</h5>
                                <span class="price">₹{{ number_format($totalPrice * 0.18, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <h5 style="font-weight: 600; color: #2C3C28;">Shipping charges:</h5><br>
                                <div>
                                    <span class="price" style="text-decoration: line-through;">Flat rate: ₹100.00</span>
                                    <span class="price" style="color: #28a745; margin-left: 10px;">Free</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h3 style="font-weight: 600; color: #2C3C28;">Total Price:</h3><br>
                                <h3 class="price" id="totalAmount" style="color: #009ec9;">₹{{ number_format($shipTotal, 2) }}</h3>
                            </div>
                        </div>
                        <input type="hidden" name   ="total_pay_amount" id="total_pay_amount"
                            value="{{ $shipTotal }}">
                        <div class="single-shop-list d-none" id="couponDiscount">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="left-area">
                                    <span style="font-weight: 600; color: #2C3C28;">Discount Applied:</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="price" id="dicountAmount" style="color: #009ec9;"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-applied-code" title="Remove Code">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="bottom-cupon-code-cart-area d-block" >
                            <div class="d-flex w-100" style="border: 1px solid #009ec9; border-radius: 6px;">
                                <input type="text" placeholder="Coupon/Reference Code" id="coupon" class="coupon" >
                                <button type="button" class="rts-btn btn-primary apply-coupon p-3 w-50">Apply</button>
                            </div>
                            <small class="text-muted mt-1">
                                <i class="fa fa-info-circle"></i> Enter either a coupon code or reference code for discounts
                            </small>
                        </div>
                        <div class="cottom-cart-right-area">
                            <a href="javascript:void(0)" id="pay-button" class="rts-btn btn-primary">Place Order</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
/* Payment Method Styling */
.rts-payment-method-area {
    margin-top: 20px;
}

.rts-payment-method-area .title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #2C3C28;
}

.payment-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.payment-options .option-card {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.payment-options .option-card:hover {
    border-color: #009ec9;
    background-color: #f8f9fa;
}

.payment-options .option-card input[type="radio"] {
    display: none;
}

.payment-options .option-card input[type="radio"]:checked + .custom-radio + span {
    color: #009ec9;
    font-weight: 600;
}

.payment-options .option-card input[type="radio"]:checked ~ .custom-radio {
    background-color: #009ec9;
    border-color: #009ec9;
}

.payment-options .option-card input[type="radio"]:checked ~ .custom-radio::after {
    opacity: 1;
}

.payment-options .option-card input[type="radio"]:checked ~ * {
    border-color: #009ec9;
}

.payment-options .option-card.selected,
.payment-options .option-card input[type="radio"]:checked + .custom-radio + span {
    border-color: #009ec9;
}

.custom-radio {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 50%;
    margin-right: 10px;
    position: relative;
    background-color: white;
    flex-shrink: 0;
    margin-top: 2px;
}

.custom-radio::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: white;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.payment-desc {
    color: #666;
    font-size: 13px;
    margin-left: 30px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .payment-options .option-card {
        padding: 12px;
    }
    
    .payment-desc {
        margin-left: 25px;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Debug information
        console.log('Checkout page loaded');
        console.log('Total price:', {{ $totalPrice ?? 0 }});
        console.log('All products:', @json($allProduct ?? []));

        $('#pay-button').on('click', function(e) {
            e.preventDefault();

            const allProductData = @json($allProduct ?? []);
            const payAmountTotal = $("#total_pay_amount").val();
            const paymentAmount = payAmountTotal * 100;
            const sameAsShipping = document.querySelector('input[name="sameAsShipping"]:checked').value;
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;

            const selectedAddressId = $('input[name="billAdd"]:checked').val();
            const useExisting = selectedAddressId !== undefined;

            let billingData = {};
            let shippingData = {};

            // Prepare price breakdown data
            const priceBreakdown = {
                item_price: {{ str_replace(',', '', no_tax_price($totalPrice) ?? '0') }},
                gst_amount: {{ round($totalPrice * 0.18, 2) ?? 0 }},
                shipping_charge: 0,
                total_before_discount: {{ $totalPrice ?? 0 }},
                discount_amount: 0,
                final_total: {{ $shipTotal ?? 0 }}
            };

            if (useExisting) {
                // Just send the existing address ID, but still need email
                billingData = {
                    existing_billing_id: selectedAddressId,
                    email: "{{ Auth::check() ? Auth::user()->email : '' }}",
                    @auth
                    first_name: "{{ Auth::user()->name ? explode(' ', Auth::user()->name)[0] : 'N/A' }}",
                    last_name: "{{ Auth::user()->name ? (count(explode(' ', Auth::user()->name)) > 1 ? implode(' ', array_slice(explode(' ', Auth::user()->name), 1)) : '') : 'N/A' }}",
                    @endauth
                    price_breakdown: priceBreakdown
                };
            } else {
                // Get data from form fields
                billingData = {
                    @guest
                    first_name: $('#billingFirstName').val().trim(),
                    last_name: $('#billingLastName').val().trim(),
                    @else
                    first_name: "{{ Auth::user()->name ? explode(' ', Auth::user()->name)[0] : 'N/A' }}",
                    last_name: "{{ Auth::user()->name ? (count(explode(' ', Auth::user()->name)) > 1 ? implode(' ', array_slice(explode(' ', Auth::user()->name), 1)) : '') : 'N/A' }}",
                    @endguest
                    email: $('#billingEmail').val().trim(),
                    phone: $('#billingPhone').val().trim(),
                    street: $('#billingStreet').val().trim(),
                    city: $('#billingCity').val().trim(),
                    state: $('#billingState').val().trim(),
                    zip: $('#billingZip').val().trim(),
                    price_breakdown: priceBreakdown
                };

                // Validate manual input
                @guest
                if (!billingData.first_name || !billingData.last_name || !billingData.email || !billingData.phone || !billingData.street || !billingData.city || !billingData.state || !billingData.zip) {
                    alert("Please fill all required billing fields including first name, last name, and email address.");
                    return;
                }
                @else
                if (!billingData.email || !billingData.phone || !billingData.street || !billingData.city || !billingData.state || !billingData.zip) {
                    alert("Please fill all required billing fields including email address.");
                    return;
                }
                @endguest
            }

            // Shipping data
            if (sameAsShipping === "no") {
                shippingData = {
                    phone: $('#shippingPhone').val().trim(),
                    street: $('#shippingStreet').val().trim(),
                    city: $('#shippingCity').val().trim(),
                    state: $('#shippingState').val().trim(),
                    zip: $('#shippingZip').val().trim()
                };
            } else {
                shippingData = {
                    ...billingData
                };
            }

            // Check if coupon was already applied globally
            let appliedDiscount = window.appliedDiscount || 0;
            let appliedCoupon = window.appliedCoupon || null;

            // Use updated price breakdown if coupon was applied, otherwise use default
            const finalPriceBreakdown = window.priceBreakdown || priceBreakdown;

            // Handle COD orders differently
            if (paymentMethod === 'cod') {
                // Process COD order directly
                Swal.fire({
                    title: 'Processing Order...',
                    text: 'Please wait while we confirm your COD order.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const orderData = {
                    payment_method: 'cod',
                    billing: {
                        ...billingData,
                        price_breakdown: finalPriceBreakdown
                    },
                    shipping: shippingData,
                    products: allProductData,
                    amount: (payAmountTotal),
                    discount: appliedDiscount,
                    coupon: appliedCoupon,
                    @guest
                    is_guest: true
                    @else
                    is_guest: false,
                    user_email: "{{ Auth::user()->email ?? 'N/A' }}"
                    @endguest
                };

                console.log('COD Order Data being sent:', orderData);
                console.log('COD Coupon debug:', {
                    appliedCoupon: appliedCoupon,
                    appliedDiscount: appliedDiscount,
                    couponInOrderData: orderData.coupon
                });

                // Send COD order to server
                $.ajax({
                    url: "{{ route('cod.payment') }}", // You'll need to create this route
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    data: orderData,
                    success: function(data) {
                        if (data.success) {
                            // Redirect directly to thank you page without popup
                            window.location.href = data.redirect_url || "{{ route('thank.you') }}";
                        } else {
                            Swal.fire({
                                title: 'Order Failed',
                                text: 'Something went wrong. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('COD Order Error:', xhr.responseText);
                        Swal.fire({
                            title: 'Order Failed',
                            text: 'Server error: ' + (xhr.responseJSON?.message || 'Please try again.'),
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
                return;
            }

            // Original Razorpay payment processing for online payments
            let options = {
                "key": "{{ config('services.razorpay.key') }}",
                "amount": paymentAmount,
                "currency": "INR",
                "name": "Buy Protein",
                "description": "Order Payment",
                "image": "https://yourdomain.com/logo.png",
                "handler": function(response) {
                    console.log('Razorpay Response:', response);
                    Swal.fire({
                        title: 'Processing Payment...',
                        text: 'Please wait while we complete your order.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    const paymentData = {
                        razorpay_payment_id: response.razorpay_payment_id,
                        payment_method: 'online',
                        billing: {
                            ...billingData,
                            price_breakdown: finalPriceBreakdown
                        },
                        shipping: shippingData,
                        products: allProductData,
                        amount: (paymentAmount / 100),
                        discount: appliedDiscount,
                        coupon: appliedCoupon,
                        @guest
                        is_guest: true
                        @else
                        is_guest: false,
                        user_email: "{{ Auth::user()->email ?? 'N/A' }}"
                        @endguest
                    };
                    
                    console.log('Payment Data being sent:', paymentData);
                    console.log('Coupon debug before payment:', {
                        appliedCoupon: appliedCoupon,
                        appliedDiscount: appliedDiscount,
                        couponInPaymentData: paymentData.coupon
                    });
                    console.log('User authentication status:', {
                        @auth
                        is_authenticated: true,
                        user_id: {{ Auth::user()->id }},
                        user_email: "{{ Auth::user()->email ?? 'N/A' }}",
                        user_name: "{{ Auth::user()->name ?? 'N/A' }}"
                        @endauth
                        @guest
                        is_authenticated: false
                        @endguest
                    });
                    
                    // First test with test route
                    $.ajax({
                        url: "{{ route('test.payment') }}",
                        type: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        data: paymentData,
                        success: function(testData) {
                            console.log('Test route success:', testData);
                            
                            // Now try the actual payment route
                            $.ajax({
                                url: "{{ route('razorpay.payment') }}",
                                type: "POST",
                                headers: {
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                data: paymentData,
                                success: function(data) {
                                    if (data.success) {
                                        // Redirect directly to thank you page without popup
                                        window.location.href = data.redirect_url || "{{ route('thank.you') }}";
                                    } else {
                                        Swal.fire({
                                            title: 'Payment Failed',
                                            text: 'Something went wrong. Please try again.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.log('Payment AJAX Error:', xhr.responseText);
                                    console.log('Status:', status);
                                    console.log('Error:', error);
                                    
                                    Swal.fire({
                                        title: 'Payment Failed',
                                        text: 'Server error: ' + (xhr.responseJSON?.message || 'Please try again.'),
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.log('Test route AJAX Error:', xhr.responseText);
                            console.log('Status:', status);
                            console.log('Error:', error);
                            
                            Swal.fire({
                                title: 'Connection Test Failed',
                                text: 'Unable to connect to server: ' + (xhr.responseJSON?.message || 'Please try again.'),
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });

                },
                "prefill": {
                    @auth
                        "name": "{{ Auth::user()->name }}",
                        "email": "{{ Auth::user()->email }}"
                    @endauth
                    @guest
                        "name": (billingData.first_name || '') + ' ' + (billingData.last_name || ''),
                        "email": billingData.email || ''
                    @endguest
                },
                "theme": {
                    "color": "#528FF0"
                }
            };

            let rzp = new Razorpay(options);
            rzp.open();
        });

        function toggleShippingFields() {
            if ($('#sameNo').is(':checked')) {
                $('#shippingFields').show();
            } else {
                $('#shippingFields').hide();
            }
        }

        // Event listeners
        $('input[name="sameAsShipping"]').on('change', toggleShippingFields);

        // Initial state
        toggleShippingFields();
    });
</script>

<script>
    $(document).ready(function() {
        // Auto-fill reference code from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');
        if (refCode) {
            $("#coupon").val(refCode.toUpperCase());
            
            // Show notification about pre-filled reference code
            Swal.fire({
                title: 'Reference Code Detected',
                text: `Reference code "${refCode.toUpperCase()}" has been pre-filled. Click Apply to use it.`,
                icon: 'info',
                confirmButtonText: 'OK',
                timer: 5000,
                timerProgressBar: true
            });
        }

        // Add input validation and formatting
        $("#coupon").on('input', function() {
            let value = $(this).val().toUpperCase();
            // Remove any invalid characters and limit length
            value = value.replace(/[^A-Z0-9\-]/g, '').substring(0, 20);
            $(this).val(value);
        });

        // Add validation feedback
        function validateCodeFormat(code) {
            // Basic format validation
            if (code.length < 3) {
                return {
                    valid: false,
                    message: 'Code must be at least 3 characters long.'
                };
            }
            
            if (code.length > 20) {
                return {
                    valid: false,
                    message: 'Code cannot exceed 20 characters.'
                };
            }
            
            if (!/^[A-Z0-9\-]+$/i.test(code)) {
                return {
                    valid: false,
                    message: 'Code can only contain letters, numbers, and hyphens.'
                };
            }
            
            return { valid: true };
        }

        $(".apply-coupon").on('click', function() {
            console.log('Apply coupon button clicked');
            
            const paymentAmount = {{ $shipTotal }};
            const code = $("#coupon").val();
            
            console.log('Payment amount:', paymentAmount);
            console.log('Code entered:', code);

            // Check if code is entered
            if (!code || code.trim() === '') {
                console.log('No code entered, showing warning');
                Swal.fire({
                    title: 'Code Required',
                    text: 'Please enter a coupon or reference code.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Clean and validate code format
            const cleanCode = code.trim().toUpperCase();
            console.log('Clean code:', cleanCode);

            // Validate code format
            const formatValidation = validateCodeFormat(cleanCode);
            if (!formatValidation.valid) {
                Swal.fire({
                    title: 'Invalid Code Format',
                    text: formatValidation.message,
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Check if already applied to prevent reapplication
            if (window.appliedCoupon === cleanCode) {
                console.log('Code already applied:', cleanCode);
                Swal.fire({
                    title: 'Already Applied',
                    text: 'This code has already been applied to your order.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Determine if it's a reference code or coupon code
            // Reference codes typically have different patterns
            const isReferenceCode = isReference(cleanCode);
            console.log('Is reference code:', isReferenceCode);
            
            const apiUrl = isReferenceCode ? "{{ route('apply.reference') }}" : "{{ route('apply.coupon') }}";
            const dataPayload = isReferenceCode ? 
                { reference_code: cleanCode, total_amount: paymentAmount } : 
                { coupon: cleanCode, paymentAmount: paymentAmount };
                
            console.log('API URL:', apiUrl);
            console.log('Data payload:', dataPayload);

            // Show loading state
            const originalText = $(this).text();
            $(this).text('Applying...').prop('disabled', true);

            $.ajax({
                url: apiUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                data: dataPayload,
                success: function(data) {
                    console.log('AJAX Success response:', data);
                    
                    if (data.success) {
                        console.log('Code applied successfully');
                        
                        // Store globally so it persists across functions
                        window.appliedDiscount = data.total_discount;
                        window.appliedCoupon = cleanCode;
                        window.appliedCodeType = isReferenceCode ? 'reference' : 'coupon';
                        
                        console.log('Updated global variables:', {
                            appliedDiscount: window.appliedDiscount,
                            appliedCoupon: window.appliedCoupon,
                            appliedCodeType: window.appliedCodeType
                        });
                        
                        // Show discount section
                        $("#couponDiscount").removeClass('d-none');
                        $("#dicountAmount").text("₹" + data.total_discount);
                        $("#totalAmount").text("₹" + (paymentAmount - data.total_discount));
                        $("#total_pay_amount").val(paymentAmount - data.total_discount);
                        
                        // Update the global price breakdown when code is applied
                        window.priceBreakdown = {
                            item_price: {{ str_replace(',', '', no_tax_price($totalPrice) ?? '0') }},
                            gst_amount: {{ round($totalPrice * 0.18, 2) ?? 0 }},
                            shipping_charge: 0,
                            total_before_discount: {{ $totalPrice ?? 0 }},
                            discount_amount: data.total_discount,
                            final_total: (paymentAmount - data.total_discount)
                        };

                        // Update button text and disable input
                        $('.apply-coupon').text('Applied').addClass('btn-success').removeClass('btn-primary');
                        $("#coupon").prop('disabled', true);

                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: data.message || `${isReferenceCode ? 'Reference' : 'Coupon'} applied successfully!`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        
                        // Debug log
                        console.log(`${isReferenceCode ? 'Reference' : 'Coupon'} applied successfully:`, {
                            appliedCoupon: window.appliedCoupon,
                            appliedDiscount: window.appliedDiscount,
                            codeType: window.appliedCodeType
                        });
                    } else {
                        // Clear global variables on error
                        window.appliedDiscount = 0;
                        window.appliedCoupon = null;
                        window.appliedCodeType = null;
                        
                        // Show specific error message from backend
                        Swal.fire({
                            title: `${isReferenceCode ? 'Reference' : 'Coupon'} Error`,
                            text: data.message || `Please enter a valid ${isReferenceCode ? 'reference' : 'coupon'} code.`,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Clear global variables on error
                    window.appliedDiscount = 0;
                    window.appliedCoupon = null;
                    window.appliedCodeType = null;
                    
                    console.log('Code validation error:', xhr.responseText);
                    
                    let errorMessage = 'Server error. Please try again.';
                    
                    // Try to parse JSON error response
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 422) {
                        errorMessage = 'Invalid code data provided.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please try again later.';
                    }
                    
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    // Reset button state
                    $('.apply-coupon').prop('disabled', false);
                    if (!window.appliedCoupon) {
                        $('.apply-coupon').text(originalText);
                    }
                }
            });
        });

        // Function to determine if code is a reference code
        function isReference(code) {
            // Reference codes typically start with "COUP-" or "REF-" or have specific patterns
            // You can customize this logic based on your reference code pattern
            return code.startsWith('COUP-') || 
                   code.startsWith('REF-') || 
                   code.startsWith('GYM') || 
                   code.startsWith('SHOP') ||
                   code.startsWith('TRAINER') ||
                   code.startsWith('INFLUENCER') ||
                   code.startsWith('DIETITIAN') ||
                   /^[A-Z]{3,5}-[A-Z0-9]{6,10}$/i.test(code) ||
                   /^(GYM|SHOP|TRAINER|INFLUENCER|DIETITIAN)[A-Z]{0,3}\d+$/i.test(code);
        }

        // Add remove code functionality
        $(document).on('click', '.remove-applied-code', function() {
            Swal.fire({
                title: 'Remove Code?',
                text: 'Are you sure you want to remove the applied code?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Remove',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    removeAppliedCode();
                }
            });
        });

        function removeAppliedCode() {
            const paymentAmount = {{ $shipTotal }};
            
            // Reset UI
            $("#couponDiscount").addClass('d-none');
            $("#totalAmount").text("₹" + paymentAmount);
            $("#total_pay_amount").val(paymentAmount);
            $("#coupon").val('').prop('disabled', false);
            $('.apply-coupon').text('Apply').removeClass('btn-success').addClass('btn-primary');
            
            // Clear global variables
            window.appliedDiscount = 0;
            window.appliedCoupon = null;
            window.appliedCodeType = null;
            
            // Reset price breakdown
            window.priceBreakdown = {
                item_price: {{ str_replace(',', '', no_tax_price($totalPrice) ?? '0') }},
                gst_amount: {{ round($totalPrice * 0.18, 2) ?? 0 }},
                shipping_charge: 0,
                total_before_discount: {{ $totalPrice ?? 0 }},
                discount_amount: 0,
                final_total: paymentAmount
            };
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const showNewAddressBtn = document.getElementById('showNewAddressForm');
        const newAddressForm = document.getElementById('newAddressForm');

        if (showNewAddressBtn) {
            showNewAddressBtn.addEventListener('click', function() {
                newAddressForm.style.display = 'block';
                this.style.display = 'none';
            });
        }
    });
</script>
