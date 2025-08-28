@extends('layouts.app')

@section('content')
    <!-- rts contact main wrapper -->
    <div class="rts-contact-main-wrapper-banner bg_image">
        <div class="container">
            <div class="row">
                <div class="co-lg-12">
                    <div class="contact-banner-content">
                        <h1 class="title">
                            Ask Us Question
                        </h1>
                        <p class="disc">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pretium mollis ex, vel interdum
                            augue faucibus sit amet. Proin tempor purus ac suscipit...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rts contact main wrapper end -->

    <div class="rts-map-contact-area rts-section-gap2">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="contact-left-area-main-wrapper">
                        <h2 class="title">
                            You can ask us questions !
                        </h2>
                        <p class="disc">
                            Contact us for all your questions and opinions, or you can solve your problems in a shorter time
                            with our contact offices.
                        </p>
                        <div class="location-single-card">
                            <div class="icon">
                                <i class="fa-light fa-location-dot"></i>
                            </div>
                            <div class="information">
                                <h3 class="title">Surat</h3>
                                <p>F-2 AKASH GANGA COMPLEX,NR .BOB BANK, OPP.MCDONALD'S, ALTHAN CHOKDI VIP ROAD SURAT -395017</p>
                                <p>SHOP 1, SCHOOL, DIPANJALI SHOPPING CENTER, SUBHASH  CHANDRA BOSE MARG, OPP. L P SAVANI, ADAJAN GAM ,SURAT, GUJRAT 395009*</p>
                                <p>RUXMANI PARK, 23, DINDOLI - KHARVASA RD, NEAR MADHURIMA CIRCLE, NEW DINDOLI  , VRUKSHMANI SOCIETY, DINDOLI, SURAT, KARADVA, GUJARAT 394210</p>
                                <a href="#" class="number">+91 81285 30460</a>
                                <a href="#" class="email">proteinx.in@gmail.com</a>
                            </div>
                        </div>
                         <div class="location-single-card">
                            <div class="icon">
                                <i class="fa-light fa-location-dot"></i>
                            </div>
                            <div class="information">
                                <h3 class="title">Varanashi</h3>
                                <p>SHIVPUR BYPASS RD, GALAT BAZAR, SHIVPUR, VARANASI, UTTAR PRADESH 221002</p>
                                <a href="#" class="number">+91 81285 30460</a>
                                <a href="#" class="email">proteinx.in@gmail.com</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 pl--50 pl_sm--5 pl_md--5">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d14602.288851207937!2d71.5724!3d22.6708!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sin!4v1716725338558!5m2!1sen!2sin"
                        width="600" height="800" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- rts contact-form area start -->
    <div class="rts-contact-form-area rts-section-gapBottom">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="bg_light-1 contact-form-wrapper-bg">
                        <div class="row">
                            <div class="col-lg-7 pr--30 pr_md--10 pr_sm--5">
                                <div class="contact-form-wrapper-1">
                                    <h3 class="title mb--50">Fill Up The Form If You Have Any Question</h3>
                                    <form action="{{ route('contact.submit') }}" class="contact-form-1">
                                        <div class="contact-form-wrapper--half-area">
                                            <div class="single">
                                                <input type="text" placeholder="name*" name="name">
                                            </div>
                                            <div class="single">
                                                <input type="text" placeholder="Email*" name="email">
                                            </div>
                                        </div>
                                        <div class="single-select">
                                            <select name="subject">
                                                <option data-display="Subject*">All Categories</option>
                                                <option value="1">Some option</option>
                                                <option value="2">Another option</option>
                                                <option value="3" disabled>A disabled option</option>
                                                <option value="4">Potato</option>
                                            </select>
                                        </div>
                                        <textarea name="message" name="message" placeholder="Write Message Here"></textarea>
                                        <button class="rts-btn btn-primary mt--20">Send Message</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-lg-5 mt_md--30 mt_sm--30">
                                <div class="thumbnail-area">
                                    <img src="assets/images/contact/02.jpg" alt="contact_form">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- rts contact-form area end -->

@endsection
