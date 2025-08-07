<!-- rts footer one area start -->
<div class="rts-footer-area pt--80 bg_light-1">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="footer-main-content-wrapper pb--70 pb_sm--30">
                    <!-- single footer area wrapper -->
                    <div class="single-footer-wized">
                        <h3 class="footer-title">About Company</h3>
                        <div class="call-area">
                            <div class="icon">
                                <i class="fa-solid fa-phone-rotary"></i>
                            </div>
                            <div class="info">
                                <span>Have Question? Call Us</span>
                                <a href="#" class="number">+91 97240 86537</a>
                            </div>
                        </div>
                        <div class="opening-hour">
                            <div class="single">
                                <p>Monday - Friday: <span>10:00am - 6:00pm</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- single footer area wrapper -->
  
                    <!-- single footer area wrapper -->
                    <div class="single-footer-wized">
                        <h3 class="footer-title">Shop Categories</h3>
                        @php
                            $allCat = \App\Models\Category::where('is_active', 1)->limit(5)->get();
                        @endphp
                        <div class="footer-nav">
                            <ul>
                                @foreach ($allCat as $cat)
                                    <li><a
                                            href="{{ route('shop', ['type' => 'category', 'id' => Crypt::encrypt($cat->id)]) }}">{{ $cat->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <!-- single footer area wrapper -->

                    <!-- single footer area wrapper -->
                    <div class="single-footer-wized">
                        <h3 class="footer-title">Useful Links</h3>
                        <div class="footer-nav">
                            <ul>
                                <li><a href="{{ route('about.us') }}">About Us</a></li>
                                <li><a href="{{ route('contact') }}">Contact Us</a></li>
                                <li><a href="{{ route('term.condition') }}">Terms & Conditions</a></li>
                                <li><a href="{{ route('privacy.policy') }}">Privacy Policy</a></li>
                                <li><a href="{{ route('shipping.policy') }}">Shipping Policy</a></li>
                                <li><a href="{{ route('return.policy') }}">Return Policy</a></li>
                            </ul>
                        </div>
                    </div>
                    <!-- single footer area wrapper -->
                    <!-- single footer area wrapper -->
                    <div class="single-footer-wized">
                        <h3 class="footer-title">Our Newsletter</h3>
                        <p class="disc-news-letter">
                            Subscribe to the mailing list to receive updates one <br> the new arrivals and other
                            discounts
                        </p>

                        <p class="dsic">
                            I would like to receive news and special offer
                        </p>
                    </div>
                    <!-- single footer area wrapper -->
                </div>
                <div class="social-and-payment-area-wrapper">
                    <div class="social-one-wrapper">
                        <span>Follow Us:</span>
                        <ul>
                            <li><a href="https://www.facebook.com/share/16YivBxrm4/"><i
                                        class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="https://www.instagram.com/buyproteins?igsh=bGowMXp2cW5ybWM4"><i
                                        class="fa-brands fa-instagram"></i></a></li>
                            <li><a href="https://youtube.com/@buyproteins?si=AnzFjw8hA3XZvcdC"><i
                                        class="fa-brands fa-youtube"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- rts footer one area end -->

<!-- rts copyright-area start -->
<div class="rts-copyright-area">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="copyright-between-1">
                    <p class="disc">
                        Copyright 2025 <a href="#">©BuyProtein</a>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- rts copyright-area end -->
