<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 9px;
        }
        
        /* Compact Header */
        .invoice-header {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #2dc2fa;
            display: flex;
           
        }

        .company-info {
            font-size: 9px;
            line-height: 1.2;
        }

        .invoice-meta {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #2dc2fa;
            padding-bottom: 10px;
        }
        
        .company-logo {
            width: 150px;
            height: auto;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2dc2fa;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .company-details {
            font-size: 10px;
            line-height: 1.6;
            color: #555;
        }
        
        .invoice-title {
            text-align: right;
            flex: 0 0 auto;
        }
        
        .invoice-title h1 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .invoice-number {
            font-size: 14px;
            color: #2dc2fa;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .invoice-dates {
            display: flex;
            gap: 15px;
        }
        
        .date-item {
            display: flex;
            flex-direction: column;
        }
        
        .date-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .date-value {
            font-size: 9px;
            color: #333;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 3px 6px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            align-self: flex-start;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-partially_shipped { background-color: #e2d9f3; color: #6f42c1; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .billing-box {
            flex: 1;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            border-left: 2px solid #2dc2fa;
        }
        
        .billing-title {
            font-size: 9px;
            font-weight: bold;
            color: #2dc2fa;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .billing-content {
            font-size: 8px;
            line-height: 1.3;
        }
        
        .table-container {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        
        .table th {
            background-color: #2dc2fa;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            font-size: 9px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .product-name {
            font-weight: bold;
            color: #333;
        }
        
        .product-description {
            color: #666;
            font-size: 7px;
            margin-top: 1px;
        }
        
        .seller-name {
            color: #2dc2fa;
            font-weight: bold;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        
        .totals-table .label {
            text-align: left;
            color: #666;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #2dc2fa;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        
        .payment-info {
            background-color: #e8f4fd;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 10px;
            border-left: 2px solid #2dc2fa;
        }
        
        .payment-title {
            font-size: 9px;
            font-weight: bold;
            color: #2dc2fa;
            margin-bottom: 3px;
        }
        
        .payment-details {
            display: flex;
            gap: 15px;
            font-size: 8px;
        }
        
        .tax-info {
            background-color: #fff3cd;
            padding: 4px;
            border-radius: 3px;
            margin-bottom: 10px;
            border-left: 2px solid #ffc107;
        }
        
        .tax-title {
            font-size: 9px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 2px;
        }
        
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .footer-note {
            margin-bottom: 2px;
        }
        
        .currency {
            font-family: Arial, sans-serif;
        }
        
        /* General Adjustments for A4 */
        @media print {
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
            }

            .invoice-container {
                margin: 0;
                padding: 8px;
            }

            .invoice-header, .addresses-section, .items-section, .totals-section, .notes-section {
                page-break-inside: avoid;
            }

            .items-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <!-- Company Logo SVG -->
                <div class="company-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="200" height="50" viewBox="0 0 472.000000 99.000000" preserveAspectRatio="xMidYMid meet">
                        <style>
                            .logo-primary { fill: #2dc2fa; }
                            .logo-accent { fill: #333; }
                        </style>
                        <g transform="translate(0.000000,99.000000) scale(0.100000,-0.100000)" stroke="none">
                            <path d="M587 803 l-28 -4 3 -242 c3 -232 4 -244 26 -279 32 -51 91 -73 177 -65 76 7 109 26 136 78 17 35 19 60 19 274 l0 235 -65 0 -65 0 0 -214 c0 -118 -4 -226 -10 -240 -6 -18 -17 -26 -34 -26 -47 0 -50 17 -54 256 l-4 224 -23 0 c-13 0 -29 2 -37 3 -7 2 -26 2 -41 0z" class="logo-accent"/>
                            <path d="M1300 800 l-64 -5 -27 -85 c-15 -47 -29 -78 -32 -70 -3 8 -15 48 -27 88 l-21 72 -74 0 -73 0 61 -147 61 -148 5 -140 6 -140 68 -3 67 -3 0 122 0 121 66 165 c36 91 62 168 57 172 -4 3 -37 4 -73 1z" class="logo-accent"/>
                            <path d="M1698 802 l-48 -3 0 -290 0 -289 70 0 70 0 0 115 0 115 56 0 c69 0 112 19 143 66 52 75 33 216 -35 260 -28 19 -159 32 -256 26z m160 -118 c16 -11 22 -25 22 -53 0 -47 -16 -69 -57 -77 l-33 -6 0 76 c0 72 1 76 23 76 12 0 33 -7 45 -16z" class="logo-accent"/>
                            <path d="M2688 810 c-88 -26 -118 -85 -125 -250 -7 -143 9 -249 43 -290 52 -61 199 -80 262 -33 68 51 82 97 82 278 0 195 -16 240 -100 283 -46 23 -110 28 -162 12z m106 -122 c13 -18 16 -53 16 -179 0 -141 -2 -158 -19 -173 -11 -10 -30 -16 -42 -14 -42 6 -50 40 -47 206 3 139 5 155 22 168 28 20 53 17 70 -8z" class="logo-accent"/>
                            <path d="M3120 843 c0 -22 3 -23 69 -23 58 0 70 3 74 18 4 16 5 16 6 0 1 -11 9 -18 21 -18 15 0 20 7 20 25 0 21 -5 25 -29 25 -23 0 -31 -5 -34 -22 l-4 -23 -2 23 c-1 22 -5 23 -61 21 -56 -2 -60 -4 -60 -26z" class="logo-primary"/>
                            <path d="M3477 802 c-16 -3 -17 -28 -15 -290 l3 -287 158 -3 157 -3 0 61 0 60 -90 0 -90 0 0 65 0 64 83 3 82 3 0 55 0 55 -82 3 -83 3 0 49 0 50 90 0 90 0 0 55 c0 46 -3 55 -17 55 -214 4 -272 4 -286 2z" class="logo-accent"/>
                        </g>
                    </svg>
                </div>
                
                <div class="company-name">BuyProtein</div>
                <div class="company-tagline">Professional Nutrition & Supplements</div>
                
                <div class="company-details">
                    <strong>BuyProtein Private Limited</strong><br>
                    123 Business District, Tech Park<br>
                    Bangalore, Karnataka 560001<br>
                    India<br><br>
                    <strong>Contact:</strong> +91 9876543210<br>
                    <strong>Email:</strong> support@buyprotein.com<br>
                    <strong>Website:</strong> www.buyprotein.com<br>
                    <strong>GST Number:</strong> 29AABCU9603R1ZX
                </div>
            </div>

            <div class="invoice-meta">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $order->order_number }}</div>
                
                <table class="meta-table">
                    <tr>
                        <td class="label">Invoice Date:</td>
                        <td>{{ now()->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Order Date:</td>
                        <td>{{ $order->created_at->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Due Date:</td>
                        <td>{{ now()->addDays(30)->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td>
                            @if($type === 'seller' && isset($orderItem))
                                <span class="status-badge status-{{ $orderItem->status }}">{{ ucfirst($orderItem->status) }}</span>
                            @else
                                <span class="status-badge status-{{ str_replace('_', '-', $order->overall_status) }}">{{ ucfirst(str_replace('_', ' ', $order->overall_status)) }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Addresses Section -->
        <div class="addresses-section">
            <!-- Bill To -->
            <div class="address-block">
                <div class="address-title">Bill To</div>
                <div class="address-content">
                    <strong>{{ $customer->name }}</strong><br>
                    {{ $customer->email }}<br>
                    @if($customer->phone)
                        Phone: {{ $customer->phone }}<br>
                    @endif
                    
                    @if($billingDetail)
                        <br><strong>Billing Address:</strong><br>
                        {{ $billingDetail->billing_address }}<br>
                        {{ $billingDetail->billing_city }}, {{ $billingDetail->billing_state }}<br>
                        {{ $billingDetail->billing_postal_code }}, {{ $billingDetail->billing_country ?? 'India' }}
                        
                        @if($billingDetail->gst_number)
                            <br><br><strong>GST Number:</strong> {{ $billingDetail->gst_number }}
                        @endif
                    @endif
                </div>
            </div>

            <!-- Ship To -->
            <div class="address-block">
                <div class="address-title">Ship To</div>
                <div class="address-content">
                    @if($shippingAddress)
                        <strong>{{ $customer->name }}</strong><br>
                        {{ $shippingAddress->address_line_1 }}<br>
                        @if($shippingAddress->address_line_2){{ $shippingAddress->address_line_2 }}<br>@endif
                        {{ $shippingAddress->city }}, {{ $shippingAddress->state }}<br>
                        {{ $shippingAddress->postal_code }}, {{ $shippingAddress->country ?? 'India' }}
                        @if($shippingAddress->phone)
                            <br><br><strong>Phone:</strong> {{ $shippingAddress->phone }}
                        @endif
                    @else
                        <em>Same as billing address</em>
                    @endif
                </div>
            </div>

            @if($type === 'seller' && isset($seller))
            <!-- Seller Info -->
            <div class="address-block">
                <div class="address-title">Seller Details</div>
                <div class="address-content">
                    <strong>{{ $seller->company_name ?? $seller->business_name }}</strong><br>
                    {{ $seller->business_address }}<br>
                    @if($seller->city){{ $seller->city }}, @endif
                    @if($seller->state){{ $seller->state }}@endif
                    @if($seller->pincode) - {{ $seller->pincode }}@endif<br>
                    <br><strong>Contact:</strong> {{ $seller->contact_number }}<br>
                    <strong>Email:</strong> {{ $seller->business_email }}
                    @if($seller->gst_number)
                        <br><strong>GST No:</strong> {{ $seller->gst_number }}
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Invoice Details -->
        @if($billingDetail)
        <div class="invoice-details">
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">{{ $billingDetail->payment_method ?? 'Online Payment' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value">{{ ucfirst($billingDetail->payment_status ?? 'pending') }}</span>
            </div>
            @if($billingDetail->gst_number)
            <div class="detail-row">
                <span class="detail-label">Customer GST:</span>
                <span class="detail-value">{{ $billingDetail->gst_number }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Items Section -->
        <div class="items-section">
            <div class="section-title">Order Items</div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Product Details</th>
                        @if($type === 'admin')
                            <th style="width: 20%;">Seller</th>
                        @endif
                        <th style="width: 15%;">Variant</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 10%;" class="text-right">Unit Price</th>
                        <th style="width: 10%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($type === 'seller' && isset($orderItem))
                        <tr>
                            <td>
                                <div class="product-name">{{ $orderItem->product->name ?? 'Product not found' }}</div>
                                
                            </td>
                            <td>
                                @if($orderItem->variantCombination)
                                    <span class="variant-info">{{ $orderItem->variantCombination->variant_options ?? 'N/A' }}</span>
                                @else
                                    <span class="variant-info">Standard</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $orderItem->quantity }}</td>
                            <td class="text-right">Rs. {{ number_format($orderItem->unit_price, 2) }}</td>
                            <td class="text-right font-bold">Rs. {{ number_format($orderItem->total_amount, 2) }}</td>
                        </tr>
                    @else
                        @foreach($order->orderSellerProducts as $item)
                        <tr>
                            <td>
                                <div class="product-name">{{ $item->product->name ?? 'Product not found' }}</div>
                                @if($item->product && $item->product->description)
                                    <div class="product-desc">{{ \Illuminate\Support\Str::limit($item->product->description, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $item->seller->company_name ?? $item->seller->business_name ?? 'N/A' }}</td>
                            <td>
                                @if($item->variantCombination)
                                    <span class="variant-info">{{ $item->variantCombination->variant_options ?? 'N/A' }}</span>
                                @else
                                    <span class="variant-info">Standard</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right font-bold">₹{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">
                        @if($type === 'seller' && isset($orderItem))
                            ₹{{ number_format($orderItem->total_amount, 2) }}
                        @else
                            ₹{{ number_format($billingDetail->subtotal ?? $order->total_order_amount, 2) }}
                        @endif
                    </td>
                </tr>
                @if($billingDetail && $billingDetail->discount_amount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="value color-success">-₹{{ number_format($billingDetail->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($billingDetail && $billingDetail->tax_amount > 0)
                <tr>
                    <td class="label">Tax (GST):</td>
                    <td class="value">₹{{ number_format($billingDetail->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($billingDetail && $billingDetail->shipping_charge > 0)
                <tr>
                    <td class="label">Shipping:</td>
                    <td class="value">₹{{ number_format($billingDetail->shipping_charge, 2) }}</td>
                </tr>
                @else
                <tr>
                    <td class="label">Shipping:</td>
                    <td class="value color-success">FREE</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL AMOUNT</td>
                    <td>
                        @if($type === 'seller' && isset($orderItem))
                            ₹{{ number_format($orderItem->total_amount, 2) }}
                        @else
                            ₹{{ number_format($billingDetail->total_amount ?? $order->total_order_amount, 2) }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tax Information -->
        <div class="tax-info">
            <div class="tax-title">Tax Information</div>
            <div class="tax-content">
                <strong>HSN/SAC Code:</strong> 21069090 (Nutritional Supplements)<br>
                <strong>Tax Rate:</strong> 18% GST (9% CGST + 9% SGST for intra-state / 18% IGST for inter-state)<br>
                <strong>Tax Treatment:</strong> Goods and Services Tax as applicable under GST Act
            </div>
        </div>

        <!-- Notes Section -->
        @if(($type === 'seller' && isset($orderItem) && $orderItem->notes) || ($type === 'admin' && $order->orderSellerProducts->first() && $order->orderSellerProducts->first()->notes))
        <div class="notes-section">
            <div class="notes-title">Order Notes</div>
            <div class="notes-content">
                @if($type === 'seller' && isset($orderItem))
                    {{ $orderItem->notes }}
                @else
                    {{ $order->orderSellerProducts->first()->notes ?? 'No special notes for this order.' }}
                @endif
            </div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="notes-section">
            <div class="notes-title">Terms & Conditions</div>
            <div class="notes-content">
                1. Payment is due within 30 days of invoice date.<br>
                2. All products are covered under our return policy as per terms on our website.<br>
                3. For any queries regarding this invoice, please contact our support team.<br>
                4. This invoice is generated electronically and does not require a physical signature.<br>
                5. Prices are inclusive of all applicable taxes unless specified otherwise.
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong class="footer-highlight">Thank you for choosing BuyProtein!</strong></p>
            <p>For support, visit <span class="footer-highlight">www.buyprotein.com</span> or email <span class="footer-highlight">support@buyprotein.com</span></p>
            <p>Follow us on social media for the latest updates and exclusive offers.</p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
            <p style="font-size: 10px; color: #999;">
                This invoice was generated electronically on {{ now()->format('F d, Y \a\t g:i A') }}<br>
                BuyProtein Private Limited | CIN: U74999KA2023PTC123456 | All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
