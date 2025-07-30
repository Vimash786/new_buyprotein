<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        /* General Adjustments for A4 */
        @page {
            size: A4;
            margin: 20mm;
        }

        body,
        td,
        th {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', Arial, sans-serif;
        }

        @media print {

            body,
            html {
                margin: 0;
                padding: 0;
                width: 210mm;
                height: 297mm;
                font-size: 10px;
                -webkit-print-color-adjust: exact !important;
            }

            .invoice {
                padding: 0;
                margin: 0 auto;
                page-break-after: always;
            }

            .no-break {
                page-break-inside: avoid;
            }

            .address-section {
                display: flex;
                justify-content: space-between;
                gap: 30px;
                page-break-inside: avoid;
            }

            .address-row {
                display: flex !important;
                justify-content: space-between;
                gap: 20px;
                font-size: 12px;
                page-break-inside: avoid;
            }

            .footer {
                position: fixed;
                bottom: 10mm;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="invoice">
        <!-- Header -->
        <div class="header no-break">
            <div style="float: left; width: 55%;">
                <img src="{{ public_path('buy-protein.jpg') }}" width="200" alt="">
                <p>BuyProtein Private Limited<br>
                    123 Business District, Tech Park<br>
                    Bangalore, Karnataka 560001<br>
                    India</p>
            </div>
            <div style="float: right; width: 45%; text-align: right;">
                <h3 style="margin: 0;">INVOICE</h3>
                <p><strong>Invoice #</strong>: {{ $order->order_number }}<br>
                    <strong>Invoice Date</strong>: {{ now()->format('F d, Y') }}<br>
                    <strong>Due Date</strong>: {{ now()->addDays(30)->format('F d, Y') }}
                </p>
            </div>
            <div style="clear: both;"></div>
            <hr>
        </div>

        <!-- Addresses -->
        <div class="header address-row no-break">
            <div style="float: left; width: 32%;">
                <strong>Sold By :</strong><br>
                {{ $seller->company_name ?? 'Seller Name' }}<br>
                {{ $seller->business_address ?? '' }}<br>
                @if (isset($seller->city) || isset($seller->state) || isset($seller->pincode))
                    {{ $seller->city }}, {{ $seller->state }} - {{ $seller->pincode }}<br>
                @endif
                {{ $seller->country ?? 'India' }}
            </div>
            <div style="float: right; width: 32%; text-align: right;">
                <strong>Ship To :</strong><br>
                {{ $customer->name }}<br>
                {{ $shippingAddress->address_line_1 }}<br>
                @if ($shippingAddress->address_line_2)
                    {{ $shippingAddress->address_line_2 }}<br>
                @endif
                {{ $shippingAddress->city }}, {{ $shippingAddress->state }}<br>
                {{ $shippingAddress->postal_code }}, {{ $shippingAddress->country ?? 'India' }}
            </div>
            <div style="float: right; width: 32%; text-align: right;">
                <strong>Bill To :</strong><br>
                {{ $customer->name }}<br>
                {{ $billingDetail->billing_address }}<br>
                @if ($billingDetail->address_line_2)
                    {{ $billingDetail->address_line_2 }}<br>
                @endif
                {{ $billingDetail->billing_city }}, {{ $billingDetail->billing_state }}<br>
                {{ $billingDetail->billing_postal_code }}, {{ $billingDetail->billing_country ?? 'India' }}
            </div>
            <div style="clear: both;"></div>
            <hr>
        </div>

        <!-- Items -->
        <div class="items-section no-break">
            <table width="100%" border="1" cellspacing="0" cellpadding="5">
                <thead style="background: #2dc2fa; color: white;">
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Tax</th>
                        <th>Tax Amount</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->orderSellerProducts as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>&#8377;{{ number_format($item->unit_price, 2) }}</td>
                            <td>GST (18%)</td>
                            <td>&#8377;{{ number_format($item->total_amount * 0.18, 2) }}</td>
                            <td>&#8377;{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section no-break" style="text-align: right; margin-top: 20px;">
            <table style="width: 300px; float: right;">
                <tr>
                    <td>Subtotal:</td>
                    <td>₹{{ number_format($billingDetail->subtotal, 2) }}</td>
                </tr>
                {{-- <tr>
                    <td>Shipping:</td>
                    <td>₹{{ number_format($billingDetail->shipping_charge ?? 0, 2) }}</td>
                </tr> --}}
                {{-- <tr>
                    <td>Tax:</td>
                    <td>₹{{ number_format($billingDetail->tax_amount ?? 0, 2) }}</td>
                </tr> --}}
                <tr>
                    <td>Discount:</td>
                    <td>₹{{ number_format($billingDetail->discount_amount ?? 0, 2) }}</td>
                </tr>
                <tr style="font-weight: bold; background: #2dc2fa; color: white;">
                    <td>Total:</td>
                    <td>₹{{ number_format($billingDetail->total_amount, 2) }}</td>
                </tr>
            </table>
            <div style="clear: both;"></div>
        </div>

        <!-- Tax Info -->
        <div class="tax-info no-break"
            style="margin-top: 30px; background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;">
            <strong>HSN/SAC Code:</strong> 21069090 (Nutritional Supplements)<br>
            <strong>GST:</strong> 18% (9% CGST + 9% SGST or 18% IGST)
        </div>

        <!-- Footer -->
        <div class="footer">
            <hr>
            <p style="text-align: center; font-size: 10px;">
                Thank you for shopping at BuyProtein. Visit us at www.buyprotein.com
            </p>
        </div>
    </div>

</body>

</html>
