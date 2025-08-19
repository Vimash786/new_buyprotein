@extends('layouts.app')

@section('content')
    <div class="account-tab-area-start rts-section-gap">
        <div class="container-2">
            <div class="row">
                <div class="col-lg-3">
                    <div class="nav accout-dashborard-nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist"
                        aria-orientation="vertical">
                        <button class="nav-link active" id="v-pills-home-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-home" type="button" role="tab" aria-controls="v-pills-home"
                            aria-selected="true"><i class="fa-regular fa-chart-line"></i>Dashboard</button>
                        <button class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile"
                            aria-selected="false"><i class="fa-regular fa-bag-shopping"></i>Order</button>
                        <button class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-messages" type="button" role="tab"
                            aria-controls="v-pills-messages" aria-selected="false"><i
                                class="fa-sharp fa-regular fa-dollar-sign"></i> My Earning</button>
                        @if(in_array(Auth::user()->role, ['Gym Owner/Trainer/Influencer/Dietitian', 'Shop Owner']))
                                <button class="nav-link" id="v-pills-reference-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-reference" type="button" role="tab"
                                aria-controls="v-pills-reference" aria-selected="false"><i
                                class="fa-sharp fa-regular fa-share"></i>My Shareable Coupons</button>
                        @endif
                        <button class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-settings" type="button" role="tab"
                            aria-controls="v-pills-settings" aria-selected="false"><i
                                class="fa-sharp fa-regular fa-location-dot"></i>My Address</button>
                        <button class="nav-link" id="v-pills-settingsa-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-settingsa" type="button" role="tab"
                            aria-controls="v-pills-settingsa" aria-selected="false"><i class="fa-light fa-user"></i>Account
                            Details</button>
                        @auth
                            <button class="nav-link" id="v-pills-settingsb-tab" data-bs-toggle="pill"
                                data-bs-target="#v-pills-settingsb" type="button" role="tab"
                                aria-controls="v-pills-settingsb" aria-selected="false">
                                <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fa-light fa-right-from-bracket"></i>Log Out
                                </a>
                            </button>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        @endauth
                    </div>
                </div>
                <div class="col-lg-9 pl--50 pl_md--10 pl_sm--10 pt_md--30 pt_sm--30">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-home" role="tabpanel"
                            aria-labelledby="v-pills-home-tab" tabindex="0">
                            <div class="dashboard-account-area">
                                <h2 class="title">Hello {{ Auth::user()->name }}!</h2>
                                <p class="disc">
                                    From your account dashboard you can view your recent orders, manage your shipping and
                                    billing addresses, and edit your password and account details.
                                </p>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-profile" role="tabpanel"
                            aria-labelledby="v-pills-profile-tab" tabindex="0">
                            <div class="order-table-account">
                                <div class="h2 title">Your Orders</div>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td>#{{ $order->id }}</td>
                                                    <td>{{ $order->created_at }}</td>
                                                    <td>{{ $order->status }}</td>
                                                    <td>₹{{ $order->total_order_amount }} for
                                                        {{ $order->orderSellerProducts->count() }} item</td>
                                                    <td><a href="#" class="btn-small d-block">View</a></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-messages" role="tabpanel"
                            aria-labelledby="v-pills-messages-tab" tabindex="0">
                            <div class="tracing-order-account">
                                <h2 class="title">My Earning</h2>
                                <p>
                                    Total Earning: ₹{{ $totalEarning ?? 0 }} <br>
                                </p>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Reference Code</th>
                                                <th>Earning ID</th>
                                                <th>User</th>
                                                <th>Total Order Amount</th>
                                                <th>Total Earning Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td>#{{ $order->id }}</td>
                                                    <td>{{ $order->created_at }}</td>
                                                    <td>{{ $order->status }}</td>
                                                    <td>₹{{ $order->total_order_amount }} for
                                                        {{ $order->orderSellerProducts->count() }} item</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @if(in_array(Auth::user()->role, ['Gym Owner/Trainer/Influencer/Dietitian', 'Shop Owner']))
                        <div class="tab-pane fade" id="v-pills-reference" role="tabpanel"
                            aria-labelledby="v-pills-reference-tab" tabindex="0">
                            <div class="reference-coupons-account">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h2 class="title mb-0">
                                        <i class="fas fa-tags text-primary me-2"></i>My Reference Coupons
                                    </h2>
                                    <div class="text-end">
                                        <small class="text-muted">Share these codes to earn commissions</small>
                                    </div>
                                </div>

                                <!-- Available Reference Coupons -->
                                <div class="available-references mb-4">
                                    @if($userReferences->count() > 0)
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-gradient text-white" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-share-alt me-2"></i>Shareable Reference Coupons
                                                </h5>
                                                <small class="opacity-75">Share these codes with customers to earn commissions</small>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="border-0">
                                                                    <i class="fas fa-code text-primary me-1"></i>Coupon Code
                                                                </th>
                                                                <th class="border-0">
                                                                    <i class="fas fa-tag text-info me-1"></i>Name
                                                                </th>
                                                                <th class="border-0">
                                                                    <i class="fas fa-coins text-warning me-1"></i>Your Earning
                                                                </th>
                                                                <th class="border-0">
                                                                    <i class="fas fa-percent text-success me-1"></i>Customer Discount
                                                                </th>
                                                                <th class="border-0">
                                                                    <i class="fas fa-calendar text-danger me-1"></i>Valid Until
                                                                </th>
                                                                <th class="border-0 text-center">
                                                                    <i class="fas fa-cogs text-secondary me-1"></i>Actions
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($userReferences as $reference)
                                                            <tr class="reference-row">
                                                                <td class="align-middle">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="coupon-code-badge">
                                                                            <span class="badge bg-primary fs-6 px-3 py-2 font-monospace">
                                                                                {{ $reference->code }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="align-middle">
                                                                    <strong class="text-dark">{{ $reference->name }}</strong>
                                                                </td>
                                                                <td class="align-middle">
                                                                    <span class="text-success fw-bold">
                                                                        @if($reference->type === 'percentage')
                                                                            {{ $reference->giver_discount }}%
                                                                        @else
                                                                            ₹{{ $reference->giver_discount }}
                                                                        @endif
                                                                    </span>
                                                                </td>
                                                                <td class="align-middle">
                                                                    <span class="text-info fw-bold">
                                                                        @if($reference->type === 'percentage')
                                                                            {{ $reference->applyer_discount }}%
                                                                        @else
                                                                            ₹{{ $reference->applyer_discount }}
                                                                        @endif
                                                                    </span>
                                                                </td>
                                                                <td class="align-middle">
                                                                    <span class="text-muted">{{ $reference->expires_at->format('M d, Y') }}</span>
                                                                </td>
                                                                <td class="align-middle text-center">
                                                                    <div class="btn-group" role="group">
                                                                        <button class="btn btn-outline-secondary btn-sm" 
                                                                                onclick="copyReferenceCodeToClipboard('{{ $reference->code }}')"
                                                                                title="Copy Code">
                                                                            <i class="fa fa-copy"></i>
                                                                        </button>
                                                                        <button class="btn btn-success btn-sm" 
                                                                                onclick="shareSpecificReference('{{ $reference->code }}', '{{ $reference->name }}')"
                                                                                title="Share Code">
                                                                            <i class="fa fa-share-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body text-center py-5">
                                                <div class="mb-4">
                                                    <i class="fas fa-tags text-muted" style="font-size: 4rem;"></i>
                                                </div>
                                                <h5 class="text-muted mb-3">No Reference Coupons Available</h5>
                                                <p class="text-muted">
                                                    You don't have any reference coupons assigned yet. 
                                                    Contact admin to get reference coupons that you can share with customers.
                                                </p>
                                                <button class="btn btn-primary btn-sm" onclick="location.reload()">
                                                    <i class="fa fa-refresh me-1"></i>Refresh
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Reference Usage Statistics -->
                                <div class="reference-stats">
                                    <h4>My Reference Statistics</h4>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="stat-box p-3 border rounded text-center">
                                                <h5 class="text-success">Total Earnings</h5>
                                                <h3>₹{{ number_format($totalEarning, 2) }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stat-box p-3 border rounded text-center">
                                                <h5 class="text-primary">Available Coupons</h5>
                                                <h3>{{ $userReferences->count() }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stat-box p-3 border rounded text-center">
                                                <h5 class="text-info">Total Uses</h5>
                                                <h3>{{ \App\Models\ReferenceUsage::where('giver_user_id', Auth::user()->id)->count() }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Reference Usage -->
                                <div class="recent-usage mt-4">
                                    <h4>Recent Reference Usage</h4>
                                    @php
                                        $recentUsages = \App\Models\ReferenceUsage::with(['reference', 'user', 'order'])
                                            ->where('giver_user_id', Auth::user()->id)
                                            ->orderBy('created_at', 'desc')
                                            ->limit(5)
                                            ->get();
                                    @endphp
                                    
                                    @if($recentUsages->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Reference Code</th>
                                                        <th>Used By</th>
                                                        <th>Order Total</th>
                                                        <th>Your Earning</th>
                                                        <th>Customer Discount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentUsages as $usage)
                                                    <tr>
                                                        <td>{{ $usage->created_at->format('M d, Y') }}</td>
                                                        <td><strong>{{ $usage->reference->code }}</strong></td>
                                                        <td>{{ $usage->user->name }}</td>
                                                        <td>₹{{ number_format((float)($usage->order_total ?? 0), 2) }}</td>
                                                        <td class="text-success">₹{{ number_format((float)($usage->giver_earning_amount ?? 0), 2) }}</td>
                                                        <td class="text-primary">₹{{ number_format((float)($usage->applyer_discount_amount ?? 0), 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> No reference usage history yet. Start sharing your reference codes to earn!
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="tab-pane fade" id="v-pills-settings" role="tabpanel"
                            aria-labelledby="v-pills-settings-tab" tabindex="0"">

                            <div class="shipping-address-billing-address-account">
                                <div class="half">
                                    <h2 class="title">Billing Address</h2>
                                    @php
                                        $lastOrder = $orders->last();
                                    @endphp
                                    @if (isset($lastOrder->billingDetail))
                                        <p class="address">
                                            {{ $lastOrder->billingDetail->billing_address }} <br>
                                            {{ $lastOrder->billingDetail->billing_city }}, <br>
                                            {{ $lastOrder->billingDetail->billing_state }}
                                            {{ $lastOrder->billingDetail->billing_postal_code }} <br>
                                            {{ $lastOrder->billingDetail->billing_country }}
                                        </p>
                                    @endif
                                </div>
                                <div class="half">
                                    <h2 class="title">Shipping Address</h2>
                                    @if (isset($lastOrder->billingDetail->shippingAddress))
                                        <p class="address">
                                            {{ $lastOrder->billingDetail->shippingAddress->address_line_1 }} <br>
                                            {{ $lastOrder->billingDetail->shippingAddress->city }}, <br>
                                            {{ $lastOrder->billingDetail->shippingAddress->state }}
                                            {{ $lastOrder->billingDetail->shippingAddress->postal_code }} <br>
                                            {{ $lastOrder->billingDetail->shippingAddress->country }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="v-pills-settingsa" role="tabpanel"
                            aria-labelledby="v-pills-settingsa-tab" tabindex="0">
                            <form action="{{ route('update.user.details') }}" method="POST"
                                class="account-details-area">
                                @csrf
                                @method('POST')
                                <h2 class="title">Account Details</h2>
                                <input type="text" placeholder="Name" name="name"
                                    value="{{ Auth::user()->name }}" required>
                                <input type="email" placeholder="Email Address *" name="email"
                                    value="{{ Auth::user()->email }}" required>
                                <input type="password" placeholder="Current Password *" name="current_password">
                                <input type="password" placeholder="New Password *" name="new_password">
                                <input type="password" placeholder="Confirm Password *" name="confirm_new_password">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <button type="submit" class="rts-btn btn-primary" id="updateUserData">Save
                                    Change</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="v-pills-settingsb" role="tabpanel"
                            aria-labelledby="v-pills-settingsb-tab" tabindex="0">...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rts-shorts-service-area rts-section-gap bg_primary">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M55.7029 25.2971C51.642 21.2363 46.2429 19 40.5 19C34.7571 19 29.358 21.2363 25.2971 25.2971C21.2364 29.358 19 34.7571 19 40.5C19 46.2429 21.2364 51.642 25.2971 55.7029C29.358 59.7637 34.7571 62 40.5 62C46.2429 62 51.642 59.7637 55.7029 55.7029C59.7636 51.642 62 46.2429 62 40.5C62 34.7571 59.7636 29.358 55.7029 25.2971ZM40.5 59.4805C30.0341 59.4805 21.5195 50.9659 21.5195 40.5C21.5195 30.0341 30.0341 21.5195 40.5 21.5195C50.9659 21.5195 59.4805 30.0341 59.4805 40.5C59.4805 50.9659 50.9659 59.4805 40.5 59.4805Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M41.8494 39.2402H39.1506C37.6131 39.2402 36.3623 37.9895 36.3623 36.452C36.3623 34.9145 37.6132 33.6638 39.1506 33.6638H44.548C45.2438 33.6638 45.8078 33.0997 45.8078 32.404C45.8078 31.7083 45.2438 31.1442 44.548 31.1442H41.7598V28.3559C41.7598 27.6602 41.1957 27.0962 40.5 27.0962C39.8043 27.0962 39.2402 27.6602 39.2402 28.3559V31.1442H39.1507C36.2239 31.1442 33.8429 33.5253 33.8429 36.452C33.8429 39.3787 36.224 41.7598 39.1507 41.7598H41.8495C43.3869 41.7598 44.6377 43.0106 44.6377 44.548C44.6377 46.0855 43.3869 47.3363 41.8495 47.3363H36.452C35.7563 47.3363 35.1923 47.9004 35.1923 48.5961C35.1923 49.2918 35.7563 49.8559 36.452 49.8559H39.2402V52.6442C39.2402 53.34 39.8043 53.904 40.5 53.904C41.1957 53.904 41.7598 53.34 41.7598 52.6442V49.8559H41.8494C44.7761 49.8559 47.1571 47.4747 47.1571 44.548C47.1571 41.6214 44.7761 39.2402 41.8494 39.2402Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">Best Prices &amp; Offers</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M55.5564 24.4436C51.4012 20.2884 45.8763 18 40 18C34.1237 18 28.5988 20.2884 24.4436 24.4436C20.2884 28.5988 18 34.1237 18 40C18 45.8763 20.2884 51.4012 24.4436 55.5564C28.5988 59.7116 34.1237 62 40 62C45.8763 62 51.4012 59.7116 55.5564 55.5564C59.7116 51.4012 62 45.8763 62 40C62 34.1237 59.7116 28.5988 55.5564 24.4436ZM40 59.4219C29.2907 59.4219 20.5781 50.7093 20.5781 40C20.5781 29.2907 29.2907 20.5781 40 20.5781C50.7093 20.5781 59.4219 29.2907 59.4219 40C59.4219 50.7093 50.7093 59.4219 40 59.4219Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M42.4009 34.7622H35.0294L36.295 33.4966C36.7982 32.9934 36.7982 32.177 36.295 31.6738C35.7914 31.1703 34.9753 31.1703 34.4718 31.6738L31.0058 35.1398C30.5022 35.6434 30.5022 36.4594 31.0058 36.9626L34.4718 40.429C34.7236 40.6808 35.0536 40.8067 35.3832 40.8067C35.7132 40.8067 36.0432 40.6808 36.295 40.429C36.7982 39.9255 36.7982 39.1094 36.295 38.6059L35.0291 37.3403H42.4009C44.8229 37.3403 46.7934 39.3108 46.7934 41.7328C46.7934 44.1549 44.8229 46.1254 42.4009 46.1254H37.8925C37.1805 46.1254 36.6035 46.7028 36.6035 47.4145C36.6035 48.1265 37.1805 48.7035 37.8925 48.7035H42.4009C46.2446 48.7035 49.3716 45.5765 49.3716 41.7328C49.3716 37.8892 46.2446 34.7622 42.4009 34.7622Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">100% Return Policy</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M26.2667 26.2667C29.935 22.5983 34.8122 20.5781 40 20.5781C43.9672 20.5781 47.8028 21.7849 51.0284 24.0128L48.5382 24.2682L48.8013 26.8328L55.5044 26.1453L54.8169 19.4422L52.2522 19.7053L52.4751 21.8787C48.8247 19.3627 44.4866 18 40 18C34.1236 18 28.5989 20.2884 24.4437 24.4437C20.2884 28.5989 18 34.1236 18 40C18 44.3993 19.2946 48.6457 21.7437 52.28L23.8816 50.8393C23.852 50.7952 23.8232 50.7508 23.7939 50.7065C21.69 47.5289 20.5781 43.8307 20.5781 40C20.5781 34.8123 22.5983 29.935 26.2667 26.2667Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M58.2564 27.72L56.1184 29.1607C56.148 29.2047 56.1768 29.2493 56.2061 29.2935C58.3099 32.4711 59.4219 36.1693 59.4219 40C59.4219 45.1878 57.4017 50.065 53.7333 53.7333C50.0651 57.4017 45.1879 59.4219 40 59.4219C36.0328 59.4219 32.1972 58.2151 28.9716 55.9872L31.4618 55.7318L31.1987 53.1672L24.4956 53.8547L25.1831 60.5578L27.7478 60.2947L27.5249 58.1213C31.1754 60.6373 35.5135 62 40 62C45.8764 62 51.4011 59.7116 55.5564 55.5563C59.7117 51.4011 62 45.8764 62 40C62 35.6007 60.7055 31.3543 58.2564 27.72Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M28.7407 42.7057L30.4096 41.1632C31.6739 40 31.9142 39.2161 31.9142 38.3564C31.9142 36.7127 30.5108 35.6633 28.4753 35.6633C26.7305 35.6633 25.4788 36.3966 24.8087 37.5093L26.6673 38.546C27.0213 37.9771 27.6029 37.6863 28.2477 37.6863C29.0063 37.6863 29.3856 38.0276 29.3856 38.5966C29.3856 38.9633 29.2845 39.3679 28.5764 40.0254L25.2639 43.123V44.6907H32.1544V42.7057L28.7407 42.7057Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M40.1076 42.9965H41.4224V41.0115H40.1076V39.507H37.7433V41.0115H35.948L39.5512 35.8404H36.9594L32.9894 41.3655V42.9965H37.6674V44.6906H40.1076V42.9965Z"
                                    fill="#009ec9"></path>
                                <path d="M43.6986 45.955L47.8708 34.045H45.7341L41.5618 45.955H43.6986Z" fill="#009ec9">
                                </path>
                                <path
                                    d="M49.995 39.1908V37.8254H52.3213L49.3375 44.6906H52.0685L55.1913 37.4081V35.8404H47.8582V39.1908H49.995Z"
                                    fill="#009ec9"></path>
                            </svg>

                        </div>
                        <div class="information">
                            <h4 class="title">Support 24/7</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 col-12">
                    <!-- single service area start -->
                    <div class="single-short-service-area-start">
                        <div class="icon-area">
                            <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="40" r="40" fill="white"></circle>
                                <path
                                    d="M57.0347 37.5029C54.0518 29.3353 48.6248 23.7668 48.3952 23.5339L46.2276 21.3333V29.6016C46.2276 30.3124 45.658 30.8906 44.9578 30.8906C44.2577 30.8906 43.688 30.3124 43.688 29.6016C43.688 23.2045 38.5614 18 32.26 18H30.9902V19.2891C30.9902 25.3093 27.0988 29.646 24.1414 35.2212C21.1581 40.8449 21.3008 47.7349 24.5138 53.2021C27.7234 58.6637 33.5291 62 39.7786 62H40.3686C46.1822 62 51.6369 59.1045 54.9597 54.2545C58.2819 49.4054 59.056 43.0371 57.0347 37.5029ZM52.8748 52.7824C50.0265 56.9398 45.3513 59.4219 40.3686 59.4219H39.7786C34.4416 59.4219 29.4281 56.5325 26.6947 51.8813C23.9369 47.1886 23.8153 41.2733 26.3773 36.4436C29.1752 31.1691 32.9752 26.8193 33.4744 20.662C37.803 21.265 41.1483 25.0441 41.1483 29.6015C41.1483 31.7338 42.8572 33.4687 44.9577 33.4687C47.0581 33.4687 48.767 31.7338 48.767 29.6015V27.9161C50.54 30.2131 53.0138 33.9094 54.6534 38.399C56.3856 43.1416 55.704 48.653 52.8748 52.7824Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M38.6089 40C38.6089 37.8676 36.9 36.1328 34.7996 36.1328C32.6991 36.1328 30.9902 37.8676 30.9902 40C30.9902 42.1324 32.6991 43.8672 34.7996 43.8672C36.9 43.8672 38.6089 42.1324 38.6089 40ZM33.5298 40C33.5298 39.2892 34.0994 38.7109 34.7996 38.7109C35.4997 38.7109 36.0693 39.2892 36.0693 40C36.0693 40.7108 35.4997 41.2891 34.7996 41.2891C34.0994 41.2891 33.5298 40.7108 33.5298 40Z"
                                    fill="#009ec9"></path>
                                <path
                                    d="M44.9578 46.4453C42.8573 46.4453 41.1485 48.1801 41.1485 50.3125C41.1485 52.4449 42.8573 54.1797 44.9578 54.1797C47.0583 54.1797 48.7672 52.4449 48.7672 50.3125C48.7672 48.1801 47.0583 46.4453 44.9578 46.4453ZM44.9578 51.6016C44.2577 51.6016 43.688 51.0233 43.688 50.3125C43.688 49.6017 44.2577 49.0234 44.9578 49.0234C45.658 49.0234 46.2276 49.6017 46.2276 50.3125C46.2276 51.0233 45.658 51.6016 44.9578 51.6016Z"
                                    fill="#009ec9"></path>
                                <path d="M32.5466 52.0632L45.2407 36.599L47.1911 38.249L34.4969 53.7132L32.5466 52.0632Z"
                                    fill="#009ec9"></path>
                            </svg>
                        </div>
                        <div class="information">
                            <h4 class="title">Great Offer Daily Deal</h4>
                            <p class="disc">
                                We prepared special discounts you on grocery products.
                            </p>
                        </div>
                    </div>
                    <!-- single service area end -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Reference Coupon JavaScript Functions
        function copyReferenceCodeToClipboard(code) {
            copyToClipboard(code);
        }

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Code copied to clipboard!', 'success');
                }).catch(() => {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showToast('Code copied to clipboard!', 'success');
            } catch (err) {
                showToast('Failed to copy code. Please copy manually.', 'error');
            }
            document.body.removeChild(textArea);
        }

        function shareSpecificReference(code, name) {
            showShareModal(code, name);
        }

        function showShareModal(referenceCode, referenceName) {
            // Create modal for sharing options
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'shareModal';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Share Reference Code: ${referenceCode}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-success w-100" onclick="shareToWhatsApp('${referenceCode}')">
                                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary w-100" onclick="shareToTelegram('${referenceCode}')">
                                        <i class="fab fa-telegram me-2"></i>Telegram
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary w-100" onclick="shareToFacebook('${referenceCode}')">
                                        <i class="fab fa-facebook me-2"></i>Facebook
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-info w-100" onclick="shareToTwitter('${referenceCode}')">
                                        <i class="fab fa-twitter me-2"></i>Twitter
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">Share Message:</label>
                                        <textarea id="shareMessage" class="form-control" rows="4" readonly></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-outline-primary w-100" onclick="copyShareMessage()">
                                        <i class="fa fa-copy me-2"></i>Copy Message
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Get shareable content from server
            getShareableContent(referenceCode, 'general').then(data => {
                const shareMessageElement = document.getElementById('shareMessage');
                if (data && data.share_message) {
                    shareMessageElement.value = data.share_message;
                } else {
                    shareMessageElement.value = `Get exclusive discounts with my reference code: ${referenceCode}. Shop now and save on your orders!`;
                }
            }).catch(error => {
                console.error('Error loading share content:', error);
                // Fallback message if API fails
                const shareMessageElement = document.getElementById('shareMessage');
                if (shareMessageElement) {
                    shareMessageElement.value = `Get exclusive discounts with my reference code: ${referenceCode}. Shop now and save on your orders!`;
                }
            });
            
            // Show modal
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
            
            // Clean up modal when hidden
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }

        async function getShareableContent(referenceCode, platform = 'general') {
            try {
                // Check if CSRF token exists
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    throw new Error('CSRF token not found. Please refresh the page.');
                }

                const response = await fetch('{{ route("get.shareable.reference") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reference_code: referenceCode,
                        platform: platform
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    return result.data;
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error getting shareable content:', error);
                showToast(`Failed to generate share content: ${error.message}`, 'error');
                return null;
            }
        }

        async function shareToWhatsApp(referenceCode) {
            const data = await getShareableContent(referenceCode, 'whatsapp');
            if (data && data.whatsapp_link) {
                window.open(data.whatsapp_link, '_blank');
            } else {
                // Fallback if API fails
                const fallbackMessage = `🎉 Get exclusive discounts with my reference code: ${referenceCode}. Shop now and save!`;
                const fallbackLink = `https://wa.me/?text=${encodeURIComponent(fallbackMessage)}`;
                window.open(fallbackLink, '_blank');
            }
        }

        async function shareToTelegram(referenceCode) {
            const data = await getShareableContent(referenceCode, 'telegram');
            if (data && data.telegram_link) {
                window.open(data.telegram_link, '_blank');
            } else {
                // Fallback if API fails
                const fallbackMessage = `🎉 Get exclusive discounts with my reference code: ${referenceCode}`;
                const fallbackLink = `https://t.me/share/url?text=${encodeURIComponent(fallbackMessage)}`;
                window.open(fallbackLink, '_blank');
            }
        }

        async function shareToFacebook(referenceCode) {
            const data = await getShareableContent(referenceCode, 'facebook');
            if (data && data.facebook_link) {
                window.open(data.facebook_link, '_blank');
            } else {
                // Fallback if API fails
                const fallbackUrl = `${window.location.origin}/shop?ref=${referenceCode}`;
                const fallbackLink = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(fallbackUrl)}`;
                window.open(fallbackLink, '_blank');
            }
        }

        async function shareToTwitter(referenceCode) {
            const data = await getShareableContent(referenceCode, 'twitter');
            if (data && data.twitter_link) {
                window.open(data.twitter_link, '_blank');
            } else {
                // Fallback if API fails
                const fallbackMessage = `🎉 Exclusive discounts! Use code: ${referenceCode} for special pricing. #Fitness #Protein`;
                const fallbackLink = `https://twitter.com/intent/tweet?text=${encodeURIComponent(fallbackMessage)}`;
                window.open(fallbackLink, '_blank');
            }
        }

        function copyShareMessage() {
            const shareMessage = document.getElementById('shareMessage').value;
            copyToClipboard(shareMessage);
        }

        function fallbackShare(text) {
            copyToClipboard(text);
            showToast('Share text copied to clipboard! You can now paste it anywhere.', 'info');
        }

        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed`;
            toast.style.cssText = `
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                min-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fa fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        // Add CSS for toast animation and reference coupon styling
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                .reference-row:hover {
                    background-color: #f8f9fa !important;
                    transition: background-color 0.2s ease;
                }
                
                .coupon-code-badge .badge {
                    font-size: 1rem !important;
                    letter-spacing: 1px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    border: none !important;
                }
                
                .btn-group .btn {
                    margin: 0 2px;
                    border-radius: 5px;
                }
                
                .card-header {
                    border-bottom: 0;
                }
                
                .card-footer {
                    border-top: 1px solid #dee2e6;
                    background-color: #f8f9fa;
                }
                
                .table th {
                    font-weight: 600;
                    color: #495057;
                    font-size: 0.875rem;
                    padding: 15px 12px;
                }
                
                .table td {
                    padding: 15px 12px;
                    vertical-align: middle;
                }
                
                .reference-coupons-account .card {
                    border-radius: 10px;
                    overflow: hidden;
                    border: none;
                }
                
                .bg-gradient {
                    border-radius: 10px 10px 0 0;
                }
                
                .title {
                    color: #2c3e50;
                    font-weight: 600;
                }
                
                @media (max-width: 768px) {
                    .btn-group {
                        display: flex;
                        flex-direction: row;
                        gap: 5px;
                    }
                    
                    .coupon-code-badge .badge {
                        font-size: 0.85rem !important;
                        padding: 0.5rem 0.75rem;
                    }
                    
                    .table th, .table td {
                        padding: 10px 8px;
                        font-size: 0.85rem;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    </script>
@endsection
