<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payments;
use App\Models\TempOrder;
use App\Models\TempOrderDetail;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function stripeSuccess(Request $request)
    {
        $payment_id = $request->paymentId;

        $payments = Payments::where('payment_id', $payment_id)->first();
        $user_id = $payments->user_id ?? '';

        if (!$payments) {
            return redirect()->route('payment.status', ['' => '0'])->withError('Payment failed, Please try again.');
        }

        $result = Session::retrieve(
            $payment_id,
            []
        );

        $order_id = $payments->order_id ?? '';
        if ($result->payment_status == 'paid' && $result->status == 'complete') {
            $transaction_fees = 0;

            $order_id = $result->metadata['order_id'] ?? '';
            $response = json_encode($result);

            $getOrd = TempOrder::where(['id' => $order_id])->first();

            if (isset($getOrd->id)) {
                $newOrder = Order::create([
                    'user_id' => $getOrd->user_id,
                    'package_for' => $getOrd->package_for,
                    'total_amount' => $getOrd->total_amount,
                    'billing_address' => $getOrd->billing_address,
                    'post_code' => $getOrd->post_code,
                    'card_no' => $getOrd->card_no,
                    'card_expiry' => $getOrd->card_expiry,
                    'payment_status' => 1,
                ]);
                $reOrder_id = $newOrder->id;
                Payments::where('id', $payments->id)->update([
                    'status' => 'success',
                    'response' => $response,
                    'transaction_fees' => $transaction_fees,
                    'transaction_id' => @$result->payment_intent,
                    'order_id' => $reOrder_id
                ]);

                $getOrdDetail = TempOrderDetail::where(['order_id' => $order_id])->get();
                foreach ($getOrdDetail as $orddData) {

                    OrderDetail::create([
                        'order_id' => $reOrder_id,
                        'package_for' => $orddData->package_for,
                        'particular_record_id' => $orddData->particular_record_id,
                        'package_id' => $orddData->package_id,
                        'price' => $orddData->price,
                        'expiry_date' => $orddData->expiry_date,
                    ]);
                    TempOrderDetail::where(['id' => $orddData->id])->delete();
                }
                TempOrder::where(['id' => $order_id])->delete();
            }

            return redirect()->route('payment.status', ['success' => '1'])->withSuccess('Payment success, Thanks for subscription now are you upload your video for donation.');
        }
        Order::where('id', $order_id)->delete();
        OrderDetail::where('order_id', $order_id)->delete();
        Payments::where('order_id', $order_id)->delete();
        Cart::where('user_id', $user_id)->delete();

        return redirect()->route('payment.status', ['success' => '0'])->withError('Payment failed, Please try again.');
    }

    public function stripeCancel(Request $request)
    {
        $payment_id = $request->paymentId;

        $result = Session::retrieve(
            $payment_id,
            []
        );
        $order_id = $result->metadata->order_id ?? '';

        if ($order_id != "") {
            Order::where('id', $order_id)->delete();
            OrderDetail::where('order_id', $order_id)->delete();
            Payments::where('order_id', $order_id)->delete();
        }

        return redirect()->route('payment.status', ['success' => '0'])->withError('Payment failed, Please try again.');
    }

    public function showPaymentStatus(Request $request)
    {
        if ($request->success == '1') {
            return view('payment.success');
        }
        return view('payment.error');
    }
}
