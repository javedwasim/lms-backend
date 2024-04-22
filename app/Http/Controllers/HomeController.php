<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Session;
use DB;
use Str;
use Validator;

use App\Models\User;
use App\Helpers\ApiHelper;
use App\Models\PhoneVerification;
use App\Models\TempUser;
use App\Models\Payments;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Cart;
use App\Models\TempOrder;
use App\Models\TempOrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    private $_api_context;

    public function __construct()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function stripeSuccess(Request $request)
    {
        $payment_id = $request->paymentId;

        $payments = Payments::where('payment_id', $payment_id)->first();
        $user_id = $payments->user_id ?? '';

        if (!$payments) {
            Payments::where('payment_id', $payment_id)->update(['status' => 'failed']);
            return redirect()->route('payment.status', ['
                ' => '0'])->withError('Payment failed, Please try again.');
        }

        $result = \Stripe\Checkout\Session::retrieve(
            $payment_id,
            []
        );


        if ($result->payment_status == 'paid' && $result->status == 'complete') {
            $description = 'Add payment to wallet';
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

                    $order_detail_id = $orddData->id;
                    OrderDetail::create([
                        'order_id' => $reOrder_id,
                        'package_for' => $orddData->package_for,
                        'particular_record_id' => $orddData->particular_record_id,
                        'package_id' => $orddData->package_id,
                        'price' => $orddData->price,
                        'expiry_date' => $orddData->expiry_date,
                    ]);
                    TempOrderDetail::where(['id' => $order_detail_id])->delete();
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

        $result = \Stripe\Checkout\Session::retrieve(
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

    public function json_view($req_status = false, $req_data = "", $req_message = "")
    {
        $this->status = $req_status;
        $this->code = ($req_status == false) ? "404" : "";

        $this->data = $req_data;
        $this->message = $req_message;
        return response()->json($this);
    }
    public function front_login_check()
    {
        Log::info('front_login_check');
        Auth::logout();
        return redirect('/');
    }

    public function index()
    {
        return view('auth.login');
    }

    public function check_user_is_logged_in(Request $request)
    {

        $req_data = [];

        if (Auth::check()) {
            $message = 'User is Logged in. !';
            $req_data['code'] = '101';
            return $this->json_view(false, $req_data, $message);
        } else {
            $message = 'Please login for Add Property. !';
            $req_data['code'] = '411';
            return $this->json_view(false, $req_data, $message);
        }
    }
    public function uploadckeditorimage(Request $request)
    {
        $profile_photo_img_path = $request->upload->store('profile_photo_path', 's3');
        $data = [

            "uploaded" => 1,
            "fileName" => $profile_photo_img_path,
            "url" => Storage::disk('s3')->url($profile_photo_img_path)

        ];

        return response()->json($data);
    }
}
