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
                                                    <td><a href="{{ route('invoice.order.download', $order->id) }}" class="btn-small d-block">View</a></td>
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
