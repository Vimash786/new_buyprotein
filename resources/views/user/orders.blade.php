@extends('layouts.app')

@section('content')
    <div class="rts-navigation-area-breadcrumb">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navigator-breadcrumb-wrapper">
                        <a href="{{ route('home') }}">Home</a>
                        <i class="fa-regular fa-chevron-right"></i>
                        <a class="current" href="#">My Orders</a>
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

    <div class="orders-area rts-section-gap">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h2 class="title mb-4">My Orders</h2>
                    
                    @if($orders->count() > 0)
                        <div class="orders-list">
                            @foreach($orders as $order)
                                <div class="order-card mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h5 class="mb-0">Order #{{ $order->order_number }}</h5>
                                                    <small class="text-muted">Placed on {{ $order->created_at->format('M d, Y') }}</small>
                                                </div>
                                                <div class="col-md-6 text-md-end">
                                                    <h6 class="mb-0 text-primary">₹{{ number_format($order->total_order_amount, 2) }}</h6>
                                                    @if($order->billingDetail)
                                                        <small class="text-muted">
                                                            {{ ucfirst($order->billingDetail->payment_method) }}
                                                            @if($order->billingDetail->payment_status == 'complete')
                                                                <span class="badge bg-success ms-1">Paid</span>
                                                            @else
                                                                <span class="badge bg-warning ms-1">{{ ucfirst($order->billingDetail->payment_status) }}</span>
                                                            @endif
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if($order->orderSellerProducts && $order->orderSellerProducts->count() > 0)
                                                <div class="order-items">
                                                    @foreach($order->orderSellerProducts as $item)
                                                        <div class="order-item d-flex align-items-center mb-3 pb-3 border-bottom">
                                                            <div class="item-image me-3">
                                                                @if($item->product && $item->product->thumbnail_image)
                                                                    <img src="{{ asset('storage/' . $item->product->thumbnail_image) }}" 
                                                                         alt="{{ $item->product->name ?? 'Product' }}" 
                                                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                                                @else
                                                                    <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                                        <i class="fas fa-image text-muted"></i>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="item-details flex-grow-1">
                                                                <h6 class="mb-1">{{ $item->product->name ?? 'Product Name' }}</h6>
                                                                <small class="text-muted">Quantity: {{ $item->quantity }} | Unit Price: ₹{{ number_format($item->unit_price, 2) }}</small>
                                                            </div>
                                                            <div class="item-total">
                                                                <strong>₹{{ number_format($item->total_amount, 2) }}</strong>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            @if($order->billingDetail)
                                                <div class="shipping-address mt-3">
                                                    <h6>Shipping Address:</h6>
                                                    <p class="mb-0">
                                                        {{ $order->billingDetail->billing_first_name }} {{ $order->billingDetail->billing_last_name }}<br>
                                                        {{ $order->billingDetail->billing_address }}<br>
                                                        {{ $order->billingDetail->billing_city }}, {{ $order->billingDetail->billing_state }} {{ $order->billingDetail->billing_postal_code }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h4>No Orders Yet</h4>
                            <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                            <a href="{{ route('shop') }}" class="rts-btn btn-primary">Start Shopping</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
.order-card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.order-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.badge {
    font-size: 0.75rem;
}

.order-items {
    max-height: 400px;
    overflow-y: auto;
}
</style>
