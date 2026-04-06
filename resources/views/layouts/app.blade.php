<!DOCTYPE html>
<html lang="en">


<!-- Mirrored from html.themewant.com/ekomart/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 30 Jun 2025 07:19:26 GMT -->

<head>
    <meta charset="UTF-8">
    <meta name="description"
        content="Ekomart-Grocery-Store(e-Commerce) HTML Template: A sleek, responsive, and user-friendly HTML template designed for online grocery stores.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Grocery, Store, stores">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Buy Protein Store (e-Commerce)')</title>
    <link rel="shortcut icon" type="image/x-icon/svg+xml" href="{{ asset('buy-protein-pro.png') }}">

    <!-- plugins css -->
    <link rel="stylesheet preload" href="{{ asset('assets/css/plugins.css') }}" as="style">
    <link rel="stylesheet preload" href="{{ asset('assets/css/style.css') }}" as="style">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        /* Product card: consistent height & aligned CTA */
        .single-shopping-card-one {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .single-shopping-card-one .image-and-action-area-wrapper {
            height: 260px; /* desktop default */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .single-shopping-card-one .thumbnail-preview {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .single-shopping-card-one .thumbnail-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .single-shopping-card-one .body-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .single-shopping-card-one .body-content .title {
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* clamp to 2 lines to equalize heights */
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 44px; /* fallback for equal height */
        }
        .single-shopping-card-one .cart-counter-action {
            margin-top: auto; /* push controls to bottom */
        }
        @media (max-width: 991.98px) {
            .single-shopping-card-one .image-and-action-area-wrapper { height: 220px; }
        }
        @media (max-width: 575.98px) {
            .single-shopping-card-one .image-and-action-area-wrapper { height: 180px; }
        }

        .brand-card {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            height: 150px;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .brand-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .brand-logo-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
        }
        .single-shopping-card-one .thumbnail-preview .badge {
            left: 5;
        }
        
    </style>
    @stack('styles')
</head>

<body class="shop-main-h">
    <!-- <div class="loader-wrapper" id="loaderWrapper">
        <div class="loader"></div>
    </div> -->

    @include('partials.header')

    @include('partials.sidebar')

    <!-- Main Content -->
    @yield('content')

    @include('partials.footer')

    <!-- modal -->
    <!-- <div id="myModal-1" class="modal fade" role="dialog">
        <div class="modal-dialog bg_image">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-bs-dismiss="modal"><i class="fa-light fa-x"></i></button>
                </div>
                <div class="modal-body text-center">
                    <div class="inner-content">
                        <div class="content">
                            <span class="pre-title">Get up to 30% off on your first $150 purchase</span>
                            <h1 class="title">Feed Your Family at the  <br>
                                Best Price</h1>
                            <p class="disc">
                                We have prepared special discounts for you on grocery products. Don't <br> miss these opportunities...
                            </p>
                            <div class="rts-btn-banner-area">
                                <a href="#" class="rts-btn btn-primary radious-sm with-icon">
                                    <div class="btn-text">
                                        Shop Now
                                    </div>
                                    <div class="arrow-icon">
                                        <i class="fa-light fa-arrow-right"></i>
                                    </div>
                                    <div class="arrow-icon">
                                        <i class="fa-light fa-arrow-right"></i>
                                    </div>
                                </a>
                                <div class="price-area">
                                    <span>
                                        from
                                    </span>
                                    <h3 class="title animated fadeIn">$80.99</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <div class="product-details-popup-wrapper">
        <div class="rts-product-details-section rts-product-details-section2 product-details-popup-section">
            <div class="product-details-popup">
                <button class="product-details-close-btn"><i class="fal fa-times"></i></button>
                <div class="details-product-area">
                    <div class="product-thumb-area">
                        <div class="cursor"></div>
                        <div class="thumb-wrapper one filterd-items figure">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url({{ asset('assets/images/products/product-details.jpg') }})">
                                <img src="{{ asset('assets/images/products/product-details.jpg') }}"
                                    alt="product-thumb">
                            </div>
                        </div>
                        <div class="thumb-wrapper two filterd-items hide">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url({{ asset('assets/images/products/product-filt2.jpg') }})">
                                <img src="{{ asset('assets/images/products/product-filt2.jpg') }}" alt="product-thumb">
                            </div>
                        </div>
                        <div class="thumb-wrapper three filterd-items hide">
                            <div class="product-thumb zoom" onmousemove="zoom(event)"
                                style="background-image: url({{ asset('assets/images/products/product-filt3.jpg') }})">
                                <img src="{{ asset('assets/images/products/product-filt3.jpg') }}" alt="product-thumb">
                            </div>
                        </div>
                        <div class="product-thumb-filter-group">
                            <div class="thumb-filter filter-btn active" data-show=".one"><img
                                    src="{{ asset('assets/images/products/product-filt1.jpg') }}"
                                    alt="product-thumb-filter"></div>
                            <div class="thumb-filter filter-btn" data-show=".two"><img
                                    src="{{ asset('assets/images/products/product-filt2.jpg') }}"
                                    alt="product-thumb-filter"></div>
                            <div class="thumb-filter filter-btn" data-show=".three"><img
                                    src="{{ asset('assets/images/products/product-filt3.jpg') }}"
                                    alt="product-thumb-filter"></div>
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
                                    <input type="text" class="input" value="01" />
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
                            <span class="catagorys product-unipue"><span>Categories: </span> T-Shirts, Tops,
                                Mens</span>
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



    <!-- Modal -->
    <div class="modal modal-compare-area-start fade" id="exampleModal" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Products Compare</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="compare-main-wrapper-body">
                        <div class="single-compare-elements name">Preview</div>
                        <div class="single-compare-elements">
                            <div class="thumbnail-preview">
                                <img src="{{ asset('assets/images/grocery/01.jpg') }}" alt="grocery">
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="thumbnail-preview">
                                <img src="{{ asset('assets/images/grocery/02.jpg') }}" alt="grocery">
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="thumbnail-preview">
                                <img src="{{ asset('assets/images/grocery/03.jpg') }}" alt="grocery">
                            </div>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname spacifiq">
                        <div class="single-compare-elements name">Name</div>
                        <div class="single-compare-elements">
                            <p>J.Crew Mercantile Women's Short</p>
                        </div>
                        <div class="single-compare-elements">
                            <p>Amazon Essentials Women's Tanks</p>
                        </div>
                        <div class="single-compare-elements">
                            <p>Amazon Brand - Daily Ritual Wom</p>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Price</div>
                        <div class="single-compare-elements price">
                            <p>$25.00</p>
                        </div>
                        <div class="single-compare-elements price">
                            <p>$39.25</p>
                        </div>
                        <div class="single-compare-elements price">
                            <p>$12.00</p>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Description</div>
                        <div class="single-compare-elements discription">
                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum
                                has been the industry's standard</p>
                        </div>
                        <div class="single-compare-elements discription">
                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum
                                has been the industry's standard</p>
                        </div>
                        <div class="single-compare-elements discription">
                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum
                                has been the industry's standard</p>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Rating</div>
                        <div class="single-compare-elements">
                            <div class="rating">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <span>(25)</span>
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="rating">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <span>(19)</span>
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="rating">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <span>(120)</span>
                            </div>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Weight</div>
                        <div class="single-compare-elements">
                            <div class="rating">
                                <p>320 gram</p>
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <p>370 gram</p>
                        </div>
                        <div class="single-compare-elements">
                            <p>380 gram</p>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Stock status</div>
                        <div class="single-compare-elements">
                            <div class="instocks">
                                <span>In Stock</span>
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="outstocks">
                                <span class="out-stock">Out Of Stock</span>
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="instocks">
                                <span>In Stock</span>
                            </div>
                        </div>
                    </div>
                    <div class="compare-main-wrapper-body productname">
                        <div class="single-compare-elements name">Buy Now</div>
                        <div class="single-compare-elements">
                            <div class="cart-counter-action">
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
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="cart-counter-action">
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
                            </div>
                        </div>
                        <div class="single-compare-elements">
                            <div class="cart-counter-action">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!--================= Preloader Section Start Here =================-->
    <!-- <div id="weiboo-load">
    <div class="preloader-new">
        <svg class="cart_preloader" role="img" aria-label="Shopping cart_preloader line animation"
            viewBox="0 0 128 128" width="128px" height="128px" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="8">
                <g class="cart__track" stroke="hsla(0,10%,10%,0.1)">
                    <polyline points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80" />
                    <circle cx="43" cy="111" r="13" />
                    <circle cx="102" cy="111" r="13" />
                </g>
                <g class="cart__lines" stroke="currentColor">
                    <polyline class="cart__top" points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80"
                        stroke-dasharray="338 338" stroke-dashoffset="-338" />
                    <g class="cart__wheel1" transform="rotate(-90,43,111)">
                        <circle class="cart__wheel-stroke" cx="43" cy="111" r="13" stroke-dasharray="81.68 81.68"
                            stroke-dashoffset="81.68" />
                    </g>
                    <g class="cart__wheel2" transform="rotate(90,102,111)">
                        <circle class="cart__wheel-stroke" cx="102" cy="111" r="13" stroke-dasharray="81.68 81.68"
                            stroke-dashoffset="81.68" />
                    </g>
                </g>
            </g>
        </svg>
    </div>
</div> -->
    <!--================= Preloader End Here =================-->





    <div class="search-input-area">
        <div class="container">
            <div class="search-input-inner">
                <form action="{{ route('shop') }}" method="GET" class="input-div">
                    <input id="searchInput1" class="search-input" type="text" name="search"
                        placeholder="Search for products, categories or brands" required>
                    <button type="submit"><i class="far fa-search"></i></button>
                </form>
            </div>
        </div>
        <div id="close" class="search-close-icon"><i class="far fa-times"></i></div>
    </div>
    <div id="anywhere-home" class="anywere"></div>
    <!-- progress area start -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"
                style="transition: stroke-dashoffset 10ms linear 0s; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 307.919;">
            </path>
        </svg>
    </div>
    <!-- progress area end -->


    <!-- plugins js -->
    <script defer src="{{ asset('assets/js/plugins.js') }}"></script>

    <!-- custom js -->
    <script defer src="{{ asset('assets/js/main.js') }}"></script>
    <!-- header style two End -->

    <script>
        $(window).on('load', function() {
            const $loader = $('#loaderWrapper');
            if ($loader.length) {
                $loader.addClass('hidden');
                setTimeout(function() {
                    $loader.css('display', 'none');
                }, 500);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    @if (session('success'))
        <script>
            Toastify({
                text: "{{ session('success') }}",
                duration: 3000,
                gravity: "top", // top or bottom
                position: "right", // left, center or right
                backgroundColor: "#28a745", // green
            }).showToast();
        </script>
    @endif

    @if (session('error'))
        <script>
            Toastify({
                text: "{{ session('error') }}",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545", // red
            }).showToast();
        </script>
    @endif


    @stack('scripts')

</body>


<!-- Mirrored from html.themewant.com/ekomart/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 30 Jun 2025 07:19:57 GMT -->

</html>
