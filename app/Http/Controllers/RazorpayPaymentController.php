<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;

class RazorpayPaymentController extends Controller
{
    public function payment(Request $request)
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        $payment = $api->payment->fetch($request->razorpay_payment_id);

        if ($payment->capture(['amount' => $payment['amount']])) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
