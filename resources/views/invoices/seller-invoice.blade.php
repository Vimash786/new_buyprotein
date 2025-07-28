<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .company-logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info div {
            width: 48%;
        }
        .invoice-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .seller-info {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-confirmed { background-color: #d4edda; color: #155724; }
        .status-shipped { background-color: #d1ecf1; color: #0c5460; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-logo">BuyProtein</div>
        <div class="invoice-title">SELLER INVOICE</div>
        <p>Professional Nutrition & Supplements</p>
    </div>

    <div class="invoice-details">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong>Invoice Number:</strong> {{ $order->order_number }}-{{ $orderItem->id }}<br>
                <strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}<br>
                <strong>Status:</strong> 
                <span class="status-badge status-{{ $orderItem->status }}">{{ ucfirst($orderItem->status) }}</span>
            </div>
            <div style="text-align: right;">
                <strong>Invoice Date:</strong> {{ now()->format('F d, Y') }}<br>
                <strong>Due Date:</strong> {{ now()->addDays(30)->format('F d, Y') }}
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
        <div style="width: 48%;">
            <div class="section-title">Seller Information</div>
            <div class="seller-info">
                <strong>{{ $seller->company_name ?? $seller->business_name }}</strong><br>
                {{ $seller->business_address }}<br>
                @if($seller->city), {{ $seller->city }}@endif
                @if($seller->state), {{ $seller->state }}@endif
                @if($seller->pincode)- {{ $seller->pincode }}@endif<br>
                <strong>Contact:</strong> {{ $seller->contact_number }}<br>
                <strong>Email:</strong> {{ $seller->business_email }}
                @if($seller->gst_number)
                <br><strong>GST No:</strong> {{ $seller->gst_number }}
                @endif
            </div>
        </div>

        <div style="width: 48%;">
            <div class="section-title">Customer Information</div>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                <strong>{{ $customer->name }}</strong><br>
                {{ $customer->email }}<br>
                @if($customer->phone)
                Phone: {{ $customer->phone }}<br>
                @endif
                
                @if($shippingAddress)
                <br><strong>Shipping Address:</strong><br>
                {{ $shippingAddress->address_line_1 }}<br>
                @if($shippingAddress->address_line_2){{ $shippingAddress->address_line_2 }}<br>@endif
                {{ $shippingAddress->city }}, {{ $shippingAddress->state }} - {{ $shippingAddress->postal_code }}
                @endif
            </div>
        </div>
    </div>

    <div class="section-title">Order Details</div>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $orderItem->product->name ?? 'Product not found' }}</strong>
                    @if($orderItem->product && $orderItem->product->description)
                    <br><small style="color: #666;">{{ \Illuminate\Support\Str::limit($orderItem->product->description, 100) }}</small>
                    @endif
                </td>
                <td>
                    @if($orderItem->variantCombination)
                        {{ $orderItem->variantCombination->variant_options ?? 'N/A' }}
                    @else
                        Standard
                    @endif
                </td>
                <td>{{ $orderItem->quantity }}</td>
                <td>₹{{ number_format($orderItem->unit_price, 2) }}</td>
                <td class="text-right">₹{{ number_format($orderItem->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; display: inline-block; min-width: 300px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Subtotal:</span>
                <span>₹{{ number_format($orderItem->total_amount, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Tax (included):</span>
                <span>₹0.00</span>
            </div>
            <hr style="margin: 10px 0;">
            <div class="total-row" style="display: flex; justify-content: space-between;">
                <span>Total Amount:</span>
                <span>₹{{ number_format($orderItem->total_amount, 2) }}</span>
            </div>
        </div>
    </div>

    @if($orderItem->notes)
    <div style="margin-top: 30px;">
        <div class="section-title">Notes</div>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
            {{ $orderItem->notes }}
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Thank you for your business with BuyProtein!</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>For any queries, please contact our support team.</p>
        <small>Generated on {{ now()->format('F d, Y \a\t g:i A') }}</small>
    </div>
</body>
</html>
