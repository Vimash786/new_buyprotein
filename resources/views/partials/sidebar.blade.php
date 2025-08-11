<!-- Mobile Sidebar -->
<!-- header style two -->
<div id="side-bar" class="side-bar header-two">
    <button class="close-icon-menu"><i class="far fa-times"></i></button>

    <form action="#" class="search-input-area-menu mt--30">
        <input type="text" placeholder="Search..." required>
        <button><i class="fa-light fa-magnifying-glass"></i></button>
    </form>

    <div class="mobile-menu-nav-area tab-nav-btn mt--20">

        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home"
                    type="button" role="tab" aria-controls="nav-home" aria-selected="true">Menu</button>
                <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Category</button>
            </div>
        </nav>

        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab"
                tabindex="0">
                <!-- mobile menu area start -->
                <div class="mobile-menu-main">
                    <nav class="nav-main mainmenu-nav mt--30">
                        <ul class="mainmenu metismenu" id="mobile-menu-active">
                            <li>
                                <a href="{{ route('home') }}" class="main">Home</a>
                            </li>
                            <li>
                                <a href="{{ route('shop') }}" class="main">Explore</a>
                            </li>
                            <li>
                                <a href="{{ route('user.blogs') }}" class="main">Categories</a>
                            </li>
                            <li>
                                <a href="{{ route('about.us') }}" class="main">About Us</a>
                            </li>
                            <li>
                                <a href="{{ route('contact') }}" class="main">Contact Us</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <!-- mobile menu area end -->
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab"
                tabindex="0">
                <div class="category-btn category-hover-header menu-category">
                    <ul class="category-sub-menu" id="category-active-menu">
                        @php
                            use App\Models\Category;
                            $catData = Category::where('is_active', 1)->limit(10)->get();
                        @endphp
                        @foreach ($catData as $cat)
                            <li>
                                <a href="{{ route('shop', ['type' => 'category', 'id' => Crypt::encrypt($cat->id)]) }}"
                                    class="menu-item">
                                    <span>{{ $cat->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <!-- button area wrapper start -->
    <div class="button-area-main-wrapper-menuy-sidebar mt--50">
        @if (!Auth::user())
            <div class="buton-area-bottom">
                <a href="{{ route('login') }}" class="rts-btn btn-primary">Sign In</a>
                <a href="{{ route('register') }}" class="rts-btn btn-primary">Sign Up</a>
            </div>
        @endif
    </div>
    <!-- button area wrapper end -->

</div>
<!-- header style two End -->
