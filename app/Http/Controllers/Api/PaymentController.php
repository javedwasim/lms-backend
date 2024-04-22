<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Course;
use App\Models\FlashCard;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payments;
use App\Models\Seminar;
use App\Models\TempOrder;
use App\Models\TempOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function purchasePlan(Request $request)
    {
        $request->validate([
            'package_for' => 'required|integer',
            'course_ids' => 'required',
            'package_ids' => 'required',
            'total_package_amount' => 'required|integer',
        ]);

        $user_id = auth()->user()->id;
        $package_for = $request->package_for;

        $total_amount = $request->total_package_amount;
        $billing_address = $request->billing_address ?? '';
        $post_code = $request->post_code ?? 0;
        $card_no = $request->card_no ?? '';
        $card_expiry = $request->card_expiry ?? '';


        $tempOrder = TempOrder::where(['user_id' => $user_id])->with('orderDetails')->first();

        if ($tempOrder) {
            $tempOrder->orderDetails()->delete();
            $tempOrder->delete();
        }

        $newOrder = TempOrder::create([
            'user_id' => $user_id,
            'package_for' => $package_for,
            'total_amount' => $total_amount,
            'billing_address' => $billing_address,
            'post_code' => $post_code,
            'card_no' => $card_no,
            'card_expiry' => $card_expiry,
        ]);

        if (isset($newOrder->id)) {
            Cart::where('user_id', $user_id)->delete();
        }

        $order_id = $newOrder->id;

        $course_id_arr = explode(',', $request->course_ids);
        $package_id_arr = explode(',', $request->package_ids);

        $total_amount = 0;
        $orderPackages = [];

        foreach ($course_id_arr as $key => $course_id) {
            $package_id = $package_id_arr[$key];
            $package = Package::where('id', $package_id)->first();

            $package_amount = (isset($package->id)) ? $package->price : 0;
            $total_amount += $package_amount;

            if ($package->packagetype == "onetime") {
                $package_for_month = (isset($package->expire_date)) ? $package->expire_date : '';

                $expiry_date = date('Y-m-d h:i:s', strtotime($package->expire_date));
            } else {
                $package_for_month = (isset($package->package_for_month)) ? $package->package_for_month : '';

                $curr_date = date('Y-m-d');

                $expiry_date = date('Y-m-d h:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
            }

            $orderPackages[] = [
                'order_id' => $order_id,
                'package_for' => $package_for,
                'particular_record_id' => $course_id,
                'package_id' => $package_id,
                'price' => $package_amount,
                'expiry_date' => $expiry_date,
            ];
        }

        TempOrderDetail::insert($orderPackages);

        return response()->json([
            'status' => true,
            'data' => [
                'order_id' => $order_id,
                'total_amount' => $total_amount,
            ],
            'message' => 'Package Purchased Successful',
        ]);
    }

    public function stripeCall(Request $request)
    {
        $request->validate([
            'order_id' => 'required|numeric',
            'amount' => 'required|numeric|min:1',
        ]);

        // documentation: https://stripe.com/docs/api/checkout/sessions/create
        try {
            $session = Session::create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'GBP',
                        'product_data' => [
                            'name' => 'Add payment to wallet',
                            'description' => 'MEDICMIND LMS',
                        ],
                        'unit_amount' => ceil($request->amount) * 100,
                    ],
                    'quantity' => 1,
                ]],
                "metadata" => ["order_id" => $request->order_id],
                "customer_email" => auth()->user()->email,
                'mode' => 'payment',
                'success_url' => env('APP_URL') . '/stripe/success?paymentId={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_URL') . '/stripe/cancel?paymentId={CHECKOUT_SESSION_ID}',
            ]);

            if (isset($session->url)) {
                Payments::create([
                    'order_id' => $request->order_id,
                    'user_id' => auth()->user()->id,
                    'payment_id' => $session->id,
                    'payment_method' => 'stripe',
                    'amount' => $request->amount,
                    'status' => 'pending'
                ]);

                return response()->json(['statusCode' => 200, 'message' => 'Payment link generate successful.', 'payment_url' => $session->url], 200);
            }
            return response()->json(['statusCode' => 422, 'message' => 'Payment link not generate.'], 200);
        } catch (\Exception $e) {
            return response()->json(['statusCode' => 422, 'message' => 'Payment link not generate.'], 200);
        }
    }

    public function transactions(Request $request)
    {
        $type = $request->type;
        $fromDate = $request->fromDate;
        $toDate = $request->toDate;
        $userId = auth()->user()->id;

        $type = '';

        switch ($request->type) {
            case "course":
                $type = 1;
                break;
            case "seminar":
                $type = 2;
                break;
            case "flashcard":
                $type = 3;
                break;
            case "book":
                $type = 4;
                break;
            default:
                '';
        };

        $get_data = Order::query()->where("user_id", $userId);


        if ($type) {
            $get_data = $get_data->where("package_for", $type);
        }
        if (!empty($fromDate)) {
            $get_data = $get_data->whereDate("created_at", ">=", $fromDate);
        }
        if (!empty($toDate)) {
            $get_data = $get_data->whereDate("created_at", "<=", $toDate);
        }

        $get_data = $get_data->orderBy('id', 'desc')->get();

        $result = array();

        foreach ($get_data as $key => $val) {
            $result[$key]['type'] = $val->package_for;

            $allpackage = array();
            $allpackage1 = array();
            $expireDate = array();
            $packageIds = array();

            foreach ($val->orderDetails as $val1) {
                $allpackage[] = $val1->package->package_title;
                $packageIds[] = $val1->particular_record_id;

                $course_id = ($val1->package_for == '1') ? $val1->particular_record_id : '';
                $seminar_id = ($val1->package_for == '2') ? $val1->particular_record_id : '';
                $flashcard_id = ($val1->package_for == '3') ? $val1->particular_record_id : '';
                $book_id = ($val1->package_for == '4') ? $val1->particular_record_id : '';

                if (!empty($course_id)) {
                    $getCat = Course::where('id', $course_id)->first(['id', 'course_name']);
                    $allpackage1[] = $getCat->course_name;
                }
                if (!empty($book_id)) {
                    $getCat = Book::where('id', $book_id)->first(['id', 'title']);
                    $allpackage1[] = $getCat->title;
                }
                if (!empty($flashcard_id)) {
                    $getCat = FlashCard::where('id', $flashcard_id)->first(['id', 'title']);
                    $allpackage1[] = $getCat->title;
                }
                if (!empty($seminar_id)) {
                    $getCat = Seminar::where('id', $seminar_id)->first(['id', 'title']);
                    $allpackage1[] = $getCat->title;
                }

                $expireDate[] = $val1->expiry_date;
            }


            $course = implode(",", $allpackage1);
            $title = implode(",", $allpackage);
            $expireDate = implode(",", $expireDate);
            $packageId = implode(",", $packageIds);

            $result[$key]['packageId'] = $packageId;
            $result[$key]['packageName'] = $title;
            $result[$key]['courseName'] = $course;
            $result[$key]['enrollDate'] = $val->created_at;
            $result[$key]['expireDate'] = $expireDate;
            $result[$key]['status'] = $val->payment_status == 1 ? "Success" : "failed";
        }
        return response()->json(['statusCode' => 200, 'message' => 'transaction', 'data' => $result], 200);
    }

    public function updateTransaction(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'transaction_id' => 'required',
            'payment_status' => 'required|integer',
        ]);

        $order_id = $request->order_id;
        $transaction_id = $request->transaction_id;
        $payment_status = $request->payment_status;

        $req_data = [];

        $ord_Count = Order::where(['id' => $order_id])->count();
        if ($ord_Count > 0) {
            $newUser = Order::where(['id' => $order_id])->update([
                'transaction_id' => $transaction_id,
                'payment_status' => $payment_status,
            ]);
            if ($payment_status == '2')
                $req_message = "Transaction failed";
            else if ($payment_status == '1')
                $req_message = "Transaction successfully";
            else
                $req_message = "Transaction is pending";
        }
        return $this->json_view(true, $req_data, $req_message);
    }
}
