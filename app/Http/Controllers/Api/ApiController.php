<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Validator;
use Illuminate\Auth\Events\Registered;
use Session;
use Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ApiHelper;
use DB;
use Carbon\Carbon;
use DateTime;
use App\Models\Category;
use App\Models\VideoCommentLike;
use App\Models\SubCategory;
use App\Models\QuestionTempFilter;
use App\Models\Course;
use App\Models\Tutorial;
use App\Models\QuestionAnswer;
use App\Models\WatchedTutorial;
use App\Models\TempTest;
use App\Models\AttemptQuestion;
use App\Models\VideoComment;
use App\Models\Package;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\TempOrder;
use App\Models\TempOrderDetail;
use App\Models\CourseExamDate;
use App\Models\TutorialNote;
use App\Models\Tips;
use App\Models\Bookmark;
use App\Models\PersonalSupport;
use App\Models\Cart;
use App\Models\ProgressSetting;
use App\Models\Payments;
use App\Models\QueOption;
use App\Models\TempBeforeFinishTest;
use App\Models\BlockPopup;
use App\Models\QueOptionAnswerType;
use App\Models\Rating;
use App\Models\Comment;
use App\Models\LikeUnlike;
use App\Models\TempSrQuestion;
use App\Models\AssignTutorial;
use App\Models\AssignQuestion;
use App\Models\Reportissue;
use App\Models\PackageMultiple;
use App\Models\Tutoring;
use App\Models\CmsPage;
use App\Models\Seminar;
use App\Models\Book;
use App\Models\CategoryUcatScore;
use App\Models\CommentLike;
use App\Models\FlashCard;
use App\Models\SeminarAddon;
use App\Models\SeminarTestimonial;
use App\Models\SeminarTutor;
use App\Models\TempPerformance;
use App\Models\Testimonial;
use App\Models\TutorialFile;
use App\Models\TipsDisplay;
use App\Models\TutorialOrder;
use App\Models\AttemptMocktestQuestion;
use App\Models\MocktestCategory;
use App\Models\AssingQuestionMocktest;
use Exception;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    private $offset;
    private $limit;
    private $page;

    private $_api_context;

    public function __construct()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    }


    public function stripe_call(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'amount' => 'required|numeric|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $error = (!empty($validator->errors())) ? $validator->errors()->first() : '';
            return response()->json(['statusCode' => 422, 'message' => $error], 200);
        }
        // documentation: https://stripe.com/docs/api/checkout/sessions/create

        $auth_email = Auth::user()->email;

        try {
            $order_id = $request->order_id;

            $session = \Stripe\Checkout\Session::create([
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
                "metadata" => ["order_id" => $order_id],
                "customer_email" => $auth_email,
                'mode' => 'payment',
                'success_url' => route('stripe.success') . '?paymentId={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel') . '?paymentId={CHECKOUT_SESSION_ID}',
            ]);

            if (isset($session->url)) {
                $user_id = Auth::id();

                Payments::create([
                    'order_id' => $order_id,
                    'user_id' => auth()->user()->id,
                    'payment_id' => $session->id,
                    'payment_method' => 'stripe',
                    'amount' => $request->amount,
                    'status' => 'pending'
                ]);

                $stripe_call_url = $session->url;

                return response()->json(['statusCode' => 200, 'message' => 'Payment link generate successful.', 'payment_url' => $stripe_call_url], 200);
            }
            return response()->json(['statusCode' => 422, 'message' => 'Payment link not generate.'], 200);
        } catch (Error $e) {
            return response()->json(['statusCode' => 422, 'message' => 'Payment link not generate.'], 200);
        }
    }

    public function purchase_plan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_for' => 'required|integer',
            'course_ids' => 'required',
            'package_ids' => 'required',
            'total_package_amount' => 'required|integer',
        ]);

        $req_data = [];

        if ($validator->fails()) {
            $error = '';

            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }

            $message = $error;

            return $this->json_view(false, $req_data, $message);
        } else {
            $user_id = Auth::id();
            $order_id = '';
            $total_amount = $request->total_package_amount;
            $package_for = $request->package_for;
            $billing_address = $request->billing_address ?? '';
            $post_code = $request->post_code ?? '';
            $card_no = $request->card_no ?? '';
            $card_expiry = $request->card_expiry ?? '';

            $getOrder = TempOrder::where(['user_id' => $user_id])->get();

            // delete temporary order and order details
            if ($getOrder->count() > 0) {
                foreach ($getOrder as $orderValue) {
                    $order_detail_id = $orderValue->id;

                    TempOrderDetail::where(['id' => $order_detail_id])->delete();
                }

                TempOrder::where(['user_id' => $user_id])->delete();
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
                Cart::where(['user_id' => $user_id])->delete();
            }

            $order_id = $newOrder->id;

            $course_id_arr = explode(',', $request->course_ids);
            $package_id_arr = explode(',', $request->package_ids);

            $total_amount = '0';
            $order_detail_arr = [];

            foreach ($course_id_arr as $key => $course_id) {
                $package_id = $package_id_arr[$key];
                $getPackageDetail = Package::where('id', $package_id)->first();

                $package_amount = (isset($getPackageDetail->id)) ? $getPackageDetail->price : '0';
                $total_amount = $total_amount + $package_amount;

                if ($getPackageDetail->packagetype == "onetime") {
                    $package_for_month = (isset($getPackageDetail->expire_date)) ? $getPackageDetail->expire_date : '';
                    $expiry_date = date('Y-m-d h:i:s', strtotime($getPackageDetail->expire_date));
                } else {
                    $package_for_month = (isset($getPackageDetail->package_for_month)) ? $getPackageDetail->package_for_month : '';
                    $curr_date = date('Y-m-d');
                    $expiry_date = date('Y-m-d h:i:s', strtotime('+' . $package_for_month . ' month', strtotime($curr_date)));
                }

                TempOrderDetail::create([
                    'order_id' => $order_id,
                    'package_for' => $package_for,
                    'particular_record_id' => $course_id,
                    'package_id' => $package_id,
                    'price' => $package_amount,
                    'expiry_date' => $expiry_date,
                ]);
            }
        }

        $req_data['order_id'] = $order_id;
        $req_message = "Package Purchased Successful";

        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_rating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'rating' => 'required',
        ]);

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $is_exist = Rating::where(['user_id' => $user_id, 'question_id' => $request->question_id])->count();

            if ($is_exist == 0) {
                $newUser = Rating::create([
                    'user_id' => $user_id,
                    'question_id' => $request->question_id,
                    'rating' => $request->rating
                ]);
                $req_message = "Rating Added Successful";
            } else {
                $rev_arr = ['rating' => $request->rating];

                Rating::where(['user_id' => $user_id, 'question_id' => $request->question_id])->update($rev_arr);

                $req_message = "Rating Update Successful";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'user_name' => 'required',
            'comment' => 'required',
        ]);

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {

            $status = (int) $request->is_name_display;

            $row = new Comment;
            $row->user_id = $user_id;
            $row->user_name = $request->user_name;
            $row->question_id = $request->question_id;
            $row->comment = $request->comment;
            $row->is_name_display = $status;
            $row->save();

            $req_message = "Comment Added Successful";
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function like_unlike(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
            'like_unlike_status' => 'required',
        ]);

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $question_id = $request->question_id;

            if ($request->like_unlike_status == '1') {
                $req_message = "Liked Successful";
            } else if ($request->like_unlike_status == '2') {
                $req_message = "Disliked Successful";
            }

            $check_record = LikeUnlike::where(['user_id' => $user_id, 'question_id' => $request->question_id, 'like_unlike_status' => $request->like_unlike_status])->first(['id', 'question_id', 'like_unlike_status']);

            if (isset($check_record->id)) {
                LikeUnlike::where('id', $check_record->id)->delete();
                $req_message = ($request->like_unlike_status == '1') ? "Like removed" : "Dislike removed";
            } else {
                LikeUnlike::create([
                    'user_id' => $user_id,
                    'question_id' => $question_id,
                    'like_unlike_status' => $request->like_unlike_status
                ]);
            }
            $req_data['likecount'] = LikeUnlike::where('question_id', $question_id)->where("like_unlike_status", 1)->count();
            $req_data['unlikecount'] = LikeUnlike::where('question_id', $question_id)->where("like_unlike_status", 2)->count();
        }
        return $this->json_view(true, $req_data, $req_message);
    }
    public function update_transaction(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'transaction_id' => 'required',
            'payment_status' => 'required|integer',
        ]);

        $user_id = Auth::id();
        $order_id = $request->order_id;
        $transaction_id = $request->transaction_id;
        $payment_status = $request->payment_status;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
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
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function get_package_list(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'package_for' => 'required|integer',
            'course_id' => 'required|integer',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $getPackage =  Package::with('packagemultiples')->orderBy('id', 'desc')->where([
                'status' => 1,
                'package_for' => $request->package_for,
                'perticular_record_id' => $request->course_id,
            ])->get();

            $packageArr = ($getPackage->count() > 0) ? $getPackage->toArray() : [];
            foreach ($packageArr as $key => $val) {
                $packageType = '';
                if ($val['packagetype'] == "onetime") {
                    $packageType = "One Time Specific Date";
                } else if ($val['packagetype'] == "subscription_onetime") {
                    $packageType = "One Time Specific Month";
                } else if ($val['packagetype'] == "subscription") {
                    $packageType = "Subscription";
                } else if ($val['packagetype'] == "free") {
                    $packageType = "Free";
                }
                $packageArr[$key]['packagetype'] = $packageType;
            }

            $req_data['package_list'] = $packageArr;

            if (count($packageArr)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }

    public function get_progress_list(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $progressArr = [];

            $user_id = Auth::id();

            $course_id = $request->course_id;

            $courseDetail = Course::find($course_id);
            // for only tutorial type course
            if ($courseDetail->is_tutorial == 1 && $courseDetail->is_question == 0) {
                $progressArr = $this->tutorailPrograssLogic($request);
            } else {
                $progressArr = $this->questionPrograssLogic($request);
            }
            $calenderColor = ProgressSetting::all();


            $req_data['progress_record'] = $progressArr;
            $req_data['calenderColor'] = $calenderColor;
            $userAllOrder = Order::where("user_id", $user_id)->pluck("id");
            $orderType = OrderDetail::where(['particular_record_id' => $course_id, 'package_for' => '1'])->whereIn("order_id", $userAllOrder)->first();
            if (!empty($orderType)) {
                $courseType = "paid";
            } else {
                $courseType = "unpaid";
            }
            $req_data['courseType'] = $courseType;

            if (count($progressArr)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }
    public function questionPrograssLogic($request)
    {
        $user_id = Auth::id();
        $course_id = $request->course_id;
        $start_date = strtotime($request->start_date);
        $end_date = strtotime($request->end_date);
        for ($i = $start_date; $i <= $end_date; $i = $i + 86400) {
            $our_date = date('Y-m-d', $i); // 2010-05-01, 2010-05-02, etc

            $startDate = $our_date . ' 00:00:01';
            $endDate = $our_date . ' 23:58:59';

            $totalQueAttenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->whereDate('created_at',  $our_date)->count();

            $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
            $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';
            $testDateStatus = false;
            if ($course_exam_date != "" && $course_exam_date == $our_date) {
                $color = "#C32F5B";
                $testDateStatus = true;
            } else if ($totalQueAttenpt == 0) {

                $color = '#C0C0C0';
            } else if ($totalQueAttenpt >= 1 && $totalQueAttenpt <= 50) {
                $getColor = ProgressSetting::where('id', '3')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 50 && $totalQueAttenpt <= 100) {
                $getColor = ProgressSetting::where('id', '1')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 100) {
                $getColor = ProgressSetting::where('id', '2')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else {
                $color = '#C0C0C0';
            }

            $progressArr[] = [
                "calendar_date" => $our_date,
                "total_question_attempt" => $totalQueAttenpt,
                "color" => $color,
                "testDate" => $testDateStatus,

            ];
        }
        return $progressArr;
    }
    public function tutorailPrograssLogic($request)
    {
        $user_id = Auth::id();
        $course_id = $request->course_id;
        $start_date = strtotime($request->start_date);
        $end_date = strtotime($request->end_date);
        for ($i = $start_date; $i <= $end_date; $i = $i + 86400) {
            $our_date = date('Y-m-d', $i); // 2010-05-01, 2010-05-02, etc

            $startDate = $our_date . ' 00:00:01';
            $endDate = $our_date . ' 23:58:59';

            $totalQueAttenpt = WatchedTutorial::where(['user_id' => $user_id, 'course_id' => $course_id])->whereDate('created_at',  $our_date)->count();

            $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
            $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';
            $testDateStatus = false;
            if ($course_exam_date != "" && $course_exam_date == $our_date) {
                $color = "#C32F5B";
                // $testDateStatus = false;
            } else if ($totalQueAttenpt == 0) {

                $color = '#C0C0C0';
            } else if ($totalQueAttenpt >= 1 && $totalQueAttenpt <= 50) {
                $getColor = ProgressSetting::where('id', '3')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 50 && $totalQueAttenpt <= 100) {
                $getColor = ProgressSetting::where('id', '1')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 100) {
                $getColor = ProgressSetting::where('id', '2')->first();
                $color = $getColor->color;
                $testDateStatus = true;
            } else {
                $color = '#C0C0C0';
            }


            $progressArr[] = [
                "calendar_date" => $our_date,
                "total_question_attempt" => $totalQueAttenpt,
                "color" => $color,
                "testDate" => $testDateStatus,

            ];
        }
        return $progressArr;
    }

    public function delete_popup_or_banner(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|integer',
            'type' => 'required|integer',
        ]);

        $user_id = Auth::id();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $course_id = $request->course_id;
            $record_type = $request->type;

            $insArr = [
                'user_id' => $user_id,
                'course_id' => $course_id,
                'type' => $record_type,
            ];

            $recCount = BlockPopup::where($insArr)->count();
            if ($recCount == 0) {
                $newUser = BlockPopup::create($insArr);
                $req_message = "Removed Succefully";
            } else {
                $req_message = "No record found";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function delete_cart_item(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|integer',
        ]);

        $user_id = Auth::id();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $cart_id = $request->cart_id;
            $recCount = Cart::where('id', $cart_id)->count();
            if ($recCount > 0) {
                $newUser = Cart::where('id', $cart_id)->delete();
                $req_message = "Record deleted successful";
            } else {
                $req_message = "No record found";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function get_category_list(Request $request)
    {

        $response = $this->categorylistlogicfilter($request);
        // $response = $this->categorylistlogic($request);
        return $this->json_view($response['status'], $response['data'], $response['message']);
    }

    public function get_category_list_tutorial(Request $request)
    {
        $response = $this->categorylistloginTutorial($request);
        return $this->json_view($response['status'], $response['data'], $response['message']);
    }

    public function get_category_list_new(Request $request)
    {
        if (!empty($request->subcategoryIds)) {
            $response = $this->categorylistlogicfilter($request);
        } else {
            $response = $this->categorylistlogic($request);
        }

        return $this->json_view($response['status'], $response['data'], $response['message']);
    }

    public function categorylistlogic(Request $request)
    {

        $data = $request->all();

        $req_data = [];

        $record_type = $request->record_type; //1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3
        $tutorial_id = $request->tutorial_id; // 1
        $category_ids = $request->category_ids; // 1
        $subcategoryIds = $request->subcategoryIds; // 1
        $subcategoryIds = explode(",", $subcategoryIds); // 1
        $questionCount = $request->questionCount; // 1
        $totalQuestion = $request->total_question; // 1

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];

        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();
        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        $buy_question_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_question_id')->join(',');

        $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',', $buy_question_ids) : [];
        $buyQuesIdArr = array_unique($buyQuesIdArr);




        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);
        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }
        if (!empty($questionCount) && $questionCount > 0) {
            $allCategory = explode(",", $category_ids);
            $parcentToDeduct = ($questionCount / $totalQuestion) * 100;

            foreach ($allCategory as $categoryId) {

                $totalQuestionCount = QuestionAnswer::where(['status' => 1, 'category_id' => $categoryId])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();
                $selectedQuestion = ($totalQuestionCount * $parcentToDeduct) / 100;
                $selectedQuestion = ceil($selectedQuestion);

                $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $categoryId)->first();
                $data = array("user_id" => $user_id, "category_id" => $categoryId, "question_count" => $selectedQuestion);
                if (!empty($checkCategory)) {
                    QuestionTempFilter::where("id", $checkCategory->id)->update($data);
                } else {
                    QuestionTempFilter::insert($data);
                }
            }
        } else {
            QuestionTempFilter::where("user_id", $user_id)->delete();
        }





        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $tot_sub_cat_attempt = '';
            $tot_sub_cat_correct = '';
            $tot_sub_cat_incorrect = '';
            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();
            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();
            foreach ($getSubCat as $subCatDt) {
                $sub_filter_questions_id = [];

                if ($filter_type == '1' || $filter_type == '') { // New questions

                    $sub_attenptQuess = AttemptQuestion::select('question_id')->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                    if ($is_plan_exist == 1) {
                        $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                    }

                    $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else { // for free trial
                        // $sub_queQuery->where('test_mode',1);

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }
                    if (!empty($checkCategory)) {
                        $sub_queQuery = $sub_queQuery->limit($checkCategory->question_count);
                    }
                    $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();

                    $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                } else if ($filter_type == '2') { // new and prev incorrect

                    $sub_attenptQuess = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)');

                    if ($is_plan_exist == 1) {
                        $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                    }

                    $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();
                    $tot_sub_cat_attempt = count($sub_attenptQueArr);

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else {
                        // $sub_queQuery->where('test_mode',1);

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $sub_attenptQueArr)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }
                    if (!empty($checkCategory)) {
                        $sub_queQuery = $sub_queQuery->limit($checkCategory->question_count);
                    }
                    $sub_questions_id_Arr1 = $sub_queQuery->pluck('id')->toArray();
                    $tot_sub_cat_que_dt = count($sub_questions_id_Arr1);

                    $queQuery2 = AttemptQuestion::whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id])->where('is_correct', '0');

                    if ($is_plan_exist == 1) {
                        $queQuery2->whereIn('question_id', $buyQuesIdArr);
                    }

                    $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                    $tot_sub_cat_correct = count($questions_id_Arr2);

                    $sub_filter_questions_id = array_merge($sub_questions_id_Arr1, $questions_id_Arr2);
                    // return $sub_filter_questions_id;
                    // dd($sub_filter_questions_id);

                    if (!empty($filter_questions_id)) {
                        $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
                    } else {
                        $filter_questions_id = $sub_filter_questions_id;
                    }
                } else if ($filter_type == '3') { // All question

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else { // for free trial
                        // $sub_queQuery->where('test_mode',1); 

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }
                    if (!empty($checkCategory)) {
                        $sub_queQuery = $sub_queQuery->limit($checkCategory->question_count);
                    }

                    $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();

                    $tot_sub_cat_que_dt = count($sub_filter_questions_id);

                    $tot_sub_cat_attempt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->whereIn('question_id', $sub_filter_questions_id)->count();

                    $tot_sub_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->whereIn('question_id', $sub_filter_questions_id)->count();

                    $tot_sub_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->whereIn('question_id', $sub_filter_questions_id)->count();
                }
                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                }


                if ($filter_type == '1' || $filter_type == '2') {
                    $tot_sub_cat_attempt = 0;
                }



                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id);
                if (!empty($checkCategory)) {
                    $getAllQueCount = $getAllQueCount->limit($checkCategory->question_count);
                }
                $getAllQueCount = $getAllQueCount->count();

                // print_r([$getAllQueCount,'cour'=>$course_id,'category_id'=>$subCatDt->category_id,'su'=>$subCatDt->id]);die;
                $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->count();

                $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->count();


                $total_all_question_list = $tot_sub_cat_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;


                $at = ($total_all_correct + $total_cat_incorrect);
                $c = $total_all_correct;
                $i = $total_cat_incorrect;


                if ($total_all_question_list == 0) {
                    $at = 0;
                    $c = 0;
                    $i = 0;
                }


                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }


                if ($total_all_question_list == 0) {
                    $total_correct_percentage = 0;
                    $total_incorrect_percentage = 0;
                    $total_white_percentage = 0;
                }

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $total_all_question_list,
                    'total_attenpt' => $at,
                    'total_correct' => $c,
                    'total_incorrect' => $i,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_filter_questions_id' => (count($sub_filter_questions_id) > 0) ? implode(',', $sub_filter_questions_id) : "",
                ];
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];

            if ($filter_type == '1' || $filter_type == '') { // for new question

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                /*  if(isset($request->tutorial_id)){
                                $attenptQue->where('tutorial_id',$tutorial_id);
                            } */
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();


                $queQueryT = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                if (!empty($attenptQueArr)) {
                    $queQueryT->whereNotIn('id', $attenptQueArr);
                }


                $queQuery = $queQueryT->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*  if(isset($request->tutorial_id)){   
                                $queQuery->where('tutorial_id',$tutorial_id);
                            }  */

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }

                $filter_questions_id = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '2') { // for new question & prev. incorrect

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

                /*    if(isset($request->tutorial_id)){
                                $attenptQue->where('tutorial_id',$tutorial_id);
                            } */
                // if($is_plan_exist==1){
                //     $attenptQue->whereIn('question_id',$buyQuesIdArr);
                // }
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

                $total_attenpt = count($attenptQueArr);

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

                // $queQuery->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');


                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*   if(isset($request->tutorial_id)){
                                $queQuery->where('tutorial_id',$tutorial_id);
                            }   */
                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }
                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                $queQuery_new_1Que = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery_newQue->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery_new_1Que->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery_new_1Que->whereIn('id', $testModeQueId);
                }


                $queQuery_new = $queQuery_newQue->count();
                $queQuery_new_1 = $queQuery_new_1Que->count();

                $tot_que_dt =  $queQuery_new_1;
                // $tot_que_dt = count($questions_id_Arr1);
                $tot_all_que = $tot_all_que + $queQuery_new;
                // return $tot_que_dt;

                $queQuery2 = AttemptQuestion::where(['user_id' => $user_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                if (isset($request->tutorial_id)) {
                    $queQuery2->where('tutorial_id', $tutorial_id);
                }
                if ($is_plan_exist == 1) {
                    $queQuery2->whereIn('question_id', $buyQuesIdArr);
                } else {
                    $queQuery2->whereIn('question_id', $testModeQueId);
                }

                $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                $tot_cat_correct = count($questions_id_Arr2);

                //dd($filter_questions_id);
                $filter_questions_id = array_merge($filter_questions_id, $questions_id_Arr1, $questions_id_Arr2);
            } else if ($filter_type == '3') { // for all types

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                /* if(isset($request->tutorial_id)){
                                $queQuery->where('tutorial_id',$tutorial_id);
                            }   */

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }
                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($questions_id_Arr1);

                $que_total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id]);

                /*  if(isset($request->tutorial_id)){
                                $que_total_attenpt->where('tutorial_id',$tutorial_id);
                            } */
                if ($is_plan_exist == 0) {
                    $que_total_attenpt->whereIn('question_id', $buyQuesIdArr);
                }
                $total_attenpt = $que_total_attenpt->get()->count();

                $que_tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '1');

                /*   if(isset($request->tutorial_id)){
                                $que_tot_cat_correct->where('tutorial_id',$tutorial_id);
                            } */
                if ($is_plan_exist == 0) {
                    $que_tot_cat_correct->whereIn('question_id', $buyQuesIdArr);
                }
                $tot_cat_correct = $que_tot_cat_correct->get()->count();

                $que_total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                /*  if(isset($request->tutorial_id)){
                                $que_total_cat_incorrect->where('tutorial_id',$tutorial_id);
                            } */
                if ($is_plan_exist == 0) {
                    $que_total_cat_incorrect->whereIn('question_id', $buyQuesIdArr);
                }
                $total_cat_incorrect = $que_total_cat_incorrect->get()->count();

                $filter_questions_id = $questions_id_Arr1;
            }

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);
            // return $filter_questions_id; 
            $total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->count();

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();
            ///print_r(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id]);die;
            $cr_attenpt = count($filter_questions_id);
            if ($filter_type == '1') {
                $cr_attenpt = $total_attenpt = 0;
            } else if ($filter_type == '2') {
                $cr_attenpt = 0; //$tot_cat_correct;  

                // $tot_que_dt = $tot_que_dt-$tot_cat_correct;

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '3') {
                $cr_attenpt = $tot_cat_correct + $total_cat_incorrect;
                // $tot_que_dt = $tot_que_dt-$cr_attenpt;
            }



            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }



            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }





            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect), //$total_attenpt,
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            if ($filter_type == '2') {
                $tot_all_que = $tot_all_que;
            } else {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }
            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') // for tutorial
            {
                // $filter_questions_id = [];

                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }
                /*else{
                            $totalTutorialsQue->where('test_mode',1);
                        }*/

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';


                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }
                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }




                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;
                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }

            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }
        /*if($request->record_type=='2')
                {*/

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";
        // } 
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d');

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', $weekStartDate)->whereDate('tip_date', '<=', $weekEndDate)->orderBy('tip_date', 'asc')->first();
        // $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();

        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];

        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->get()->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->get()->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }


            $req_data['total_questions'] = $total_all_question_list;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;

            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2')  // for tutorial
        {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }
    public function get_category_listold(Request $request)
    {

        $data = $request->all();

        $req_data = [];

        $record_type = $request->record_type; //1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3
        $tutorial_id = $request->tutorial_id; // 1

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];
        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();
        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        $buy_question_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_question_id')->join(',');

        $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',', $buy_question_ids) : [];
        $buyQuesIdArr = array_unique($buyQuesIdArr);




        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);
        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }




        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $tot_sub_cat_attempt = '';
            $tot_sub_cat_correct = '';
            $tot_sub_cat_incorrect = '';

            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();
            foreach ($getSubCat as $subCatDt) {
                $sub_filter_questions_id = [];

                if ($filter_type == '1' || $filter_type == '') { // New questions

                    $sub_attenptQuess = AttemptQuestion::select('question_id')->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                    if ($is_plan_exist == 1) {
                        $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                    }

                    $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else { // for free trial
                        // $sub_queQuery->where('test_mode',1);

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }

                    $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();

                    $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                } else if ($filter_type == '2') { // new and prev incorrect

                    $sub_attenptQuess = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)');

                    if ($is_plan_exist == 1) {
                        $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                    }

                    $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();
                    $tot_sub_cat_attempt = count($sub_attenptQueArr);

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else {
                        // $sub_queQuery->where('test_mode',1);

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $sub_attenptQueArr)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }
                    $sub_questions_id_Arr1 = $sub_queQuery->pluck('id')->toArray();
                    $tot_sub_cat_que_dt = count($sub_questions_id_Arr1);

                    $queQuery2 = AttemptQuestion::whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id])->where('is_correct', '0');

                    if ($is_plan_exist == 1) {
                        $queQuery2->whereIn('question_id', $buyQuesIdArr);
                    }

                    $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                    $tot_sub_cat_correct = count($questions_id_Arr2);

                    $sub_filter_questions_id = array_merge($sub_questions_id_Arr1, $questions_id_Arr2);
                    // return $sub_filter_questions_id;
                    // dd($sub_filter_questions_id);

                    if (!empty($filter_questions_id)) {
                        $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
                    } else {
                        $filter_questions_id = $sub_filter_questions_id;
                    }
                } else if ($filter_type == '3') { // All question

                    $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                    if ($is_plan_exist == 1) {
                        $sub_queQuery->whereIn('id', $buyQuesIdArr);
                    } else { // for free trial
                        // $sub_queQuery->where('test_mode',1); 

                        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                        $sub_queQuery->whereIn('id', $testModeQueId);
                    }

                    $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();

                    $tot_sub_cat_que_dt = count($sub_filter_questions_id);

                    $tot_sub_cat_attempt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->whereIn('question_id', $sub_filter_questions_id)->count();

                    $tot_sub_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->whereIn('question_id', $sub_filter_questions_id)->count();

                    $tot_sub_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->whereIn('question_id', $sub_filter_questions_id)->count();
                }
                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                }


                if ($filter_type == '1' || $filter_type == '2') {
                    $tot_sub_cat_attempt = 0;
                }



                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id)->count();
                // print_r([$getAllQueCount,'cour'=>$course_id,'category_id'=>$subCatDt->category_id,'su'=>$subCatDt->id]);die;
                $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->count();

                $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->count();


                $total_all_question_list = $tot_sub_cat_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;


                $at = ($total_all_correct + $total_cat_incorrect);
                $c = $total_all_correct;
                $i = $total_cat_incorrect;


                if ($total_all_question_list == 0) {
                    $at = 0;
                    $c = 0;
                    $i = 0;
                }


                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }


                if ($total_all_question_list == 0) {
                    $total_correct_percentage = 0;
                    $total_incorrect_percentage = 0;
                    $total_white_percentage = 0;
                }

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $total_all_question_list,
                    'total_attenpt' => $at,
                    'total_correct' => $c,
                    'total_incorrect' => $i,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_filter_questions_id' => (count($sub_filter_questions_id) > 0) ? implode(',', $sub_filter_questions_id) : "",
                ];
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];

            if ($filter_type == '1' || $filter_type == '') { // for new question

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                /*  if(isset($request->tutorial_id)){
                            $attenptQue->where('tutorial_id',$tutorial_id);
                        } */
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();


                $queQueryT = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                if (!empty($attenptQueArr)) {
                    $queQueryT->whereNotIn('id', $attenptQueArr);
                }

                $queQuery = $queQueryT->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*  if(isset($request->tutorial_id)){   
                            $queQuery->where('tutorial_id',$tutorial_id);
                        }  */

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }

                $filter_questions_id = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '2') { // for new question & prev. incorrect

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

                /*    if(isset($request->tutorial_id)){
                            $attenptQue->where('tutorial_id',$tutorial_id);
                        } */
                // if($is_plan_exist==1){
                //     $attenptQue->whereIn('question_id',$buyQuesIdArr);
                // }
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

                $total_attenpt = count($attenptQueArr);

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

                // $queQuery->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');


                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*   if(isset($request->tutorial_id)){
                            $queQuery->where('tutorial_id',$tutorial_id);
                        }   */
                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }

                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                $queQuery_new_1Que = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery_newQue->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery_new_1Que->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery_new_1Que->whereIn('id', $testModeQueId);
                }

                $queQuery_new = $queQuery_newQue->count();
                $queQuery_new_1 = $queQuery_new_1Que->count();

                $tot_que_dt =  $queQuery_new_1;
                // $tot_que_dt = count($questions_id_Arr1);
                $tot_all_que = $tot_all_que + $queQuery_new;
                // return $tot_que_dt;

                $queQuery2 = AttemptQuestion::where(['user_id' => $user_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                if (isset($request->tutorial_id)) {
                    $queQuery2->where('tutorial_id', $tutorial_id);
                }
                if ($is_plan_exist == 1) {
                    $queQuery2->whereIn('question_id', $buyQuesIdArr);
                } else {
                    $queQuery2->whereIn('question_id', $testModeQueId);
                }

                $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                $tot_cat_correct = count($questions_id_Arr2);

                //dd($filter_questions_id);
                $filter_questions_id = array_merge($filter_questions_id, $questions_id_Arr1, $questions_id_Arr2);
            } else if ($filter_type == '3') { // for all types

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                /* if(isset($request->tutorial_id)){
                            $queQuery->where('tutorial_id',$tutorial_id);
                        }   */

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($questions_id_Arr1);

                $que_total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id]);

                /*  if(isset($request->tutorial_id)){
                            $que_total_attenpt->where('tutorial_id',$tutorial_id);
                        } */
                if ($is_plan_exist == 0) {
                    $que_total_attenpt->whereIn('question_id', $buyQuesIdArr);
                }
                $total_attenpt = $que_total_attenpt->get()->count();

                $que_tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '1');

                /*   if(isset($request->tutorial_id)){
                            $que_tot_cat_correct->where('tutorial_id',$tutorial_id);
                        } */
                if ($is_plan_exist == 0) {
                    $que_tot_cat_correct->whereIn('question_id', $buyQuesIdArr);
                }
                $tot_cat_correct = $que_tot_cat_correct->get()->count();

                $que_total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                /*  if(isset($request->tutorial_id)){
                            $que_total_cat_incorrect->where('tutorial_id',$tutorial_id);
                        } */
                if ($is_plan_exist == 0) {
                    $que_total_cat_incorrect->whereIn('question_id', $buyQuesIdArr);
                }
                $total_cat_incorrect = $que_total_cat_incorrect->get()->count();

                $filter_questions_id = $questions_id_Arr1;
            }

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);
            // return $filter_questions_id; 
            $total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->count();

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();
            ///print_r(['user_id'=>$user_id,'course_id'=>$course_id,'category_id'=>$catDt->id]);die;
            $cr_attenpt = count($filter_questions_id);
            if ($filter_type == '1') {
                $cr_attenpt = $total_attenpt = 0;
            } else if ($filter_type == '2') {
                $cr_attenpt = 0; //$tot_cat_correct;  

                // $tot_que_dt = $tot_que_dt-$tot_cat_correct;

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '3') {
                $cr_attenpt = $tot_cat_correct + $total_cat_incorrect;
                // $tot_que_dt = $tot_que_dt-$cr_attenpt;
            }



            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }



            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }





            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect), //$total_attenpt,
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            if ($filter_type == '2') {
                $tot_all_que = $tot_all_que;
            } else {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }
            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') // for tutorial
            {
                // $filter_questions_id = [];

                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }
                /*else{
                        $totalTutorialsQue->where('test_mode',1);
                    }*/

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';


                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }
                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }




                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;
                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }

            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }
        /*if($request->record_type=='2')
            {*/

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";
        // } 

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();

        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];

        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->get()->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->get()->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }


            $req_data['total_questions'] = $total_all_question_list;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;

            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2')  // for tutorial
        {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function update_user_profile(Request $request)
    {

        $user_id = auth()->user()->id;

        $req_data = [];

        $rules = [
            'user_name' => 'required',
            'phone' => 'required|integer|unique:users,phone,' . $user_id,
            'email' => 'required|unique:users,email,' . $user_id,
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            return $this->json_view(false, $req_data, $error);
        }

        $input = [
            'name' => $request->user_name,
            'phone' => $request->phone,
            'email' => $request->email ?? '',
        ];

        if ($request->hasFile('profile_photo_path')) {
            $profile_photo_img_path = $request->profile_photo_path->store('profile_photo_path');
            $input['profile_photo_path'] = $profile_photo_img_path;
        }

        User::find($user_id)->update($input);

        $req_message = "Profile Updated Successful";

        $user = User::find($user_id);

        // $req_data['access_token'] = $user->createToken('authToken', ['user'])->accessToken;
        return $this->json_view(true, $req_data, $req_message);
    }

    public function get_user_profile(Request $request)
    {

        $user_id = auth()->user()->id;

        $req_data = [];
        $gender_name = "";
        $user = User::find($user_id);

        if ($user->gender == '1') {
            $gender_name = "Male";
        } else if ($user->gender == '2') {
            $gender_name = "Female";
        }
        $req_data = [
            'user_id' => $user->id,
            'user_name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'gender_name' => $gender_name,
            'profile_image' => ($user->profile_photo_path) ? url('uploads/' . $user->profile_photo_path) : "",
        ];

        $req_message = "User Information";

        return $this->json_view(true, $req_data, $req_message);
    }

    public function reset_quiz(Request $request)
    {

        $user_id = auth()->user()->id;

        $req_data = [];

        TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        TempTest::where(['user_id' => $user_id])->delete();

        TempTest::where(['user_id' => $user_id])->delete();

        $req_message = "Data reset successfully";

        return $this->json_view(true, $req_data, $req_message);
    }

    public function additional_time_category_for_quiz(Request $request)
    {

        $user_id = auth()->user()->id;

        $req_data = [];

        $rules = [
            'filter_questions_id' => 'required',
            'course_id' => 'required',
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            return $this->json_view(false, $req_data, $error);
        }
        $courseid = $request->course_id;
        // AttemptQuestion::where('course_id',courseid)->where('user_id',$user_id)->delete();
        $courseid = !empty($request->course_id) ? (int) $request->course_id : 0;
        $findCourse = Course::find($courseid);

        $popupStatus = !empty($findCourse->is_modal) ? 1 : 0;

        $count_down_time = '';

        if ($request->filter_questions_id) {

            $queIdArr = explode(',', $request->filter_questions_id);

            $question_time = '00:00:00';
            foreach ($queIdArr as $queId) {
                $QueAnsQuery = QuestionAnswer::leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->where('question_answer_tbl.id', $queId)->first(['category_tbl.time']);

                if (isset($QueAnsQuery->time)) {
                    $que_time = $QueAnsQuery->time;
                    $question_time = $this->sum_the_time($question_time, $que_time);
                }
            }
            $count_down_time = date('H:i:s', strtotime($question_time));
            $hours = date('H', strtotime($question_time));
            $min = date('i', strtotime($question_time));
            $sec = date('s', strtotime($question_time));

            $total_min = ($hours * 60) + $min + ($sec / 60);

            $after_per_25 = ($total_min * 25 / 100);
            $after_per_25_explode = explode('.', $after_per_25);
            $after_per_25_min = $after_per_25_explode[0];
            $after_per_25_sec = 0;
            if (!empty($after_per_25_explode[1])) {
                $after_per_25_sec = $after_per_25_explode[1] * 60 / 100;
            }
            $final_time_25 = date('H:i:s', strtotime($question_time . ' +' . $after_per_25_min . ' minutes +' . $after_per_25_sec . ' seconds'));

            // return $after_per_25_sec;
            $after_per_50 = ($total_min * 50 / 100);
            $after_per_50_explode = explode('.', $after_per_50);
            $after_per_50_min = $after_per_50_explode[0];
            $after_per_50_sec = 0;
            if (!empty($after_per_50_explode[1])) {
                $after_per_50_sec = $after_per_50_explode[1] * 60 / 100;
            }
            $final_time_50 = date('H:i:s', strtotime($question_time . ' +' . $after_per_50_min . ' minutes +' . $after_per_50_sec . ' seconds'));
        }
        // return $final_time_25;
        $UCAT = $count_down_time;
        $UCATSEN = $final_time_25;
        $UCATSEN50 = $final_time_50;

        $add_cat = [
            [
                'additional_category' => "UCAT",
                'additional_time_in_percent' => "0",
                'time' => $UCAT,
            ],
            [
                'additional_category' => "UCATSEN",
                'additional_time_in_percent' => "25",
                'time' => $UCATSEN,
            ],
            [
                'additional_category' => "UCATSEN50",
                'additional_time_in_percent' => "50",
                'time' => $UCATSEN50,
            ]
        ];

        $req_data['additional_category'] = $add_cat;
        $req_data['filter_questions_id'] = $request->filter_questions_id;
        $req_data['popup_status'] = $findCourse->course_type_id == 1 ? $popupStatus : 0;

        $req_message = "Additional Time Data";

        return $this->json_view(true, $req_data, $req_message);
    }

    public function check_mailsend_api(Request $request)
    {
        $req_data = [];

        $random_verify_str = substr(md5(mt_rand()), 0, 49);

        $Data = array(
            "verify_code" => $random_verify_str
        );

        $verify_link = '<a href="' . env('APP_URL') . '/reset_password/verify_mail/' . $random_verify_str . '" style="background-color: #7087A3; font-size: 12px; padding: 10px 15px; color: #fff; text-decoration: none">Reset Password</a>';

        $user_email = "manvendrajploft@gmail.com";

        $mail_data = [
            'receiver' => ucwords('manvendra singh'),
            'email' => $user_email,
            'web_url' => env('APP_URL'),
            'verify_link' => $verify_link
        ];
        try {
            if (env('MAIL_ON_OFF_STATUS') == "on") {
                \Mail::send('mails.reset_password_mail', $mail_data, function ($message) use ($mail_data) {
                    $message->to($mail_data['email']);
                    $message->from(env('MAIL_FROM_ADDRESS'), env('APP_NAME'));
                    $message->subject(env('APP_NAME') . ' Reset Password Notification');
                });
            }

            $message = 'Reset Password link is send to your email. Please check your account...!';
            return $this->json_view(true, $req_data, $message);
        } catch (\Throwable $e) {
            return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
        }
    }

    public function mailchimp_test(Request $request)
    {
        $req_data = [];

        $mailchimp = new \MailchimpMarketing\ApiClient();

        $mailchimp->setConfig([
            'apiKey' => 'ab5feef6f7310214cdd68e889dcb8943-us10',
            'server' => 'us10'
        ]);

        $response = $mailchimp->ping->get();
        print_r($response);

        return $this->json_view(true, $req_data, 'Mailchimp Test');
    }

    public function get_tutorial_list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
            'category_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $user_id = auth()->user()->id;
            $course_id = $request->course_id;
            $category_id = $request->category_id;

            $tutArr = [];

            $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
                ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1'])
                ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
                ->where('user_id', $user_id)
                ->pluck('package_tbl.assign_tutorial_id')->join(',');

            $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];

            Log::info('buyTutIdArr' . json_encode($buyTutIdArr));

            $is_plan_exist = (count($buyTutIdArr) > 0) ? '1' : '0';

            if ($is_plan_exist == 0) {
                $freeCourse = $this->getfreecourse($user_id);
                if (in_array($course_id, $freeCourse)) {
                    $is_plan_exist = 1;
                }
            }

            $getTutorialOrders = TutorialOrder::where("course_id", $course_id)->where("category_id", $category_id)->orderBy("tutorialorder", "asc")->get();

            if (!empty($getTutorialOrders) && count($getTutorialOrders) > 0) {

                foreach ($getTutorialOrders as $tutorialDetail) {
                    $getTutorialQue = Tutorial::orderBy('tutorialorder', 'asc')->where(['category_id' => $category_id, 'status' => 1, 'id' => $tutorialDetail->tutorial_id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                    if ($is_plan_exist == '1') {
                        $getTutorialQue->whereIn('id', $buyTutIdArr);
                    }

                    $getTutorial = $getTutorialQue->first();
                    if (!empty($getTutorial)) {
                        $tutArr[] = $this->tutorialResponse($getTutorial, $user_id, $course_id, $category_id, $is_plan_exist);
                    }
                }
            } else {
                $getTutorialQue = Tutorial::orderBy('tutorialorder', 'asc')->where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == '1') {
                    $getTutorialQue->whereIn('id', $buyTutIdArr);
                }

                $getTutorial = $getTutorialQue->get();

                Log::info('getTutorial' . json_encode($getTutorial));

                foreach ($getTutorial as $val) {
                    $tutArr[] = $this->tutorialResponse($val, $user_id, $course_id, $category_id, $is_plan_exist);
                }
            }

            $getShowIds = Tutorial::where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->pluck('id')->toArray();

            $totalTutorials =  Tutorial::where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

            $seenedTutorials =  WatchedTutorial::where(['course_id' => $course_id, 'category_id' => $category_id, 'user_id' => $user_id])
                ->whereIn('tutorial_id', $getShowIds)->get()->count();

            if ($seenedTutorials > 0 && $totalTutorials > 0)
                $my_score =  ($seenedTutorials * 100) / $totalTutorials;
            else
                $my_score = '0';

            $getCat = Category::where('id', $category_id)->first();

            $req_data['category_name'] = @$getCat->category_name ?? "";
            $req_data['tutorial_list'] = $tutArr;
            $req_data['total_tutorial'] = count($tutArr);
            $req_data['seened_tutorial'] = $seenedTutorials;
            $req_data['score_in_percent'] = $my_score;
            $req_data['is_plan_exist'] = $is_plan_exist;

            if (count($tutArr)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }
    public function tutorialResponse($tutDt, $user_id, $course_id, $category_id, $is_plan_exist)
    {
        $getNote = TutorialNote::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->get(['id', 'notes']);
        $getNoteArr = ($getNote->count() > 0) ? $getNote->toArray() : [];

        $get_bookmarkCount = Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutDt->id])->count();
        $is_tutorial_bookmarked = ($get_bookmarkCount > 0) ? '1' : '0';

        $watched_tutorial =  WatchedTutorial::where(['course_id' => $course_id, 'category_id' => $category_id, 'user_id' => $user_id, 'tutorial_id' => $tutDt->id, 'user_id' => $user_id])->first();

        $check_test_mode = AssignTutorial::where(['course_id' => $course_id, 'tutorial_id' => $tutDt->id])->first(['id']);
        $is_test_mode = (isset($check_test_mode->id)) ? '1' : '0';
        $videoComment = VideoComment::where("tutorial_id", $tutDt->id)->where("parent_id", 0)->with("user")->orderBy("likecount", "DESC")->get();
        $allComment = array();
        foreach ($videoComment as $cmtKey => $cmt) {
            $adminComment = VideoComment::where('parent_id', $cmt->id)->where('admin_reply', 1)->orderBy("likecount", "DESC")->get();
            $adminData = array();
            foreach ($adminComment as $key1 => $admin) {
                $userDetail = User::find($admin->user_id);
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $userDetail;
            }
            $allComment[$cmtKey] = $cmt;
            $allComment[$cmtKey]['adminComment'] = $adminData;
        }
        $tutorialFile = TutorialFile::where("tutorial_id", $tutDt->id)->with("subfiles")->first();


        $tutArr = [
            'tutorial_id' => $tutDt->id,
            'test_mode' => $is_test_mode,
            'chapter_name' => $tutDt->chapter_name,
            'video_url' => $tutDt->video_url,
            'pdf_heading' => $tutDt->pdf_heading,
            'custom_code' => $tutDt->custom_code,
            'video_heading' => $tutDt->video_heading,
            'video_pdf_order' => $tutDt->video_pdf_order,
            'video_type' => $tutDt->video_type,
            'pdf_url' => $tutDt->pdf_url,
            'is_tutorial_bookmarked' => $is_tutorial_bookmarked,
            'total_video_time' => $tutDt->total_video_time,
            'trans_script' => $tutDt->trans_script,
            'my_total_video_time' => @$watched_tutorial->total_video_time,
            'my_watched_time' => @$watched_tutorial->watched_time,
            'comment' => @$allComment,
            'files' => @$tutorialFile,

            'get_notes' => $getNoteArr,
            'is_exist_in_plan' => $is_plan_exist,
        ];

        return $tutArr;
    }

    public function get_course_listold(Request $request)
    {
        $user_id = $request->user_id;

        $buy_user_course_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where('user_id', $user_id)
            ->pluck('order_detail.particular_record_id')
            ->toArray();

        $buy_user_course_ids = array_unique($buy_user_course_arr);

        $getCourseDt =  Course::whereIn('id', $buy_user_course_ids)->where('status', 1)->orderBy('sort', 'asc')->get();
        $getFeaturedCourse = [];
        $tempData = [];


        foreach ($getCourseDt as $feat_dt) {
            $course_id = $feat_dt->id;
            $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
                ->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
                ->where(['order_detail.package_for' => '1'])
                ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
                ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->count();
            $is_plan_exist = ($check_plan > 0) ? '1' : '0';

            $que_category = QuestionAnswer::leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->whereRaw('FIND_IN_SET("' . $feat_dt->id . '",question_answer_tbl.course_id)')->get(['category_tbl.time']);

            $no_of_hours = 0;
            foreach ($que_category as $getQueDt) {
                if (isset($getQueDt->time)) {
                    $no_of_hours += strtotime($getQueDt->time);
                }
            }
            $tot_hours = ($no_of_hours > 0) ? date('H:m:s', strtotime($no_of_hours)) : '';

            $totalTutorialsQue =  Tutorial::whereRaw('FIND_IN_SET("' . $feat_dt->id . '",course_id)')->where('status', 1);
            $totalTutorials = $totalTutorialsQue->count();

            $totalStudents =  OrderDetail::where(['particular_record_id' => $feat_dt->id, 'package_for' => '1'])->count();

            $attemptCount = AttemptQuestion::where(['course_id' => $feat_dt->id, 'user_id' => $user_id])->count();
            $watchedCount = WatchedTutorial::where(['course_id' => $feat_dt->id, 'user_id' => $user_id])->count();
            $is_course_attempt = $attemptCount + $watchedCount;

            $myWatchedTutorial = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $feat_dt->id, 'watched_tutorial.user_id' => $user_id])->first(['watched_tutorial.tutorial_id', 'tutorial_tbl.chapter_name', 'category_tbl.id as category_id', 'category_tbl.category_name']);

            $getFeaturedCourse[] = array(
                "course_id" => $feat_dt->id,
                "course_name" => $feat_dt->course_name,
                "course_image" => $feat_dt->course_image,
                "no_of_lessions" => $totalTutorials,
                "no_of_hours" => $tot_hours,
                'total_hours' => $feat_dt->total_hours ?? 0,
                "no_of_students" => $totalStudents,
                "is_course_attempt" => ($is_course_attempt > 0) ? '1' : '0',
                "last_watched_tutorial_id" => @$myWatchedTutorial->tutorial_id,
                "last_watched_chapter_name" => @$myWatchedTutorial->chapter_name,
                "last_watched_category_id" => @$myWatchedTutorial->category_id,
                "last_watched_category_name" => @$myWatchedTutorial->category_name,
            );
        }
        if ($request->user_id) {
            $req_data['enrolled_course_list'] = $getFeaturedCourse;
        }

        $getCourseDt2 =  Course::whereNotIn('id', $buy_user_course_ids)->where('status', 1)->orderBy('sort', 'asc')->get();

        $getCourse = [];
        foreach ($getCourseDt2 as $course_dt) {

            $que2_category = QuestionAnswer::leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->whereRaw('FIND_IN_SET("' . $course_dt->id . '",question_answer_tbl.course_id)')->get(['category_tbl.time']);

            $no_of_hours2 = 0;
            foreach ($que2_category as $getQueDt2) {
                if (isset($getQueDt2->time)) {
                    $no_of_hours2 += strtotime($getQueDt2->time);
                }
            }
            $tot_hours2 = ($no_of_hours2 > 0) ? date('H:m:s', strtotime($no_of_hours2)) : '';

            $totalTutorials2 =  Tutorial::whereRaw('FIND_IN_SET("' . $course_dt->id . '",course_id)')->where('status', 1)->count();
            $totalStudents2 =  OrderDetail::where(['particular_record_id' => $course_dt->id, 'package_for' => '1'])->count();

            $getCourse[] = array(
                "course_id" => $course_dt->id,
                "course_name" => $course_dt->course_name,
                "course_image" => $course_dt->course_image,
                "no_of_lessions" => $totalTutorials2,
                "no_of_hours" => $tot_hours2,
                'total_hours' => $course_dt->total_hours ?? 0,
                "no_of_students" => $totalStudents2,

                'sort' => $course_dt->sort
            );
        }

        $req_data['course_list'] = $getCourse;
        $tutoring = Tutoring::first();
        $req_data['tutoringUrl'] = $tutoring->url;

        if (count($getCourse)) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function getfreecourse($user_id)
    {
        $buy_packageId = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where('user_id', $user_id)
            ->pluck('order_detail.package_id')
            ->toArray();

        $freeCourseId = Package::whereIn("id", $buy_packageId)->get();
        $freeCourse = array();
        foreach ($freeCourseId as $val) {
            $exploaded = explode(",", $val->freecourse);
            foreach ($exploaded as $val1) {
                if (!empty($val1))
                    $freeCourse[] = $val1;
            }
        }
        return $freeCourse;
    }
    public function get_course_list(Request $request)
    {
        $user_id = $request->user_id;
        $freeCourse = $this->getfreecourse($user_id);

        $buy_user_course_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where('user_id', $user_id)
            ->pluck('order_detail.particular_record_id')
            ->toArray();

        $buy_user_course_ids = array_merge($buy_user_course_arr, $freeCourse);
        $buy_user_course_ids = array_unique($buy_user_course_ids);

        $getCourseDt =  Course::whereIn('id', $buy_user_course_ids)->where('status', 1)->orderBy('sort', 'asc')->get();
        $getFeaturedCourse = [];
        $tempData = [];

        foreach ($getCourseDt as $feat_dt) {
            $course_id = $feat_dt->id;

            $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')
                ->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
                ->where(['order_detail.package_for' => '1'])
                ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
                ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->count();

            $is_plan_exist = ($check_plan > 0) ? '1' : '0';

            $que_category = QuestionAnswer::leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->whereRaw('FIND_IN_SET("' . $feat_dt->id . '",question_answer_tbl.course_id)')->get(['category_tbl.time']);

            $no_of_hours = 0;
            foreach ($que_category as $getQueDt) {
                if (isset($getQueDt->time)) {
                    $no_of_hours += strtotime($getQueDt->time);
                }
            }
            $tot_hours = ($no_of_hours > 0) ? date('H:m:s', strtotime($no_of_hours)) : '';

            $totalTutorialsQue =  Tutorial::whereRaw('FIND_IN_SET("' . $feat_dt->id . '",course_id)')->where('status', 1);
            $totalTutorials = $totalTutorialsQue->count();

            $totalStudents =  OrderDetail::where(['particular_record_id' => $feat_dt->id, 'package_for' => '1'])->count();

            $attemptCount = AttemptQuestion::where(['course_id' => $feat_dt->id, 'user_id' => $user_id])->count();
            $watchedCount = WatchedTutorial::where(['course_id' => $feat_dt->id, 'user_id' => $user_id])->count();
            $is_course_attempt = $attemptCount + $watchedCount;

            $myWatchedTutorial = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $feat_dt->id, 'watched_tutorial.user_id' => $user_id])->first(['watched_tutorial.tutorial_id', 'tutorial_tbl.chapter_name', 'category_tbl.id as category_id', 'category_tbl.category_name']);

            $getFeaturedCourse[] = array(
                "course_id" => $feat_dt->id,
                "typename" => @$feat_dt->courseType->name ? $feat_dt->courseType->name : '',
                "course_name" => $feat_dt->course_name,
                "course_image" => $feat_dt->course_image,
                "no_of_lessions" => $totalTutorials,
                "no_of_hours" => $tot_hours,
                'total_hours' => $feat_dt->total_hours ?? 0,
                "no_of_students" => $totalStudents,
                "is_course_attempt" => ($is_course_attempt > 0) ? '1' : '0',
                "last_watched_tutorial_id" => @$myWatchedTutorial->tutorial_id,
                "last_watched_chapter_name" => @$myWatchedTutorial->chapter_name,
                "last_watched_category_id" => @$myWatchedTutorial->category_id,
                "last_watched_category_name" => @$myWatchedTutorial->category_name,
            );
        }
        if ($request->user_id) {
            $req_data['enrolled_course_list'] = $getFeaturedCourse;
        }

        $getCourseDt2 =  Course::whereNotIn('id', $buy_user_course_ids)->where('status', 1)->orderBy('sort', 'asc')->get();

        $getCourse = [];
        foreach ($getCourseDt2 as $course_dt) {

            $que2_category = QuestionAnswer::leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->whereRaw('FIND_IN_SET("' . $course_dt->id . '",question_answer_tbl.course_id)')->get(['category_tbl.time']);

            $no_of_hours2 = 0;
            foreach ($que2_category as $getQueDt2) {
                if (isset($getQueDt2->time)) {
                    $no_of_hours2 += strtotime($getQueDt2->time);
                }
            }
            $tot_hours2 = ($no_of_hours2 > 0) ? date('H:m:s', strtotime($no_of_hours2)) : '';

            $totalTutorials2 =  Tutorial::whereRaw('FIND_IN_SET("' . $course_dt->id . '",course_id)')->where('status', 1)->count();
            $totalStudents2 =  OrderDetail::where(['particular_record_id' => $course_dt->id, 'package_for' => '1'])->count();
            $getCourse[$course_dt->course_type_id]['typename'] = @$course_dt->courseType->name ? $course_dt->courseType->name : '';

            $getCourse[$course_dt->course_type_id]['list'][] = array(
                "course_id" => $course_dt->id,
                "course_name" => $course_dt->course_name,
                "course_image" => $course_dt->course_image,
                "no_of_lessions" => $totalTutorials2,
                "no_of_hours" => $tot_hours2,
                'total_hours' => $course_dt->total_hours ?? 0,
                "no_of_students" => $totalStudents2,

                'sort' => $course_dt->sort
            );
        }
        $getCourse = array_values($getCourse);
        $req_data['course_list'] = $getCourse;
        $tutoring = Tutoring::first();
        $req_data['tutoringUrl'] = $tutoring->url;

        if (count($getCourse)) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function get_course_detail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'course_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        }


        $course_id = $request->course_id;
        $user_id = $request->user_id;
        $response = $this->courseDetailLogic($course_id, $user_id);
        return $this->json_view($response['status'], $response['data'], $response['message']);
    }
    public function courseDetailLogic($course_id, $user_id)
    {
        $courseDetailsRow = Course::find($course_id);
        $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
        $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];

        $packCOunt = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->count();

        if ($packCOunt == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($course_id, $freeCourse)) {
                $packCOunt = 1;
            }
        }

        $is_package_purchased = ($packCOunt > 0) ? '1' : '0';

        $remaining_exam_days = '';

        if (!empty($course_exam_date)) {
            if (date('Y-m-d') <= $course_exam_date) {
                $fdate = date('Y-m-d');
                $tdate = $course_exam_date;
                $datetime1 = new DateTime($fdate);
                $datetime2 = new DateTime($tdate);
                $interval = $datetime1->diff($datetime2);
                $remaining_exam_days = $interval->format('%a');
            } else if (date('Y-m-d') > $course_exam_date) {
                $remaining_exam_days = '';
            } else {
                $remaining_exam_days = '';
            }
        }


        $popupBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 1])->count();
        $popupBannerBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 2])->count();

        $is_popup_block = ($popupBlockCount > 0) ? '1' : '0';
        $is_banner_block = ($popupBannerBlockCount > 0) ? '1' : '0';

        $req_data['is_popup_block'] = $is_popup_block;
        $req_data['is_banner_block'] = $is_banner_block;
        $req_data['is_question_number'] = @$courseDetailsRow->is_tutorial == 1 && $courseDetailsRow->is_test == 0 && $courseDetailsRow->is_question == 0 ? 0 : 1;

        $req_data['is_package_purchased'] = $is_package_purchased;
        $req_data['course_exam_date'] = $course_exam_date;
        $req_data['remaining_exam_days'] = $remaining_exam_days;

        $getCourseDt = Course::orderBy('id', 'desc')->where('id', $course_id)->first(['id', 'course_name', 'course_image', 'video_image', 'banner_content', 'banner_link', 'popup_content', 'popup_course_image', 'popup_link', 'is_tutorial', 'is_question', 'is_test']);
        $getCourse = (isset($getCourseDt->id)) ? $getCourseDt->toArray() : [];
        $req_data['course_detail'] = $getCourse;

        $getLastWatchedDt =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.user_id' => $user_id])->first(['tutorial_tbl.id', 'tutorial_tbl.category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $purchased_plans_users_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1'])->pluck('order_tbl.user_id')->toArray();

        $total_tutorials = '0';
        $total_tutorial_seen = '0';
        $my_total_attempt = '0';
        $catTutArr = [];
        $catQueArr = [];

        $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : []);

        if (isset($request->category_id)) {
            $catQue->where('id', $request->category_id);
        }

        $getCatgory = $catQue->where('status', 1)->orderBy('sort', 'asc')->get();

        $aall_my_total_attempt = '0';
        $totalLessions = 0;
        $mainAvarage = 0;
        $mainPercentileCount = 0;
        $totalAttemptCategoryList = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get();
        $totalAttemptCategory = array();

        foreach ($totalAttemptCategoryList as $val) {
            $totalAttemptCategory[$val->category_id] = $val->category_id;
        }

        $outSideTotalQuestion = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get()->count();
        $outSideTotalCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->get()->count();
        $outSideTotalIncorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '0'])->get()->count();
        $totalquestionofcourse =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereIn("category_id", $totalAttemptCategory)->get()->count();


        $outSideTotalAttempt = $outSideTotalCorrect + $outSideTotalIncorrect;

        $outSideAverage = $outSideTotalQuestion - (($outSideTotalIncorrect + $outSideTotalCorrect) - $outSideTotalCorrect);
        $outSideTotalAverageCal =  $this->getUserWisePercentage($purchased_plans_users_arr, $user_id, $course_id, true, $outSideAverage, '', true);

        $allQuestionAtStatus  = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->first();


        $DD_your_total_average_score = 0;
        $DD_your_total_percentile_score = 0;
        foreach ($getCatgory as $catDt) {

            $totalTutorialsQue =  Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id, 'status' => 1]);


            $seenedTutorialsQue =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.category_id' => $catDt->id, 'watched_tutorial.user_id' => $user_id]);

            if ($is_package_purchased > 0) {
                $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $buyTutIdArr);
            } else {
                $testModeTutorialId = AssignTutorial::where('course_id', $course_id)->pluck('tutorial_id')->toArray();

                $totalTutorialsQue->whereIn('id', $testModeTutorialId);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $testModeTutorialId);
            }
            $totalTutorials = $totalTutorialsQue->get()->count();
            $seenedTutorials = $seenedTutorialsQue->get()->count();

            $catTutArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'total_tutorials' => !empty($is_package_purchased) ? $totalTutorials : rand(11, 99),
                'tutorial_seen_by_me' => !empty($is_package_purchased) ? $seenedTutorials : rand(11, 99),
            ];
            $total_tutorials = $total_tutorials + $totalTutorials;
            $total_tutorial_seen = $total_tutorial_seen + $seenedTutorials;

            $totalQuestionsAll =  QuestionAnswer::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
            $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

            $totalAllUserAttemptCorrect = AttemptQuestion::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'is_correct' => '1'])->get()->count();


            $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;

            $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;
            $totalQuestions = (int)$totalQuestions;

            $totalAllAttemptQuestions = (int)$totalAllAttemptQuestions;

            if ($totalAllUserAttemptCorrect > 0 && $totalAllAttemptQuestions > 0)
                $mm_score =  ($totalAllUserAttemptCorrect * 100) / $totalAllAttemptQuestions;
            else
                $mm_score = '0';

            $aall_my_total_attempt = $aall_my_total_attempt + $totalAllAttemptQuestions;

            $my_total_attempt = $my_total_attempt + $totalMyAttemptCorrect;


            $totalQuestionsWithouAttempt = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id])->get()->count();

            $your_score = '0';
            if ($totalMyAttemptCorrect > 0 && $totalQuestions > 0) {
                $your_score =  ($totalMyAttemptCorrect / $totalQuestions) * 100;
            }

            // your percentile category
            $yPercentileScore = 0;
            if ($totalMyAttemptCorrect > 0) {
                $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptCorrect + $totalMyAttemptInCorrect)) * 100;
            }

            $sssub_cat_arr = [];

            // My Logic ///
            $catAverage = $totalQuestions - (($totalMyAttemptInCorrect + $totalMyAttemptCorrect) - $totalMyAttemptCorrect);

            if ($totalMyAttemptInCorrect > 0 || $totalMyAttemptInCorrect > 0) {
                $catAverage = ($totalMyAttemptCorrect * 100) / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect);
            }

            /// My Logic End ///
            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();
            foreach ($getSubCat as $subCatDt) {

                $sub_totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->get()->count();
                $sub_totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->get()->count();


                $sub_totalAllUserAttemptCorrect = AttemptQuestion::whereIn('user_id', $purchased_plans_users_arr)
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])
                    ->get()->count();



                $totalQuestionsWithouAttemptSub = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $subAverage = '0';
                if ($totalMyAttemptCorrect > 0 && $totalQuestionsWithouAttemptSub > 0) {
                    $subAverage = ($sub_totalMyAttemptCorrect / ($totalQuestionsWithouAttemptSub)) * 100;
                }


                // your percentile category
                $subPercentileScore = 0;
                if ($sub_totalMyAttemptCorrect > 0) {
                    $subPercentileScore = ($sub_totalMyAttemptCorrect / ($totalMyAttemptCorrect + $sub_totalMyAttemptInCorrect)) * 100;
                }

                $subPercentileScore =   !empty($subPercentileScore) ? $subPercentileScore : 0;


                $sub_totalMyAttemptCorrect = (int)$sub_totalMyAttemptCorrect;

                $sub_totalAllUserAttemptCorrect = (int)$sub_totalAllUserAttemptCorrect;
                $sub_totalQuestions = (int)$sub_totalQuestions;

                $sub_totalAllAttemptQuestions = (int)$sub_totalAllAttemptQuestions;

                if ($sub_totalAllUserAttemptCorrect > 0 && $sub_totalAllAttemptQuestions > 0)
                    $sub_mm_score =  ($sub_totalAllUserAttemptCorrect * 100) / $sub_totalAllAttemptQuestions;
                else
                    $sub_mm_score = '0';

                $my_total_attempt = $my_total_attempt + $sub_totalMyAttemptCorrect;

                if ($sub_totalMyAttemptCorrect > 0 && $sub_totalQuestions > 0)
                    $sub_your_score =  ($sub_totalMyAttemptCorrect * 100) / $sub_totalQuestions;
                else
                    $sub_your_score = '0';

                $sssub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'mm_score' => !empty($is_package_purchased) ? $subPercentileScore : 0,
                    'your_score' => !empty($is_package_purchased) ? $subAverage : 0,
                ];
            }

            $avgAllUserCategoryWise = 0;
            if ($totalAllAttemptQuestions > 0 && $totalAllUserAttemptCorrect > 0) {
                $avgAllUserCategoryWise = ($totalAllUserAttemptCorrect / $totalAllAttemptQuestions) * 100;
            }

            $avgAllUserCategoryWise = round($avgAllUserCategoryWise);
            $catQueArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'sort_category_name' => $catDt->short_name,
                'mm_score' => !empty($is_package_purchased) ? $avgAllUserCategoryWise : 0,
                'your_score' => !empty($is_package_purchased) ? $your_score : 0,
                'sub_cat_arr' => $sssub_cat_arr,
            ];

            $mainAvarage += $catAverage;


            $mainPercentileCount += 0;
            $DD_your_total_average_score += $your_score;
            $DD_your_total_percentile_score += $yPercentileScore;
        }

        $remaining_tutorial = $total_tutorials - $total_tutorial_seen;

        if ($total_tutorial_seen > 0 && $total_tutorials > 0)
            $percentage_tutorial =  ($total_tutorial_seen * 100) / $total_tutorials;
        else
            $percentage_tutorial = '0';

        $totalLessions = Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['status' => 1])->get()->count();

        $req_data['total_tutorials'] = $totalLessions;
        $req_data['seen_tutorial'] = $total_tutorial_seen;
        $req_data['remaining_tutorial'] = $remaining_tutorial;
        $req_data['percentage_tutorial'] = $percentage_tutorial;
        $req_data['tutorial_category_data'] = $catTutArr;

        $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

        $my_total_attempt = (int)$my_total_attempt;
        $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

        if ($my_total_attempt > 0 && $totalQuestionsNoCategory > 0)
            $your_percentile =  ($my_total_attempt * 100) / $totalQuestionsNoCategory;
        else
            $your_percentile = '0';

        if ($my_total_attempt > 0 && $aall_my_total_attempt > 0)
            $your_total_average_score = $my_total_attempt * 100 / $aall_my_total_attempt;
        else
            $your_total_average_score = '0';

        $req_data['my_total_attempt'] = $my_total_attempt;
        $req_data['total_questions'] = $totalQuestionsNoCategory;


        $DD_your_total_average_score += $your_score;
        $DD_your_total_percentile_score += $yPercentileScore;

        $tttAverageCount = 0;
        $tttPercentCount = 0;
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttAverageCount = $DD_your_total_average_score / count($getCatgory);
        }
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttPercentCount = $DD_your_total_percentile_score / count($getCatgory);
        }

        $final = 0;
        if ($outSideTotalCorrect > 0 && $outSideTotalAttempt > 0) {
            $final = ($outSideTotalCorrect / $outSideTotalAttempt) * 100;
        }
        $percentile = $this->percentileCalculation(0, $final, $user_id, $course_id);

        $req_data['your_total_average_score'] = round($final, 2);
        $req_data['your_percentile'] = round($percentile);



        $req_data['question_category_data'] = $catQueArr;

        $check_TipsDisplay = TipsDisplay::where('course_id', $course_id)->where('display_date', date('Y-m-d'))->first();
        if (empty($check_TipsDisplay)) {
            $get_exists_tips = TipsDisplay::where('course_id', $course_id)->pluck('tip_id');
            $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->whereNotIn('id', $get_exists_tips)->get();
            if (count($getTipDt) > 0) {
                $getTipDt = $getTipDt->random(1);
            } else {
                $delete_TipsDisplay = TipsDisplay::where('course_id', $course_id)->delete();
                $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->get();
            }

            if (!empty($getTipDt[0])) {
                $create_TipsDisplay_param['tip_id'] = $getTipDt[0]['id'];
                $create_TipsDisplay_param['display_date'] = date('Y-m-d');
                $create_TipsDisplay_param['course_id'] = $course_id;
                $create_TipsDisplay = TipsDisplay::create($create_TipsDisplay_param);
            }
        } else {
            $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->where('id', $check_TipsDisplay->tip_id)->get();
        }

        $tip_dt = (count($getTipDt) > 0) ? $getTipDt->toArray() : [];

        $getSupport = PersonalSupport::where('course_id', $course_id)->first();
        $support_dt = (isset($getSupport->id)) ? $getSupport->toArray() : [];
        $userDetail = User::find($user_id);

        $req_data['tips_data'] = $tip_dt;
        $req_data['personal_support_data'] = $support_dt;
        $req_data['userData'] = $userDetail;
        if (count($getCourse)) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }

    public function courseDetailLogicPerformance($course_id, $user_id)
    {
        $courseDetailsRow = Course::find($course_id);

        if (empty($courseDetailsRow)) {
            return array("data" => array());
        }

        $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
        $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];

        $packCOunt = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->count();

        $is_package_purchased = ($packCOunt > 0) ? '1' : '0';

        $remaining_exam_days = '';

        if (!empty($course_exam_date)) {
            if (date('Y-m-d') <= $course_exam_date) {
                $fdate = date('Y-m-d');
                $tdate = $course_exam_date;
                $datetime1 = new DateTime($fdate);
                $datetime2 = new DateTime($tdate);
                $interval = $datetime1->diff($datetime2);
                $remaining_exam_days = $interval->format('%a');
            } else if (date('Y-m-d') > $course_exam_date) {
                $remaining_exam_days = '';
            } else {
                $remaining_exam_days = '';
            }
        }

        $popupBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 1])->count();
        $popupBannerBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 2])->count();

        $is_popup_block = ($popupBlockCount > 0) ? '1' : '0';
        $is_banner_block = ($popupBannerBlockCount > 0) ? '1' : '0';

        $req_data['is_popup_block'] = $is_popup_block;
        $req_data['is_banner_block'] = $is_banner_block;
        $req_data['is_question_number'] = @$courseDetailsRow->is_tutorial == 1 && $courseDetailsRow->is_test == 0 && $courseDetailsRow->is_question == 0 ? 0 : 1;

        $req_data['is_package_purchased'] = $is_package_purchased;
        $req_data['course_exam_date'] = $course_exam_date;
        $req_data['remaining_exam_days'] = $remaining_exam_days;

        $getCourseDt = Course::orderBy('id', 'desc')->where('id', $course_id)->first(['id', 'course_name', 'course_image', 'video_image', 'banner_content', 'banner_link', 'popup_content', 'popup_course_image', 'popup_link']);
        $getCourse = (isset($getCourseDt->id)) ? $getCourseDt->toArray() : [];
        $req_data['course_detail'] = $getCourse;

        $getLastWatchedDt =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.user_id' => $user_id])->first(['tutorial_tbl.id', 'tutorial_tbl.category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $purchased_plans_users_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1'])->pluck('order_tbl.user_id')->toArray();

        $total_tutorials = '0';
        $total_tutorial_seen = '0';
        $my_total_attempt = '0';
        $catTutArr = [];
        $catQueArr = [];

        $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : []);

        if (isset($request->category_id)) {
            $catQue->where('id', $request->category_id);
        }

        $getCatgory = $catQue->orderBy('sort', 'asc')->get();

        $aall_my_total_attempt = '0';
        $totalLessions = 0;
        $mainAvarage = 0;
        $mainPercentileCount = 0;
        $totalAttemptCategoryList = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get();
        $totalAttemptCategory = array();
        foreach ($totalAttemptCategoryList as $val) {
            $totalAttemptCategory[$val->category_id] = $val->category_id;
        }

        $outSideTotalQuestion = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get()->count();
        $outSideTotalCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->get()->count();
        $outSideTotalIncorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '0'])->get()->count();
        $totalquestionofcourse =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereIn("category_id", $totalAttemptCategory)->get()->count();


        $outSideTotalAttempt = $outSideTotalCorrect + $outSideTotalIncorrect;

        $outSideAverage = $outSideTotalQuestion - (($outSideTotalIncorrect + $outSideTotalCorrect) - $outSideTotalCorrect);
        $outSideTotalAverageCal =  $this->getUserWisePercentage($purchased_plans_users_arr, $user_id, $course_id, true, $outSideAverage, '', true);

        $allQuestionAtStatus  = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->first();


        $DD_your_total_average_score = 0;
        $DD_your_total_percentile_score = 0;

        foreach ($getCatgory as $catDt) {
            $totalTutorialsQue =  Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id, 'status' => 1]);

            $seenedTutorialsQue =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.category_id' => $catDt->id, 'watched_tutorial.user_id' => $user_id]);

            if ($is_package_purchased > 0) {
                $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $buyTutIdArr);
            } else {
                $testModeTutorialId = AssignTutorial::where('course_id', $course_id)->pluck('tutorial_id')->toArray();

                $totalTutorialsQue->whereIn('id', $testModeTutorialId);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $testModeTutorialId);
            }

            $totalTutorials = $totalTutorialsQue->get()->count();
            $seenedTutorials = $seenedTutorialsQue->get()->count();

            $catTutArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'total_tutorials' => !empty($is_package_purchased) ? $totalTutorials : 0,
                'tutorial_seen_by_me' => !empty($is_package_purchased) ? $seenedTutorials : 0,
            ];
            $total_tutorials = $total_tutorials + $totalTutorials;
            $total_tutorial_seen = $total_tutorial_seen + $seenedTutorials;

            $totalQuestionsAll =  QuestionAnswer::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
            $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

            $totalAllUserAttemptCorrect = AttemptQuestion::where(['category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();

            $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;

            $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;
            $totalQuestions = (int)$totalQuestions;

            $totalAllAttemptQuestions = (int)$totalAllAttemptQuestions;

            if ($totalAllUserAttemptCorrect > 0 && $totalAllAttemptQuestions > 0)
                $mm_score =  ($totalAllUserAttemptCorrect * 100) / $totalAllAttemptQuestions;
            else
                $mm_score = '0';

            $aall_my_total_attempt = $aall_my_total_attempt + $totalAllAttemptQuestions;

            $my_total_attempt = $my_total_attempt + $totalMyAttemptCorrect;


            $totalQuestionsWithouAttempt = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id])->get()->count();

            $your_score = '0';
            if ($totalMyAttemptCorrect > 0 && $totalQuestions > 0) {
                $your_score =  ($totalMyAttemptCorrect / $totalQuestions) * 100;
            }

            // your percentile category
            $yPercentileScore = 0;
            if ($totalMyAttemptCorrect > 0) {
                $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptCorrect + $totalMyAttemptInCorrect)) * 100;
            }

            $sssub_cat_arr = [];

            // My Logic ///
            $catAverage = $totalQuestions - (($totalMyAttemptInCorrect + $totalMyAttemptCorrect) - $totalMyAttemptCorrect);

            if ($totalMyAttemptInCorrect > 0 || $totalMyAttemptInCorrect > 0) {
                $catAverage = ($totalMyAttemptCorrect * 100) / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect);
            }

            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();

            foreach ($getSubCat as $subCatDt) {

                $sub_totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->get()->count();
                $sub_totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->get()->count();


                $sub_totalAllUserAttemptCorrect = AttemptQuestion::whereIn('user_id', $purchased_plans_users_arr)
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])
                    ->get()->count();

                $totalQuestionsWithouAttemptSub = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $subAverage = '0';
                if ($totalMyAttemptCorrect > 0 && $totalQuestionsWithouAttemptSub > 0) {
                    /// $your_score =  ($totalMyAttemptCorrect/$totalQuestionsAll)/100;
                    $subAverage = ($sub_totalMyAttemptCorrect / ($totalQuestionsWithouAttemptSub)) * 100;
                }


                // your percentile category
                $subPercentileScore = 0;
                if ($sub_totalMyAttemptCorrect > 0) {
                    $subPercentileScore = ($sub_totalMyAttemptCorrect / ($totalMyAttemptCorrect + $sub_totalMyAttemptInCorrect)) * 100;
                }

                $subPercentileScore =   !empty($subPercentileScore) ? $subPercentileScore : 0;

                $sub_totalMyAttemptCorrect = (int)$sub_totalMyAttemptCorrect;

                $sub_totalAllUserAttemptCorrect = (int)$sub_totalAllUserAttemptCorrect;
                $sub_totalQuestions = (int)$sub_totalQuestions;

                $sub_totalAllAttemptQuestions = (int)$sub_totalAllAttemptQuestions;

                if ($sub_totalAllUserAttemptCorrect > 0 && $sub_totalAllAttemptQuestions > 0)
                    $sub_mm_score =  ($sub_totalAllUserAttemptCorrect * 100) / $sub_totalAllAttemptQuestions;
                else
                    $sub_mm_score = '0';

                $my_total_attempt = $my_total_attempt + $sub_totalMyAttemptCorrect;

                if ($sub_totalMyAttemptCorrect > 0 && $sub_totalQuestions > 0)
                    $sub_your_score =  ($sub_totalMyAttemptCorrect * 100) / $sub_totalQuestions;
                else
                    $sub_your_score = '0';

                $sssub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'mm_score' => !empty($is_package_purchased) ? $subPercentileScore : 0,
                    'your_score' => !empty($is_package_purchased) ? $subAverage : 0,
                ];
            }


            $avgAllUserCategoryWise = 0;
            if ($totalAllAttemptQuestions > 0 && $totalAllUserAttemptCorrect > 0) {
                $avgAllUserCategoryWise = ($totalAllUserAttemptCorrect / $totalAllAttemptQuestions) * 100;
            }

            $avgAllUserCategoryWise = round($avgAllUserCategoryWise);
            $catQueArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'sort_category_name' => $catDt->short_name,
                'mm_score' => !empty($is_package_purchased) ? $avgAllUserCategoryWise : 0,
                'your_score' => !empty($is_package_purchased) ? $your_score : 0,
                'sub_cat_arr' => $sssub_cat_arr,
            ];

            $mainAvarage += $catAverage;


            $mainPercentileCount += 0;
            $DD_your_total_average_score += $your_score;
            $DD_your_total_percentile_score += $yPercentileScore;
        }

        $remaining_tutorial = $total_tutorials - $total_tutorial_seen;

        if ($total_tutorial_seen > 0 && $total_tutorials > 0)
            $percentage_tutorial =  ($total_tutorial_seen * 100) / $total_tutorials;
        else
            $percentage_tutorial = '0';


        $totalLessions = Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['status' => 1])->get()->count();

        $req_data['total_tutorials'] = $totalLessions;
        $req_data['seen_tutorial'] = $total_tutorial_seen;
        $req_data['remaining_tutorial'] = $remaining_tutorial;
        $req_data['percentage_tutorial'] = $percentage_tutorial;
        $req_data['tutorial_category_data'] = $catTutArr;

        // TODO - get total quetions and correct questions
        $totalQuestions = 0;
        $totalAttemptedQuestions = 0;
        $totalAttemptedQuestionsByMonth = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $course = Course::find($course_id);

        $categoryIds = $course?->categories ? explode(',', $course->categories) : [];

        $categories = Category::whereIn('id', $categoryIds)->where('status', 1)->get();

        foreach ($categories as $category) {
            $subCategories = SubCategory::where(['category_id' => $category->id, 'status' => 1])->get();

            foreach ($subCategories as $subCategory) {
                $subCategoryQuestions = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')
                    ->where(['category_id' => $category->id, 'sub_category_ids' => $subCategory->id])->get()->count();

                $subCategoryAttemptedQuestionsQuery = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $category->id, 'sub_category_ids' => $subCategory->id]);

                for ($i = 0; $i <= 11; $i++) {
                    $subCategoryAttemptedQuestionsByMonthQuery = clone $subCategoryAttemptedQuestionsQuery;

                    $subCategoryAttemptedQuestionsByMonthQuery->whereRaw('MONTH(created_at) = ' . $i + 1);

                    $totalAttemptedQuestionsByMonth[$i] += $subCategoryAttemptedQuestionsByMonthQuery->count();
                }

                $subCategoryAttemptedQuestions = $subCategoryAttemptedQuestionsQuery->count();

                $totalQuestions += $subCategoryQuestions;
                $totalAttemptedQuestions += $subCategoryAttemptedQuestions;
            }
        }

        // TODO - get total and correct questions from mocktest
        $totalMockQuestionsAttempted = AttemptMocktestQuestion::where(['user_id' => $user_id])->get()->count();

        $totalCorrectMockQuestions = AttemptMocktestQuestion::where(['user_id' => $user_id, 'is_correct' => 1])->get()->count();

        // TODO - get mock test data

        // get all the ids of mock tests whose question has been attempted
        $mockTests = AttemptMocktestQuestion::leftJoin('mocktests', 'attempt_mocktest_questions.mocktest_id', '=', 'mocktests.id')->select('mocktests.id', 'mocktests.name')->where(['user_id' => $user_id, 'course_id' => $course_id])->groupBy('mocktest_id')->get();

        $mockTestIds = $mockTests->pluck('id');

        $mockTestResults = [];

        // fetch the mock test data from the ids
        foreach ($mockTestIds as $index => $mockTestId) {
            // get the category ids of the mock test
            $mockTestCategoryIds = MocktestCategory::where("mocktest_id", $mockTestId)->pluck("category_id");

            // get the category data
            $mockTestCategories = Category::whereIn('id', $mockTestCategoryIds)->orderBy('sort', 'asc')->get();

            foreach ($mockTestCategories as $category) {
                $totalQuestionsAttempted = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mockTestId, 'category_id' => $category->id])->get()->count();

                if ($totalQuestionsAttempted == 0) {
                    continue;
                }

                // find the total questions in each category
                $totalQuestionsInMockCategory = AssingQuestionMocktest::where(["category_id" => $category->id, "mocktest_id" => $mockTestId])->get()->count();

                $totalQuestionsCorrect = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mockTestId, 'category_id' => $category->id, 'is_correct' => '1'])->get()->count();

                $percentageScore = round(($totalQuestionsCorrect / $totalQuestionsInMockCategory) * 100);

                $ucatScore = CategoryUcatScore::where("category_id", $category->id)->where("min_score", '<=', $percentageScore)->where("max_score", '>=', $percentageScore)->where("course_type_id", $courseDetailsRow->course_type_id)->first();

                // average of all users in the category
                $totalQuestionsAttemptedByAllUsers = AttemptMocktestQuestion::where(['mocktest_id' => $mockTestId, 'category_id' => $category->id])->get()->count();

                $totalQuestionsCorrectByAllUsers = AttemptMocktestQuestion::where(['mocktest_id' => $mockTestId, 'category_id' => $category->id, 'is_correct' => '1'])->get()->count();

                $avgOfAllUsers = ($totalQuestionsCorrectByAllUsers / $totalQuestionsAttemptedByAllUsers) * 100;

                $mockTestResults[$mockTestId][] = [
                    'category_id' => $category->id,
                    'category_name' => $category->category_name,
                    'avg_of_all_users' => round($avgOfAllUsers),
                    'percentage_score' => $percentageScore,
                    'correct' => $totalQuestionsCorrect,
                    'total_questions' => $totalQuestionsInMockCategory,
                    'ucat_score' => @$ucatScore->score ? $ucatScore->score : 0,
                ];
            }
        }

        $req_data['mockTestResults'] = $mockTestResults;

        $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

        $my_total_attempt = (int)$my_total_attempt;
        $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

        if ($my_total_attempt > 0 && $totalQuestionsNoCategory > 0)
            $your_percentile =  ($my_total_attempt * 100) / $totalQuestionsNoCategory;
        else
            $your_percentile = '0';

        if ($my_total_attempt > 0 && $aall_my_total_attempt > 0)
            $your_total_average_score = $my_total_attempt * 100 / $aall_my_total_attempt;
        else
            $your_total_average_score = '0';

        $req_data['my_total_attempt'] = $my_total_attempt;
        $req_data['total_questions'] = $totalQuestionsNoCategory;


        $DD_your_total_average_score += $your_score;
        $DD_your_total_percentile_score += $yPercentileScore;

        $tttAverageCount = 0;
        $tttPercentCount = 0;
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttAverageCount = $DD_your_total_average_score / count($getCatgory);
        }
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttPercentCount = $DD_your_total_percentile_score / count($getCatgory);
        }

        $final = 0;
        if ($outSideTotalCorrect > 0 && $outSideTotalAttempt > 0) {
            $final = ($outSideTotalCorrect / $outSideTotalAttempt) * 100;
        }

        $newCategory = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->distinct()->count('category_id');
        $newScore = $tttPercentCount * count($getCatgory);
        if ($newScore > 0) {
            $newScore = $newScore / $newCategory;
        }

        $percentile = $this->percentileCalculation(0, $newScore, $user_id, $course_id);

        $req_data['your_total_average_score'] = round($final, 2);
        $req_data['your_percentile'] = $percentile;



        $req_data['question_category_data'] = $catQueArr;

        $check_TipsDisplay = TipsDisplay::where('course_id', $course_id)->where('display_date', date('Y-m-d'))->first();
        if (empty($check_TipsDisplay)) {
            $get_exists_tips = TipsDisplay::where('course_id', $course_id)->pluck('tip_id');
            $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->whereNotIn('id', $get_exists_tips)->get();
            if (count($getTipDt) > 0) {
                $getTipDt = $getTipDt->random(1);
            } else {
                $delete_TipsDisplay = TipsDisplay::where('course_id', $course_id)->delete();
                $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->get();
            }

            if (!empty($getTipDt[0])) {
                $create_TipsDisplay_param['tip_id'] = $getTipDt[0]['id'];
                $create_TipsDisplay_param['display_date'] = date('Y-m-d');
                $create_TipsDisplay_param['course_id'] = $course_id;
                $create_TipsDisplay = TipsDisplay::create($create_TipsDisplay_param);
            }
        } else {
            $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->where('id', $check_TipsDisplay->tip_id)->get();
        }

        $tip_dt = (count($getTipDt) > 0) ? $getTipDt->toArray() : [];

        $getSupport = PersonalSupport::where('course_id', $course_id)->first();
        $support_dt = (isset($getSupport->id)) ? $getSupport->toArray() : [];
        $userDetail = User::find($user_id);

        $req_data['tips_data'] = $tip_dt;
        $req_data['personal_support_data'] = $support_dt;
        $req_data['userData'] = $userDetail;

        $req_data['total_questions'] = $totalQuestions;
        $req_data['total_attempted_questions'] = $totalAttemptedQuestions;
        $req_data['totalAttemptedQuestionsByMonth'] = $totalAttemptedQuestionsByMonth;

        $req_data['total_mock_questions_attempted'] = $totalMockQuestionsAttempted;
        $req_data['total_correct_mock_questions'] = $totalCorrectMockQuestions;

        if (count($getCourse)) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }
    public function get_course_detailOnTotalQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'course_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        }


        $course_id = $request->course_id;
        $user_id = $request->user_id;
        $courseDetailsRow = Course::find($course_id);
        $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
        $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];

        $packCOunt = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id, 'order_detail.particular_record_id' => $course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->count();

        $is_package_purchased = ($packCOunt > 0) ? '1' : '0';
        $remaining_exam_days = '';
        if (!empty($course_exam_date)) {
            if (date('Y-m-d') <= $course_exam_date) {
                $fdate = date('Y-m-d');
                $tdate = $course_exam_date;
                $datetime1 = new DateTime($fdate);
                $datetime2 = new DateTime($tdate);
                $interval = $datetime1->diff($datetime2);
                $remaining_exam_days = $interval->format('%a');
            } else if (date('Y-m-d') > $course_exam_date) {
                $remaining_exam_days = '';
            } else {
                $remaining_exam_days = '';
            }
        }

        $popupBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 1])->count();
        $popupBannerBlockCount = BlockPopup::where(['user_id' => $user_id, 'course_id' => $course_id, 'type' => 2])->count();

        $is_popup_block = ($popupBlockCount > 0) ? '1' : '0';
        $is_banner_block = ($popupBannerBlockCount > 0) ? '1' : '0';

        $req_data['is_popup_block'] = $is_popup_block;
        $req_data['is_banner_block'] = $is_banner_block;

        $req_data['is_package_purchased'] = $is_package_purchased;
        $req_data['course_exam_date'] = $course_exam_date;
        $req_data['remaining_exam_days'] = $remaining_exam_days;

        $getCourseDt = Course::orderBy('id', 'desc')->where('id', $course_id)->first(['id', 'course_name', 'course_image', 'video_image', 'banner_content', 'banner_link', 'popup_content', 'popup_course_image', 'popup_link']);
        $getCourse = (isset($getCourseDt->id)) ? $getCourseDt->toArray() : [];
        $req_data['course_detail'] = $getCourse;

        $getLastWatchedDt =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.user_id' => $user_id])->first(['tutorial_tbl.id', 'tutorial_tbl.category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $purchased_plans_users_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1'])->pluck('order_tbl.user_id')->toArray();

        $total_tutorials = '0';
        $total_tutorial_seen = '0';
        $my_total_attempt = '0';
        $catTutArr = [];
        $catQueArr = [];

        $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : []);
        if (isset($request->category_id)) {
            $catQue->where('id', $request->category_id);
        }
        $getCatgory = $catQue->orderBy('sort', 'asc')->get();
        $category_count = $catQue->count();

        $aall_my_total_attempt = '0';
        $totalLessions = 0;
        $mainAvarage = 0;
        $mainPercentileCount = 0;
        $totalAttemptCategoryList = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get();
        $totalAttemptCategory = array();
        foreach ($totalAttemptCategoryList as $val) {
            $totalAttemptCategory[$val->category_id] = $val->category_id;
        }

        $outSideTotalQuestion = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->get()->count();
        $outSideTotalCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->get()->count();
        $outSideTotalIncorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '0'])->get()->count();
        $totalquestionofcourse =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereIn("category_id", $totalAttemptCategory)->get()->count();


        $outSideTotalAttempt = $outSideTotalCorrect + $outSideTotalIncorrect;

        $outSideAverage = $outSideTotalQuestion - (($outSideTotalIncorrect + $outSideTotalCorrect) - $outSideTotalCorrect);
        $outSideTotalAverageCal =  $this->getUserWisePercentage($purchased_plans_users_arr, $user_id, $course_id, true, $outSideAverage, '', true);

        $allQuestionAtStatus  = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => '1'])->first();


        $DD_your_total_average_score = 0;
        $DD_your_total_percentile_score = 0;
        foreach ($getCatgory as $catDt) {

            $totalTutorialsQue =  Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id, 'status' => 1]);


            $seenedTutorialsQue =  WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->where(['watched_tutorial.course_id' => $course_id, 'watched_tutorial.category_id' => $catDt->id, 'watched_tutorial.user_id' => $user_id]);

            if ($is_package_purchased > 0) {
                $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $buyTutIdArr);
            } else {
                $testModeTutorialId = AssignTutorial::where('course_id', $course_id)->pluck('tutorial_id')->toArray();

                $totalTutorialsQue->whereIn('id', $testModeTutorialId);
                $seenedTutorialsQue->whereIn('tutorial_tbl.id', $testModeTutorialId);
            }
            $totalTutorials = $totalTutorialsQue->get()->count();
            $seenedTutorials = $seenedTutorialsQue->get()->count();

            $catTutArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'total_tutorials' => !empty($is_package_purchased) ? $totalTutorials : rand(11, 99),
                'tutorial_seen_by_me' => !empty($is_package_purchased) ? $seenedTutorials : rand(11, 99),
            ];
            $total_tutorials = $total_tutorials + $totalTutorials;
            $total_tutorial_seen = $total_tutorial_seen + $seenedTutorials;

            $totalQuestionsAll =  QuestionAnswer::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
            $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

            $totalAllUserAttemptCorrect = AttemptQuestion::whereIn('user_id', $purchased_plans_users_arr)->where(['category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();

            $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;

            $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;
            $totalQuestions = (int)$totalQuestions;

            $totalAllAttemptQuestions = (int)$totalAllAttemptQuestions;

            if ($totalAllUserAttemptCorrect > 0 && $totalAllAttemptQuestions > 0)
                $mm_score =  ($totalAllUserAttemptCorrect * 100) / $totalAllAttemptQuestions;
            else
                $mm_score = '0';

            $aall_my_total_attempt = $aall_my_total_attempt + $totalAllAttemptQuestions;

            $my_total_attempt = $my_total_attempt + $totalMyAttemptCorrect;


            $totalQuestionsWithouAttempt = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id])->get()->count();

            $your_score = '0';
            if ($totalMyAttemptCorrect > 0 && $totalQuestionsAll > 0) {
                $your_score =  ($totalMyAttemptCorrect / $totalQuestionsAll) * 100;
                // $your_score = ($totalMyAttemptCorrect/($totalQuestionsWithouAttempt))*100;
            }

            // your percentile category
            $yPercentileScore = 0;
            if ($totalMyAttemptCorrect > 0) {
                $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptCorrect + $totalMyAttemptInCorrect)) * 100;
            }

            $sssub_cat_arr = [];

            $catAverage = $totalQuestions - (($totalMyAttemptInCorrect + $totalMyAttemptCorrect) - $totalMyAttemptCorrect);

            if ($totalMyAttemptInCorrect > 0 || $totalMyAttemptInCorrect > 0) {
                $catAverage = ($totalMyAttemptCorrect * 100) / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect);
            }

            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();
            foreach ($getSubCat as $subCatDt) {

                $sub_totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalAllAttemptQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $sub_totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->get()->count();
                $sub_totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->get()->count();


                $sub_totalAllUserAttemptCorrect = AttemptQuestion::whereIn('user_id', $purchased_plans_users_arr)
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])
                    ->get()->count();



                $totalQuestionsWithouAttemptSub = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')
                    ->where(['category_id' => $catDt->id, 'sub_category_ids' => $subCatDt->id])->get()->count();

                $subAverage = '0';
                if ($totalMyAttemptCorrect > 0 && $totalQuestionsWithouAttemptSub > 0) {
                    /// $your_score =  ($totalMyAttemptCorrect/$totalQuestionsAll)/100;
                    $subAverage = ($sub_totalMyAttemptCorrect / ($totalQuestionsWithouAttemptSub)) * 100;
                }


                // your percentile category
                $subPercentileScore = 0;
                if ($sub_totalMyAttemptCorrect > 0) {
                    $subPercentileScore = ($sub_totalMyAttemptCorrect / ($totalMyAttemptCorrect + $sub_totalMyAttemptInCorrect)) * 100;
                }

                $subPercentileScore =   !empty($subPercentileScore) ? $subPercentileScore : 0;


                $sub_totalMyAttemptCorrect = (int)$sub_totalMyAttemptCorrect;

                $sub_totalAllUserAttemptCorrect = (int)$sub_totalAllUserAttemptCorrect;
                $sub_totalQuestions = (int)$sub_totalQuestions;

                $sub_totalAllAttemptQuestions = (int)$sub_totalAllAttemptQuestions;

                if ($sub_totalAllUserAttemptCorrect > 0 && $sub_totalAllAttemptQuestions > 0)
                    $sub_mm_score =  ($sub_totalAllUserAttemptCorrect * 100) / $sub_totalAllAttemptQuestions;
                else
                    $sub_mm_score = '0';

                $my_total_attempt = $my_total_attempt + $sub_totalMyAttemptCorrect;

                if ($sub_totalMyAttemptCorrect > 0 && $sub_totalQuestions > 0)
                    $sub_your_score =  ($sub_totalMyAttemptCorrect * 100) / $sub_totalQuestions;
                else
                    $sub_your_score = '0';

                $sssub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'mm_score' => !empty($is_package_purchased) ? $subPercentileScore : rand(11, 99),
                    'your_score' => !empty($is_package_purchased) ? $subAverage : rand(11, 99),
                ];
            }


            $totaluserCount = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->groupBy("user_id")->get()->count();
            $totalquestionuserwise = $totalQuestionsWithouAttempt * $totaluserCount;
            $avgAllUserCategoryWise = 0;
            if ($totalquestionuserwise > 0 && $totalAllUserAttemptCorrect > 0) {
                $avgAllUserCategoryWise = ($totalAllUserAttemptCorrect / $totalquestionuserwise) * 100;
            }


            $catQueArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'sort_category_name' => $catDt->short_name,
                'mm_score' => !empty($is_package_purchased) ? $avgAllUserCategoryWise : rand(11, 99),
                'your_score' => !empty($is_package_purchased) ? $your_score : rand(11, 99),
                'sub_cat_arr' => $sssub_cat_arr,
            ];

            $mainAvarage += $catAverage;


            $mainPercentileCount += 0; //!empty($catPercentileCount)?$catPercentileCount:0;
            $DD_your_total_average_score += $your_score;
            $DD_your_total_percentile_score += $yPercentileScore;
        }

        $remaining_tutorial = $total_tutorials - $total_tutorial_seen;

        if ($total_tutorial_seen > 0 && $total_tutorials > 0)
            $percentage_tutorial =  ($total_tutorial_seen * 100) / $total_tutorials;
        else
            $percentage_tutorial = '0';

        $totalLessions = Tutorial::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['status' => 1])->get()->count();

        $req_data['total_tutorials'] = $totalLessions;
        $req_data['seen_tutorial'] = $total_tutorial_seen;
        $req_data['remaining_tutorial'] = $remaining_tutorial;
        $req_data['percentage_tutorial'] = $percentage_tutorial;
        $req_data['tutorial_category_data'] = $catTutArr;

        $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

        $my_total_attempt = (int)$my_total_attempt;
        $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

        if ($my_total_attempt > 0 && $totalQuestionsNoCategory > 0)
            $your_percentile =  ($my_total_attempt * 100) / $totalQuestionsNoCategory;
        else
            $your_percentile = '0';

        if ($my_total_attempt > 0 && $aall_my_total_attempt > 0)
            $your_total_average_score = $my_total_attempt * 100 / $aall_my_total_attempt;
        else
            $your_total_average_score = '0';

        $req_data['my_total_attempt'] = $my_total_attempt;
        $req_data['total_questions'] = $totalQuestionsNoCategory;

        $DD_your_total_average_score += $your_score;
        $DD_your_total_percentile_score += $yPercentileScore;

        $tttAverageCount = 0;
        $tttPercentCount = 0;
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttAverageCount = $DD_your_total_average_score / count($getCatgory);
        }
        if ($DD_your_total_average_score > 0 && count($getCatgory) > 0) {
            $tttPercentCount = $DD_your_total_percentile_score / count($getCatgory);
        }
        ///outSideTotalAttempt
        //$final=($outSideTotalCorrect/$totalquestionofcourse)*100;
        $final = 0;
        if ($outSideTotalCorrect > 0 && $outSideTotalAttempt > 0) {
            $final = ($outSideTotalCorrect / $totalquestionofcourse) * 100;
        }


        $req_data['your_total_average_score'] = round($final, 2);
        $req_data['your_percentile'] = round($tttPercentCount, 2);
        // if(!empty($outSideTotalAverageCal))
        // {

        // }else{
        //     if(!empty($allQuestionAtStatus))
        //     {
        //         $req_data['your_percentile'] = 100;    
        //     }else{
        //         $req_data['your_percentile'] = 0;    
        //     }
        // }



        $req_data['question_category_data'] = $catQueArr;

        /***********************/

        $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->whereDate('tip_date', date('Y-m-d'))->orderBy('tip_date', 'asc')->get();

        if (count($getTipDt) == 0) {
            $getTipDt = Tips::where(['course_id' => $course_id, 'type' => '1'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->get();
        }

        $tip_dt = (count($getTipDt) > 0) ? $getTipDt->toArray() : [];

        $getSupport = PersonalSupport::where('course_id', $course_id)->first();
        $support_dt = (isset($getSupport->id)) ? $getSupport->toArray() : [];

        $req_data['tips_data'] = $tip_dt;
        $req_data['personal_support_data'] = $support_dt;
        if (count($getCourse)) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function get_score_after_test_finish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $course_id = $request->course_id;
            $user_id = Auth::id();
            $my_total_attempt = '0';
            $all_user_total_attempt = '0';
            $catQueArr = [];
            $selectedCategoryData = TempBeforeFinishTest::where("user_id", $user_id)->orderBy("id", "DESC")->first();
            $selectedCategory = explode(",", $selectedCategoryData->category_ids);


            $courseDetailsRow = Course::find($course_id);
            $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [])->orderBy('sort', 'asc');
            $catQue = $catQue->whereIn('id', $selectedCategory);
            $getCatgory = $catQue->get();
            $category_count = $catQue->count();

            $my_total_attempt_que = 0;

            $get_total_avg = 0;
            $get_my_correct_question = 0;
            $ttCorrect = 0;
            $ttInCorrect = 0;
            $totalAllQuestionAttemptByMe = 0;

            $totalFinalAttemptCorrectQuestionByMe = 0;

            $ffScore_avg = 0;
            $allAvgScore_avg = 0;
            $totalPercentileResponseCount = 0;
            $totalcategory = array();
            $avgAllUserCategoryWiseTotal = 0;

            foreach ($getCatgory as $catDt) {
                $totalQuestions = TempTest::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();
                if ($totalQuestions == 0) {
                    continue;
                }
                $totalcategory[] = $catDt->id;
                $totalQuestionsWithouAttempt = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id])->get()->count();

                $totalQuestions = (int)$totalQuestions;
                $my_total_attempt_que = $my_total_attempt_que + $totalQuestions;

                $totalMyAttemptCorrect = TempTest::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalMyAttemptInCorrect = TempTest::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

                $totalAllQuestionAttemptByMe += $totalMyAttemptCorrect + $totalMyAttemptInCorrect;
                $totalFinalAttemptCorrectQuestionByMe += $totalMyAttemptCorrect;
                $totalUserQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

                $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;

                $totalAllUserAttemptCorrect = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;

                // your score 
                $yScore = 0;
                if ($totalMyAttemptCorrect > 0 && $totalQuestions > 0) {
                    $yScore = ($totalMyAttemptCorrect / ($totalQuestions)) * 100;
                }

                // average score
                $xScore_avg = 0;
                if ($totalUserQuestions > 0 && $totalAllUserAttemptCorrect > 0) {
                    $xScore_avg = ($totalAllUserAttemptCorrect / ($totalUserQuestions)) * 100;
                }
                // your percentile
                $yPercentileScore = 0;
                if ($totalMyAttemptCorrect > 0) {
                    $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect)) * 100;
                }

                if ($totalAllUserAttemptCorrect > 0 && $totalUserQuestions > 0)
                    $avg_of_all_users =  ($totalAllUserAttemptCorrect * 100) / $totalUserQuestions;
                else
                    $avg_of_all_users = '0';


                $my_total_attempt = 0;
                if ($totalAllQuestionAttemptByMe > 0 && $totalMyAttemptCorrect > 0) {
                    $my_total_attempt = ($totalMyAttemptCorrect * 100) / $totalAllQuestionAttemptByMe;
                }


                $all_user_total_attempt = $all_user_total_attempt + $totalAllUserAttemptCorrect;

                $get_total_avg = $get_total_avg +  $avg_of_all_users;
                $get_my_correct_question = $get_my_correct_question + $totalMyAttemptCorrect;

                $ttCorrect += (int) $totalMyAttemptCorrect;
                $ttInCorrect += (int) $totalMyAttemptInCorrect;

                $roundOfScore = round($yScore);
                $roundOfAllUserAvg = round($xScore_avg);
                $percentile = $this->percentileCalculation($catDt->id, $roundOfScore, $user_id, $course_id);
                Log::info('percentile' . json_encode($percentile));
                $ucatScore = CategoryUcatScore::where("category_id", $catDt->id)->where("min_score", '<=', $roundOfScore)->where("max_score", '>=', $roundOfScore)->where("course_type_id", $courseDetailsRow->course_type_id)->first();

                $avgAllUserCategoryWise = $totalUserQuestions > 0 ? ($totalAllUserAttemptCorrect / $totalUserQuestions) * 100 : 0;
                $avgAllUserCategoryWiseTotal = $avgAllUserCategoryWiseTotal + $avgAllUserCategoryWise;
                $catQueArr[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'sort_category_name' => $this->short_string_char($catDt->category_name),
                    'avg_of_all_users' => round($avgAllUserCategoryWise),
                    'total_questions' => $totalQuestions,
                    'attempt' => ($totalMyAttemptCorrect + $totalMyAttemptInCorrect),
                    'ttc' => ($totalMyAttemptCorrect),
                    'correct' => $totalMyAttemptCorrect,
                    'incorrect' => $totalMyAttemptInCorrect,
                    'my_total_correct_questions' => $roundOfScore,
                    'all_users_total_correct_questions' => $roundOfAllUserAvg,
                    'your_score_in_percent' => $roundOfScore,
                    'your_score_in_percentile' => @$percentile,
                    'total_question' => $totalQuestionsWithouAttempt,
                    'total_attempt_question' => ($totalMyAttemptCorrect + $totalMyAttemptInCorrect),
                    'ucat_score' => @$ucatScore->score ? $ucatScore->score : 0,
                    'band_name' => @$ucatScore && @$ucatScore->band ? @$ucatScore->band->name : '',
                ];

                $ffScore_avg += $yScore;
                $allAvgScore_avg += $xScore_avg;
                $totalPercentileResponseCount += $yPercentileScore;
            }

            $total_user_by_course = AttemptQuestion::where(['course_id' => $course_id])->groupBy('user_id')->get();
            $user_count_min = 0;
            foreach ($total_user_by_course as $key => $item) {
                $total_user_by_course_by_user = AttemptQuestion::where(['user_id' => $item->user_id, 'course_id' => $course_id, 'is_correct' => '1'])->groupBy('user_id')->get()->count();
                if ($get_my_correct_question > $total_user_by_course_by_user) {
                    $user_count_min = $user_count_min + 1;
                }
            }
            if ($user_count_min > 0 && count($total_user_by_course) > 0) {
                $you_perform_better_then = ($user_count_min / count($total_user_by_course)) * 100;
            } else {
                $you_perform_better_then = 0;
            }

            $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

            $my_total_attempt = (int)$my_total_attempt;
            $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;


            $your_total_average_score =  $category_count > 0 ? $my_total_attempt * 100 / $category_count : 0;

            if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
                $your_total_average_score =  ($my_total_attempt * 100) / $my_total_attempt_que;
            else
                $your_total_average_score = '0';

            // Your Percentile
            $fnYourPercentile = 0;
            if (count($getCatgory) > 0 && $totalPercentileResponseCount > 0) {
                $fnYourPercentile = $totalPercentileResponseCount / count($getCatgory);
            }

            $totalCategoryQuestion = TempTest::whereIn("category_id", $totalcategory)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where("user_id", $user_id)->count();

            $finalScore = $totalCategoryQuestion > 0 ? ($totalFinalAttemptCorrectQuestionByMe / $totalCategoryQuestion) * 100 : 0;
            $avgFinal = $avgAllUserCategoryWiseTotal / count($totalcategory);

            $req_data['your_score'] = round($finalScore);
            $req_data['avg_score_of_all_users'] = round($avgFinal);
            $req_data['courseType'] = $courseDetailsRow->courseType->name;
            $req_data['your_total_average_score'] = round($your_total_average_score);
            $req_data['your_percentile'] = round($fnYourPercentile);
            $req_data['you_perform_better_then'] = $you_perform_better_then;
            $req_data['category_data'] = $catQueArr;

            $getAllUserRank = AttemptQuestion::select('user_id', DB::raw('Count(is_correct) as total_score'))->where(['course_id' => $course_id, 'is_correct' => '1'])->orderBy('total_score', 'desc')->groupBy('user_id')->get();
            $rank_arr = [];
            foreach ($getAllUserRank as $rnk_val) {
                if (isset($rnk_val['total_score'])) {
                    $rank_arr[$rnk_val['user_id']] = $rnk_val['total_score'];
                }
            }

            $user_rank = (count($rank_arr) > 0 && isset($rank_arr[$user_id])) ? $rank_arr[$user_id] : '0';
            $req_data['user_rank'] = $user_rank;

            if (count($getCatgory)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }

    public function percentileCalculation($categoryId = 0, $scoreInPercent = 0, $user_id = 0, $course_id = 0)
    {
        $attemptQuestion = AttemptQuestion::where('course_id', $course_id);

        if ($categoryId > 0) {
            $attemptQuestion = $attemptQuestion->where("category_id", $categoryId);
        }

        $attemptQuestion = $attemptQuestion->groupBy('user_id')->selectRaw('
        SUM(is_correct=1) AS correctQuestion, 
        SUM(is_correct=0) AS incorrectQuestion,
        COUNT(*) AS totalAttempted,
        user_id')
            ->get();

        if (!empty($attemptQuestion)) {
            $attemptQuestion = $attemptQuestion->toArray();

            $userPercent = array();
            foreach ($attemptQuestion as $key => $val) {
                $percent = $val['correctQuestion'] > 0 ? ($val['correctQuestion'] / $val['totalAttempted']) * 100 : 0;
                $userPercent[$val['user_id']] = round($percent);
            }

            $count = count(array_filter($userPercent, function ($value) use ($scoreInPercent) {
                return $value <= $scoreInPercent;
            }));

            if ($count > 0 && $scoreInPercent > 0) {
                $percentile = $count / count($userPercent) * 100;
            } else {
                $percentile = 0;
            }

            return round($percentile);
        } else {
            return 0;
        }
    }
    public function get_score_after_test_finishOnTotalQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $course_id = $request->course_id;
            $user_id = Auth::id();
            $my_total_attempt = '0';
            $all_user_total_attempt = '0';
            $catTutArr = [];
            $catQueArr = [];
            $courseDetailsRow = Course::find($course_id);
            $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [])->orderBy('sort', 'asc');

            $getCatgory = $catQue->get();
            $category_count = $catQue->count();

            $avg_score_of_all_users = 0;
            $my_total_attempt_que = 0;

            $get_total_avg = 0;
            $get_my_correct_question = 0;
            $ttCorrect = 0;
            $ttInCorrect = 0;
            $totalAllQuestionAttemptByMe = 0;

            $totalFinalAttemptCorrectQuestionByMe = 0;

            $allFinalTotalUserAttemptScore = 0;
            $tAllSumScore = 0;

            $ffScore_avg = 0;
            $allAvgScore_avg = 0;
            $percentileArray = [];
            $totalPercentileResponseCount = 0;
            $totalcategory = array();
            $avgAllUserCategoryWiseTotal = 0;

            foreach ($getCatgory as $catDt) {

                $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();
                if ($totalQuestions == 0) {
                    continue;
                }
                $totalcategory[] = $catDt->id;
                $totalQuestionsWithouAttempt = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where(['category_id' => $catDt->id])->get()->count();



                // return $totalQuestions; 
                $totalQuestions = (int)$totalQuestions;
                $my_total_attempt_que = $my_total_attempt_que + $totalQuestions;

                $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

                $totalAllQuestionAttemptByMe += $totalMyAttemptCorrect + $totalMyAttemptInCorrect;
                $totalFinalAttemptCorrectQuestionByMe += $totalMyAttemptCorrect;
                $totalUserQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();


                $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;
                // return $totalMyAttemptCorrect;
                $totalAllUserAttemptCorrect = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;

                // your score 
                $yScore = 0;
                if ($totalMyAttemptCorrect > 0 && $totalQuestionsWithouAttempt > 0) {
                    $yScore = ($totalMyAttemptCorrect / ($totalQuestionsWithouAttempt)) * 100;
                }

                // average score
                $xScore_avg = 0;
                if ($totalUserQuestions > 0 && $totalAllUserAttemptCorrect > 0) {
                    $xScore_avg = ($totalAllUserAttemptCorrect / ($totalUserQuestions)) * 100;
                }
                // your percentile
                $yPercentileScore = 0;
                $ttAtm = $totalMyAttemptCorrect + $totalMyAttemptInCorrect;
                if ($totalMyAttemptCorrect > 0) {
                    $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect)) * 100;
                }
                $percentileArray[] = $yPercentileScore;

                if ($totalAllUserAttemptCorrect > 0 && $totalUserQuestions > 0)
                    $avg_of_all_users =  ($totalAllUserAttemptCorrect * 100) / $totalUserQuestions;
                else
                    $avg_of_all_users = '0';

                $my_total_attempt = 0;
                if ($totalAllQuestionAttemptByMe > 0 && $totalMyAttemptCorrect > 0) {
                    $my_total_attempt = ($totalMyAttemptCorrect * 100) / $totalAllQuestionAttemptByMe;
                }

                $all_user_total_attempt = $all_user_total_attempt + $totalAllUserAttemptCorrect;

                if ($totalMyAttemptCorrect > 0 && $totalAllQuestionAttemptByMe > 0)
                    $your_score_in_percent =  ($totalMyAttemptCorrect * 100) / $totalAllQuestionAttemptByMe;
                else
                    $your_score_in_percent = '0';


                $get_total_avg = $get_total_avg +  $avg_of_all_users;
                $get_my_correct_question = $get_my_correct_question + $totalMyAttemptCorrect;

                $ttCorrect += (int) $totalMyAttemptCorrect;
                $ttInCorrect += (int) $totalMyAttemptInCorrect;

                $roundOfScore = round($yScore);
                $roundOfPercentile = round($yPercentileScore);
                $roundOfAllUserAvg = round($xScore_avg);
                $totaluserCount = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->groupBy("user_id")->get()->count();
                $totalquestionuserwise = $totalQuestionsWithouAttempt * $totaluserCount;
                $avgAllUserCategoryWise = $totalCategoryQuestion > 0 ? ($totalAllUserAttemptCorrect / $totalquestionuserwise) * 100 : 0;
                $avgAllUserCategoryWiseTotal = $avgAllUserCategoryWiseTotal + $avgAllUserCategoryWise;
                $catQueArr[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'sort_category_name' => $this->short_string_char($catDt->category_name),
                    'avg_of_all_users' => round($avgAllUserCategoryWise),
                    'total_questions' => $totalQuestions,
                    'attempt' => ($totalMyAttemptCorrect + $totalMyAttemptInCorrect),
                    'ttc' => ($totalMyAttemptCorrect),
                    'correct' => $totalMyAttemptCorrect,
                    'incorrect' => $totalMyAttemptInCorrect,
                    'my_total_correct_questions' => $roundOfScore,
                    'all_users_total_correct_questions' => $roundOfAllUserAvg,
                    'your_score_in_percent' => $roundOfScore,
                ];

                $ffScore_avg += $yScore;
                $allAvgScore_avg += $xScore_avg;
                $totalPercentileResponseCount += $yPercentileScore;
            }



            $yourScore = 0;

            $total_user_by_course = AttemptQuestion::where(['course_id' => $course_id])->groupBy('user_id')->get();
            $user_count_min = 0;
            foreach ($total_user_by_course as $key => $item) {
                $total_user_by_course_by_user = AttemptQuestion::where(['user_id' => $item->user_id, 'course_id' => $course_id, 'is_correct' => '1'])->groupBy('user_id')->get()->count();
                if ($get_my_correct_question > $total_user_by_course_by_user) {
                    $user_count_min = $user_count_min + 1;
                }
            }
            if ($user_count_min > 0 && count($total_user_by_course) > 0) {
                $you_perform_better_then = ($user_count_min / count($total_user_by_course)) * 100;
            } else {
                $you_perform_better_then = 0;
            }

            $get_avg_score_of_all_users = $category_count > 0 ? $get_total_avg / $category_count : 0;
            $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();

            $my_total_attempt = (int)$my_total_attempt;
            $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

            if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
                $your_percentile =  ($my_total_attempt * 100) / $my_total_attempt_que;
            else
                $your_percentile = '0';

            $your_total_average_score = $category_count > 0 ? $my_total_attempt * 100 / $category_count : 0;

            if ($all_user_total_attempt > 0 && $totalQuestionsNoCategory > 0)
                $all_user_avg_score_in_percentile =  ($all_user_total_attempt * 100) / $totalQuestionsNoCategory;
            else
                $all_user_avg_score_in_percentile = '0';

            if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
                $your_total_average_score =  ($my_total_attempt * 100) / $my_total_attempt_que;
            else
                $your_total_average_score = '0';

            $avg_score_of_all_users = $all_user_avg_score_in_percentile;

            /// Your Score ///
            $fnScore = 0;
            if (count($getCatgory) > 0 && $ffScore_avg > 0) {
                $fnScore = $ffScore_avg / count($getCatgory);
            }
            /// All User Avg Score ///
            $fnAllUserScore = 0;
            if (count($getCatgory) > 0 && $allAvgScore_avg > 0) {
                $fnAllUserScore = $allAvgScore_avg / count($getCatgory);
            }

            ///Your Percentile  ///
            $fnYourPercentile = 0;
            if (count($getCatgory) > 0 && $totalPercentileResponseCount > 0) {
                $fnYourPercentile = $totalPercentileResponseCount / count($getCatgory);
            }




            /// $totalAllQuestionAttemptByMe = 0;
            //    $allFinalUserTotalScore = 0;
            //    if($category_count > 0 && $tAllSumScore > 0)
            //    {
            //     $allFinalUserTotalScore = ($tAllSumScore/$category_count);
            //    }

            $totalCategoryQuestion = QuestionAnswer::whereIn("category_id", $totalcategory)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

            $finalScore = $totalCategoryQuestion > 0 ? ($totalFinalAttemptCorrectQuestionByMe / $totalCategoryQuestion) * 100 : 0;
            $avgFinal = $avgAllUserCategoryWiseTotal / count($totalcategory);

            $req_data['your_score'] = round($finalScore);
            $req_data['avg_score_of_all_users'] = round($avgFinal);
            $req_data['your_total_average_score'] = round($your_total_average_score);
            $req_data['your_percentile'] = round($fnYourPercentile);
            $req_data['you_perform_better_then'] = $you_perform_better_then;
            $req_data['category_data'] = $catQueArr;

            $getAllUserRank = AttemptQuestion::select('user_id', DB::raw('Count(is_correct) as total_score'))->where(['course_id' => $course_id, 'is_correct' => '1'])->orderBy('total_score', 'desc')->groupBy('user_id')->get();
            $rank_arr = [];
            $rank_i = 1;
            foreach ($getAllUserRank as $rnk_val) {
                if (isset($rnk_val['total_score'])) {
                    $rank_arr[$rnk_val['user_id']] = $rnk_val['total_score'];
                }
            }


            $user_rank = (count($rank_arr) > 0 && isset($rank_arr[$user_id])) ? $rank_arr[$user_id] : '0';
            $req_data['user_rank'] = $user_rank;


            if (count($getCatgory)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }

    public function add_examdate_to_course(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|integer',
            'exam_date' => 'required',
        ]);

        $user_id = Auth::id();
        $course_id = $request->course_id;
        $exam_date = $request->exam_date;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $is_exist = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->count();
            if ($is_exist == 0) {

                $newUser = CourseExamDate::create([
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'exam_date' => $exam_date,
                ]);
                $req_message = "Exam Date Added";
            } else {
                CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->update(['exam_date' => $exam_date]);
                $req_message = "Exam Date Updated";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_tutorial_bookmark(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'tutorial_id' => 'required|integer',
        ]);

        $user_id = Auth::id();
        $tutorial_id = $request->tutorial_id;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $is_exist = Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutorial_id])->count();
            if ($is_exist == 0) {

                $newUser = Bookmark::create([
                    'user_id' => $user_id,
                    'tutorial_id' => $tutorial_id,
                ]);
                $req_message = "Tutorial added";
            } else {
                Bookmark::where(['user_id' => $user_id, 'tutorial_id' => $tutorial_id])->delete();
                $req_message = "Tutorial removed";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_question_for_review(Request $request)
    {
        $user_id = Auth::id();
        $all_question_id = $request->all_question_id ?? "";
        $question_id = $request->question_id;
        $courseId = $request->courseId;
        $categoryIds = $request->categoryIds;
        $isPractice = $request->isPractice ? $request->isPractice : 0;

        if (!empty($courseId) && !empty($categoryIds)) {
            $allCategory = explode(",", $categoryIds);
            // print_r($allCategory); exit;
            foreach ($allCategory as $val) {
                /*  if(!empty($val['subcategory'])){
                    $subcategorys=explode(",",$val['subcategory']);
    
                    foreach( $subcategorys as $val1)
                    {
                        AttemptQuestion::where(['user_id' => $user_id,"course_id"=>$courseId,"category_id"=>$val['category_id'],"sub_category_ids"=>$val1])->delete();
                    }
                   
                }
                else{ */
                // AttemptQuestion::where(['user_id' => $user_id, "course_id" => $courseId, "category_id" => $val])->delete();
                /*  } */
            }
            TempTest::where(['user_id' => $user_id])->delete();
            TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
            TempSrQuestion::where(['user_id' => $user_id])->delete();
        }






        $req_data = [];

        $req_message = "";
        $checked_status = '';
        $get_dt = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
        if (isset($get_dt->id)) {

            $reviewed_ques_id = $get_dt->question_for_review;
            $question_id = $request->question_id;

            $crrArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

            if (in_array($question_id, $crrArr)) {
                unset($crrArr[array_search($question_id, $crrArr)]);
                $crrArr = array_values($crrArr);
                $req_message = "Question removed from review";
                $checked_status = '0';
            } else {
                $crrArr[] = $question_id;
                $req_message = "Question added for review";
                $checked_status = '1';
            }
            $srv = array_unique($crrArr);

            $insert_srv = (count($srv) > 0) ? implode(',', $srv) : "";

            $req_data['is_checked'] = $checked_status;
            $addArr = ['question_for_review' => $insert_srv, 'category_ids' => $categoryIds];
            TempBeforeFinishTest::where(['user_id' => $user_id])->update($addArr);
        } else {
            $crrArr = [];
            $crrArr[] = $question_id;
            $req_message = "Question added for review";
            $srv = array_unique($crrArr);

            $insert_srv = (count($srv) > 0) ? implode(',', $srv) : "";
            $addArr = ['user_id' => $user_id, 'questions_id' => $all_question_id, 'question_for_review' => $insert_srv, 'category_ids' => $categoryIds, "is_practice" => $isPractice];
            TempBeforeFinishTest::insert($addArr);
        }


        // TempSrQuestion::where(['user_id' => $user_id])->delete();

        if ($all_question_id != "") {

            $queArr = explode(',', $all_question_id);
            $queArr = explode(',', $all_question_id);
            sort($queArr);
            $sr = 1;
            foreach ($queArr as $qId) {

                $checkQueCount = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $qId])->count();
                if ($checkQueCount == 0) {
                    $addArr = ['sr_no' => $sr, 'user_id' => $user_id, 'question_id' => $qId, "is_practice" => $isPractice];
                    TempSrQuestion::insert($addArr);

                    $sr++;
                }
            }
        }

        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_to_cart(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'record_type' => 'required|integer',
            'course_id' => 'required|integer',
            'package_id' => 'required|integer',
        ]);

        $user_id = Auth::id();
        $tutorial_id = $request->tutorial_id;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            //   'user_id','record_id','package_id','record_type','created_at','updated_at'

            $record_type = $request->record_type;
            $course_id = $request->course_id;
            $package_id = $request->package_id;

            $cartArr = [
                'user_id' => $user_id,
                'record_type' => $record_type,
                'record_id' => $course_id,
                'package_id' => $package_id,
            ];

            $is_exist = Cart::where(['user_id' => $user_id, 'record_id' => $course_id, 'record_type' => $record_type])->count();
            if ($is_exist == 0) {

                $newUser = Cart::create($cartArr);
                $req_message = "Package added";
            } else {
                Cart::where(['user_id' => $user_id, 'record_id' => $course_id, 'record_type' => $record_type])->update($cartArr);
                $req_message = "Package updated";
            }
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function get_cart_list(Request $request)
    {

        $user_id = Auth::id();
        $packageFor = $request->package_for;

        $getCartDt =  Cart::orderBy('id', 'desc')->where([
            'user_id' => $user_id,
            'record_type' => $packageFor ? $packageFor : 1,
        ])->get();

        $cartArr = ($getCartDt->count() > 0) ? $getCartDt->toArray() : [];

        $req_data['cart_list'] = $cartArr;

        if (count($cartArr)) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function short_string_char($str)
    {
        $ret = '';
        foreach (explode(' ', $str) as $word)
            $ret .= strtoupper($word[0]);
        return $ret;
    }

    public function add_tutorial_note(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'tutorial_id' => 'required|integer',
            'note' => 'required',
        ]);

        $user_id = Auth::id();
        $tutorial_id = $request->tutorial_id;
        $note = $request->note;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $newUser = TutorialNote::create([
                'user_id' => $user_id,
                'tutorial_id' => $tutorial_id,
                'notes' => $note,
            ]);
            $req_message = "Tutorial Note Added";
        }
        return $this->json_view(true, $req_data, $req_message);
    }

    public function add_watched_tutorial(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'tutorial_id' => 'required|integer',
            'course_id' => 'required|integer',
            'watched_time' => 'required',
        ]);

        $user_id = Auth::id(); // 15 // laxman
        $tutorial_id = $request->tutorial_id;
        $course_id = $request->course_id;

        $get_Tutorial_Dt = Tutorial::where('id', $request->tutorial_id)->first();
        // $course_id = $get_Tutorial_Dt->course_id;

        $total_video_time = $get_Tutorial_Dt->total_video_time;
        $category_id = $get_Tutorial_Dt->category_id;

        $data = $request->all();
        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {

            $checkRecord = WatchedTutorial::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $category_id, 'tutorial_id' => $tutorial_id])->first(['id', 'total_video_time']);
            $req_data['is_added'] = '0';
            $w = 0;
            $sevPer = 0;
            if (!empty($checkRecord)) {
                $w =  $this->timeToSeconds($checkRecord->total_video_time);
                $sevPer = (70 / 100) * $w;
            }




            $time = $request->watched_time;
            if ($request->watched_time >= $sevPer) {
                $time = $w;
            }

            $crr = [
                'user_id' => $user_id,
                'course_id' => $course_id,
                'tutorial_id' => $tutorial_id,
                'category_id' => $category_id,
                'total_video_time' => $total_video_time,
                'watched_time' => $time,
            ];

            if (isset($checkRecord->id)) {
                WatchedTutorial::where(['id' => $checkRecord->id])->update($crr);
            } else {
                $newUser = WatchedTutorial::create($crr);
                $req_data['is_added'] = '1';
            }
        }
        $req_message = "Tutorial Added To My Watched List";

        return $this->json_view(true, $req_data, $req_message);
    }

    public function make_test(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer',
        ]);

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $isPractice = $request->isPractice ? $request->isPractice : 0;
            $user_id = Auth::id();
            $question_id = $request->question_id;

            $correct_option_json = "";

            /*return $request->correct_option_json;
            if(count($request->correct_option_json)){
                return "hello".$correct_option_json = $request->correct_option_json;
            }*/

            $findAlreadyQuestionSaved =   TempTest::where(['user_id' => $user_id, 'question_id' => $question_id])->first();
            $findAlreadyQuestionSaved_correct_option_json = $findAlreadyQuestionSaved->correct_option_json ?? '';
            $correct_option_json = !empty($request->correct_option_json) ? $request->correct_option_json : $findAlreadyQuestionSaved_correct_option_json;;

            $all_filter_question_id = $request->all_filter_question_id;

            $get_before_finish = TempBeforeFinishTest::where('user_id', $user_id)->first();
            if (!isset($get_before_finish->id)) {
                $temp_all_filter_dt_arr =  [
                    'user_id' => $user_id,
                    'questions_id' => $all_filter_question_id,
                    'is_practice' => $isPractice,
                ];
                TempBeforeFinishTest::create($temp_all_filter_dt_arr);
            }


            $get_Que_Dt = QuestionAnswer::where('id', $question_id)->first();

            $course_id = $get_Que_Dt->course_id;
            $tutorial_id = $get_Que_Dt->tutorial_id;
            $category_id = $get_Que_Dt->category_id;
            $sub_category_ids = $get_Que_Dt->sub_category_ids;
            $question_type = $get_Que_Dt->question_type;


            if ($question_type == 2 || $question_type == 3 || $question_type == 4) {


                $option_encode = json_encode($correct_option_json);
                $option_decode = json_decode($correct_option_json);

                $correct_option_count = '0';
                $total_options = ($correct_option_json != "") ? count($option_decode) : '0';

                if (!is_null($option_decode)) {
                    foreach ($option_decode as $op_val) {
                        $option_id = $op_val->option_id;
                        $option_value_id = $op_val->option_value_id;

                        $getCorrectOption = QueOption::where(["id" => $option_id, "option_value_id" => $option_value_id])->count();
                        if ($getCorrectOption > 0) {
                            $correct_option_count++;
                        }
                    }
                }
                $is_correct = ($total_options == $correct_option_count) ? '1' : '0';
            } else {

                $correctArr = explode(',', $get_Que_Dt->correct_answer);

                $is_correct = (in_array($request->answer, $correctArr)) ? '1' : '0';
            }

            $findAlreadyQuestionSaved_answer = $findAlreadyQuestionSaved->answer ?? '';
            $temp_test_dt_arr =  [
                'user_id' => $user_id,
                'course_id' => $course_id,
                'tutorial_id' => $tutorial_id,
                'category_id' => $category_id,
                'sub_category_ids' => $sub_category_ids,
                'question_id' => $question_id,
                'question_type' => $question_type,
                'correct_option_json' => $correct_option_json,
                'is_practice' => $isPractice,
                'answer' => !empty($request->answer) ? $request->answer : $findAlreadyQuestionSaved_answer,
                'is_correct' => $is_correct,
            ];



            $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $question_id])->first();

            if (isset($getTestDt->id)) {
                TempTest::where(['user_id' => $user_id, 'question_id' => $question_id])->update($temp_test_dt_arr);
            } else {

                if ($correct_option_json != "" || @$request->answer != "") {
                    TempTest::create($temp_test_dt_arr);
                }
            }

            if ($correct_option_json == "" && @$request->answer == "") {

                $get_dt = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
                if (isset($get_dt->id)) {
                    $skip_ques_id = $get_dt->skip_question;

                    $skipQueArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];
                    if (!in_array($question_id, $skipQueArr)) {
                        $skipQueArr[] = $question_id;
                        $skip_srv = array_unique($skipQueArr);
                        $insert_skip_srv = (count($skip_srv) > 0) ? implode(',', $skip_srv) : "";

                        $addSkipArr = ['skip_question' => $insert_skip_srv];
                        TempBeforeFinishTest::where(['user_id' => $user_id])->update($addSkipArr);
                    }
                }
            } else {
                $get_dt = TempBeforeFinishTest::where(['user_id' => $user_id])->whereRaw('FIND_IN_SET("' . $question_id . '",skip_question)')->first();
                if (isset($get_dt->id)) {
                    $skip_ques_id = $get_dt->skip_question;
                    $skipQueArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];
                    $pos = array_search($question_id, $skipQueArr);
                    if ($pos !== false) {
                        unset($skipQueArr[$pos]);
                    }
                    $skip_srv = array_unique($skipQueArr);
                    $insert_skip_srv = (count($skip_srv) > 0) ? implode(',', $skip_srv) : "";
                    $addSkipArr = ['skip_question' => $insert_skip_srv];
                    TempBeforeFinishTest::where(['user_id' => $user_id])->update($addSkipArr);
                }
            }
            $req_data['request_answer'] = $request->answer;
            // $req_data['correct_answer'] = 'abc'.$get_Que_Dt->correct_answer;
            $req_data['is_correct'] = $is_correct;

            $req_message = "Test Submitted Successfully";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function finish_test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|integer',
        ]);

        $user_id = Auth::id();

        $data = $request->all();

        $req_data = [];

        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $course_id = $request->course_id;
            $isPractice = $request->isPractice ? $request->isPractice : 0;

            $getTestDt = TempTest::where(['user_id' => $user_id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get();

            Log::info('getTestDt' . json_encode($getTestDt));

            if ($getTestDt->count() > 0) {
                foreach ($getTestDt as $tstVal) {
                    $question_id = $tstVal->question_id;
                    $temp_test_dt_arr =  [
                        'user_id' => $tstVal->user_id,
                        'course_id' =>  $course_id,
                        'tutorial_id' => $tstVal->tutorial_id,
                        'category_id' => $tstVal->category_id,
                        'sub_category_ids' => $tstVal->sub_category_ids,
                        'question_id' => $tstVal->question_id,
                        'question_type' => $tstVal->question_type,
                        'correct_option_json' => $tstVal->correct_option_json,
                        'is_practice' => $isPractice,
                        'answer' => $tstVal->answer,
                        'is_correct' => $tstVal->is_correct,
                    ];

                    $getTestDt = AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $tstVal->question_id])->first();

                    if (isset($getTestDt->id)) {
                        AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $question_id])->update($temp_test_dt_arr);
                    } else {
                        AttemptQuestion::create($temp_test_dt_arr);
                    }
                }

                $req_message = "Test Submitted Successfully";
                return $this->json_view(true, $req_data, $req_message);
            }
            $req_message = "Please attempt atleast one question";
            return $this->json_view(false, $req_data, $req_message);
        }
    }


    public function deleteapi(Request $request)
    {
        $user_id = Auth::id();
        $req_data = [];
        TempTest::where(['user_id' => $user_id])->delete();
        TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        TempSrQuestion::where(['user_id' => $user_id])->delete();
        AttemptQuestion::where(['user_id' => $user_id, 'is_practice' => 1])->delete();
        $req_message = "data Deleted Successfully";
        return $this->json_view(true, $req_data, $req_message);
    }

    public function sum_the_time($time1, $time2)
    {
        $times = array($time1, $time2);
        $seconds = 0;
        foreach ($times as $time) {
            list($hour, $minute, $second) = explode(':', $time);
            $seconds += $hour * 3600;
            $seconds += $minute * 60;
            $seconds += $second;
        }
        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes  = floor($seconds / 60);
        $seconds -= $minutes * 60;
        return "{$hours}:{$minutes}:{$seconds}";
    }

    public function get_questions_list(Request $request)
    {

        $count_down_time = '';

        $questions_ids_arr = [];
        $current_qestion_for_review = $request->current_qestion_for_review;
        $QueQuery = QuestionAnswer::orderBy('id', 'asc')->where('status', 1);

        if ($request->category_id) {
            $category_ids_arr = explode(',', $request->category_id);
            $QueQuery->whereIn('category_id', $category_ids_arr);
        }
        if ($request->sub_category_id) {
            $sub_category_ids = $request->sub_category_id;

            $QueQuery->Where(function ($query) use ($sub_category_ids) {
                if (isset($sub_category_ids) && !empty($sub_category_ids)) {
                    foreach (explode(',', $sub_category_ids) as $sub_cat_val) {
                        $query->orwhereRaw('FIND_IN_SET("' . $sub_cat_val . '",sub_category_ids)');
                    }
                }
            });
        }
        if ($request->tutorial_id) {
            $QueQuery->where('tutorial_id', $request->tutorial_id);
        }
        if ($request->filter_questions_id) {
            $questions_ids_arr = explode(',', $request->filter_questions_id);

            $QueQuery->whereIn('id', $questions_ids_arr);
        }

        $current_question_id = $request->current_question_id ?? "";

        if (isset($request->current_question_id)) {
            $QueQuery->where('id', $current_question_id);
        }

        $getQuesDt = $QueQuery->first();



        $explanation = !empty($getQuesDt->explanation) ? $getQuesDt->explanation : '';
        $show_question_id = @$getQuesDt->id;

        if (isset($getQuesDt->id)) {
            $getPreviousQuesDt = QuestionAnswer::orderBy('id', 'desc')->whereIn('id', $questions_ids_arr)->where('status', 1)->where('id', '<', $show_question_id)->first(['id']);

            $previous_question_id = @$getPreviousQuesDt->id;

            $getNextQuesDt = QuestionAnswer::orderBy('id', 'asc')->whereIn('id', $questions_ids_arr)->where('status', 1)->where('id', '>', $show_question_id)->first(['id']);
            $next_question_id = @$getNextQuesDt->id;
        }
        $my_curr_que_id = @$getQuesDt->id ?? "";


        $user_id = Auth::id();
        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? '';
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $is_reviewed = (in_array($my_curr_que_id, $reviewArr)) ? '1' : '0';

        $getSrNo = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $my_curr_que_id])->first(['sr_no']);
        $sr_no_is =  @$getSrNo->sr_no ?? '';

        // $getSrNo_count = TempSrQuestion::where('user_id' , $user_id)->count();
        // return $getSrNo_count
        // $req_data['total_question'] = $getSrNo_count;
        $req_data['total_question'] = count($questions_ids_arr);
        /*  $req_data['total_question'] = $getSrNo_count;
        $req_message='';
        return $this->json_view(true, $getSrNo_count, $req_message); */
        // $req_data['total_question'] = count($questions_ids_arr);
        // print_r($req_data); exit;
        $req_data['serial_no'] = $sr_no_is;
        $req_data['count_down_time'] = $count_down_time;
        $req_data['is_question_reviewed'] = $is_reviewed;
        $req_data['previous_question_id'] = $previous_question_id ?? "";
        $req_data['current_question_id'] = $my_curr_que_id;
        $req_data['next_question_id'] = $next_question_id ?? "";
        $req_data['current_qestion_for_review'] = (int) $current_qestion_for_review ?? '';

        $getQuestion = (isset($getQuesDt) && $getQuesDt->count() > 0) ? $getQuesDt->toArray() : [];
        $req_data['question_list'] = $getQuestion;

        $is_question_attempt = '0';
        $getTestDt = AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $show_question_id])->first();


        if (isset($getTestDt->id)) {
            $is_question_attempt = '0';
            $my_answer = $getTestDt->answer ?? '';
            $my_correct_option_json = $getTestDt->correct_option_json ?? '';
        }

        $req_data['is_question_attempt'] = $is_question_attempt;

        if ($request->is_feedback) {
            $attemptUserArr = AttemptQuestion::where(['question_id' => $show_question_id])->groupBy('user_id')->get('user_id')->toArray();
            $userOrderIds = Order::where("user_id", $user_id)->pluck("id");
            $checkUserPackage = OrderDetail::whereIn("order_id", $userOrderIds)->where("package_for", 1)->where("particular_record_id", $request->course_id)->orderBy("id", "desc")->first();
            $packageStatus = @$checkUserPackage->package->price > 0 ? "paid" : "free";


            $total_que_attempt_user = count($attemptUserArr);
            $req_data['total_question_attempt_user'] = $total_que_attempt_user;
            // $show_question_id 
            // $req_data['question_selected'] = [
            //     [
            //         'my_answer'=>@$my_answer,
            //         'my_correct_option_json'=>(String) @$my_correct_option_json,
            //     ]

            // ];
            $req_data['my_answer'] = @$my_answer;
            $req_data['packageStatus'] = @$packageStatus;
            $req_data['my_correct_option_json'] = @$my_correct_option_json;

            $question_type = $getTestDt->question_type ?? '';

            $option_id = '';
            $option_value_id = '';
            $crr = [];

            if ($question_type == '2' || $question_type == '3' || $question_type == '5') {

                /*$getQueOption1  = QueOption::where(['question_id' =>$show_question_id])->get(['id','option_name','option_value_id','correct_option_answer']);   
                    foreach($getQueOption1 as $queVal_1){
                        $option_id = $queVal_1->id;*/

                $getQueOptionAttr = QueOptionAnswerType::where('question_id', $show_question_id)->get(['id', 'answer_type_name']);

                $que_option_score = [];
                foreach ($getQueOptionAttr as $skey => $queVal_2) {
                    $option_val_id = $queVal_2->id;
                    $option_type_name = $queVal_2->answer_type_name;

                    $getAttemptUserArr = AttemptQuestion::where(['question_id' => $show_question_id])->get(['id', 'question_id', 'correct_option_json', 'answer']);

                    $opt_arr = [];
                    $option_count = '0';
                    $storeAllJson = [];
                    foreach ($getAttemptUserArr as $skey1 => $queVal_3) {

                        $each_correct_option_json = $queVal_3->correct_option_json;
                        $each_json_ans = json_decode($each_correct_option_json);
                        $storeAllJson[] = $each_json_ans;
                        // dd($each_json_ans);
                        if (!empty($each_json_ans)) {
                            foreach ($each_json_ans as $skey2 => $queVal_4) {

                                $option_id = $queVal_4->option_id;
                                // dd($each_json_ans); 
                                $option_value_id2 = $queVal_4->option_value_id;

                                if ($option_val_id == $option_value_id2) {
                                    $option_count++;
                                }
                            }
                        }
                    }



                    // "id": 111,
                    // "question_id": 167,
                    $avg_score = $total_que_attempt_user > 0 ? ($option_count * 100) / $total_que_attempt_user : 0;

                    //    $findQuestionAwn = DB::table('ques_option_tbl')->where('question_id',$show_question_id)->get();
                    //  $getQueOptionAttr = QueOption::where('question_id',$show_question_id)->where('option_value_id',$option_val_id)->get();
                    /// print_r($option_val_id);die;


                    $opt_arr["option_value_id"] = $option_val_id;
                    $opt_arr["option_type_name"] = $option_type_name;
                    $opt_arr["option_attempt"] = $option_count;
                    $opt_arr["option_avg_score"] = round($avg_score, 2);



                    // $recorxd = DB::table('ques_option_tbl')->where('option_value_id',$option_type_name)->where()
                    $que_option_score[$skey] = $opt_arr;
                }

                $crr[] = $que_option_score;
                // }

            } else {

                $option_a_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "a"])->get('id')->count();
                $option_b_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "b"])->get('id')->count();
                $option_c_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "c"])->get('id')->count();
                $option_d_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "d"])->get('id')->count();
                $option_e_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "e"])->get('id')->count();
                $option_f_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "f"])->get('id')->count();
                $option_g_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "g"])->get('id')->count();
                $option_h_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "h"])->get('id')->count();
                $option_i_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "i"])->get('id')->count();
                $option_j_count = AttemptQuestion::where(['question_id' => $show_question_id, 'answer' => "j"])->get('id')->count();

                $avg_score_a = 0;
                if (($option_a_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_a = ($option_a_count * 100) / $total_que_attempt_user;
                }

                $avg_score_b = 0;
                if (($option_b_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_b = ($option_b_count * 100) / $total_que_attempt_user;
                }

                $avg_score_c = 0;
                if (($option_c_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_c = ($option_c_count * 100) / $total_que_attempt_user;
                }

                $avg_score_d = 0;
                if (($option_d_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_d = ($option_d_count * 100) / $total_que_attempt_user;
                }
                $avg_score_e = 0;
                if (($option_e_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_e = ($option_e_count * 100) / $total_que_attempt_user;
                }

                $avg_score_f = 0;
                if (($option_f_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_f = ($option_f_count * 100) / $total_que_attempt_user;
                }
                $avg_score_g = 0;
                if (($option_g_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_g = ($option_g_count * 100) / $total_que_attempt_user;
                }
                $avg_score_h = 0;
                if (($option_h_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_h = ($option_h_count * 100) / $total_que_attempt_user;
                }
                $avg_score_i = 0;
                if (($option_i_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_i = ($option_i_count * 100) / $total_que_attempt_user;
                }
                $avg_score_j = 0;
                if (($option_j_count != 0) && ($total_que_attempt_user != 0)) {
                    $avg_score_j = ($option_j_count * 100) / $total_que_attempt_user;
                }


                // $avg_score_a = ($option_a_count*100)/$total_que_attempt_user;
                // $avg_score_b= ($option_b_count*100)/$total_que_attempt_user;
                // $avg_score_c = ($option_c_count*100)/$total_que_attempt_user;
                // $avg_score_d = ($option_d_count*100)/$total_que_attempt_user;
                // $avg_score_e = ($option_e_count*100)/$total_que_attempt_user;
                // $avg_score_f = ($option_f_count*100)/$total_que_attempt_user;

                $crr["option_a_score"] = round($avg_score_a, 2);
                $crr["option_b_score"] = round($avg_score_b, 2);
                $crr["option_c_score"] = round($avg_score_c, 2);
                $crr["option_d_score"] = round($avg_score_d, 2);
                $crr["option_e_score"] = round($avg_score_e, 2);
                $crr["option_f_score"] = round($avg_score_f, 2);
                $crr["option_g_score"] = round($avg_score_g, 2);
                $crr["option_h_score"] = round($avg_score_h, 2);
                $crr["option_i_score"] = round($avg_score_i, 2);
                $crr["option_j_score"] = round($avg_score_j, 2);
                //dd($opt_arr); 
            }

            $req_data['question_option_user_score'] = $crr;
        }

        $getAllOption = QueOption::where("question_id", $getQuesDt->id)->get();

        $allOption = array();
        foreach ($getAllOption as $key => $val) {
            $getAttempt = AttemptQuestion::where("question_id", $getQuesDt->id)->where("user_id", $user_id)->first();
            if (!empty($getAttempt->correct_option_json)) {
                $correct_option_json = json_decode($getAttempt->correct_option_json, true);
                $optionGiven = array();

                foreach ($correct_option_json as $val1) {
                    $getQueOptionAttr = QueOptionAnswerType::find($val1['option_value_id']);
                    $optionGiven[$val1['option_id']]['option_value_id'] = $val1['option_value_id'];
                    $optionGiven[$val1['option_id']]['name'] = $getQueOptionAttr->answer_type_name;
                }

                $allOption[$key]['optionId'] = $val->id;
                $allOption[$key]['optionName'] = $val->option_name;
                $allOption[$key]['option_value_id'] = $val->option_value_id;
                $allOption[$key]['correct_option_answer'] = $val->correct_option_answer;
                $allOption[$key]['givenOption_value_id'] = @$optionGiven[$val->id]['option_value_id'];
                $allOption[$key]['givenOption_value_text'] = @$optionGiven[$val->id]['name'];
            } else {
                $allOption[$key]['optionId'] = $val->id;
                $allOption[$key]['optionName'] = $val->option_name;
                $allOption[$key]['option_value_id'] = $val->option_value_id;
                $allOption[$key]['correct_option_answer'] = $val->correct_option_answer;
                $allOption[$key]['givenOption_value_id'] = '';
                $allOption[$key]['givenOption_value_text'] = '';
            }
        }
        $req_data['allOption'] = $allOption;

        $get_rating = Rating::where(['user_id' => $user_id, 'question_id' => $show_question_id])->first(['rating']);
        $my_question_rating = (isset($get_rating->rating)) ? $get_rating->rating : '0';
        $req_data['my_question_rating'] = $my_question_rating;

        $get_like_count = LikeUnlike::where(['question_id' => $show_question_id, 'like_unlike_status' => '1'])->get(['id'])->count();
        $req_data['like_count'] = $get_like_count;

        $get_dislike_count = LikeUnlike::where(['question_id' => $show_question_id, 'like_unlike_status' => '2'])->get(['id'])->count();
        $req_data['dislike_count'] = $get_dislike_count;
        $req_data['user_id'] = $user_id;

        if (count($getQuestion)) {
            $req_message = "Record Found";
            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(true, $req_data, $req_message);
        }
    }

    public function review_test_before_finish(Request $request)
    {

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Not Attempted";
        $que_status_color = "#efefef";
        $que_status_new = "";
        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
        $questions_id = $getTextAllQue->questions_id ?? '';
        $testQueArr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $getTextAllQue->question_for_review;
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $getTextAllQue->skip_question;

        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];


        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($testQueArr as $key => $quesId) {
                //if($quesId=='20'){
                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();
                $que_statusNew = '';
                if (in_array($quesId, $reviewArr) && in_array($quesId, $skipArr)) {
                    if (isset($getTestDt->id)) {
                        $que_status = "Complete";
                        $que_status_color = "green";
                        $check_already_review = '1';
                    } else {
                        $que_status = "Not Attempted";
                        $que_statusNew = "Incomplete";
                        $que_status_color = "#efefef";
                        $check_already_review = '1';
                    }
                    // $check_already_review= (isset($getTestDt->id)) ? "1": "0";
                } else if (in_array($quesId, $reviewArr)) {
                    if (isset($getTestDt->id)) {
                        $que_status = "Complete";
                        $que_status_color = "green";
                        $check_already_review = 1;
                    } else {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";
                        $check_already_review = 1;
                    }
                    // $check_already_review = (isset($getTestDt->id)) ? "1" : "0";
                } else if (in_array($quesId, $skipArr)) {
                    $que_status = "Not Attempted";
                    $que_statusNew = "Incomplete";
                    $que_status_color = "#efefef";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Complete";
                    $que_status_color = "green";
                } else {
                    $que_status = "Not Attempted";
                    $que_status_color = "#efefef";
                }

                $getSrNo = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId])->first(['sr_no']);

                $sr_no_is =  @$getSrNo->sr_no ?? '';

                if ($que_statusNew == "Incomplete" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] =  [
                    'sr_no' => $sr_no_is,
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_for_review' => $que_status_new,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];
                $price = array();
                foreach ($temp_test_dt_arr as $key1 => $row1) {
                    $price[$key1] = $row1['sr_no'];
                }
                array_multisort($price, SORT_ASC, $temp_test_dt_arr);


                $req_data['review_list'] = $temp_test_dt_arr;

                // }
            }
        }
        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;

        $req_data['review_list'] = $temp_test_dt_arr;
        $req_message = "No test Found";
        return $this->json_view(false, $req_data, $req_message);
    }
    //backup
    public function review_test_before_finishbeforcolorchange(Request $request)
    {

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Unseen";
        $que_status_color = "red";
        $que_status_new = "";
        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
        $questions_id = $getTextAllQue->questions_id ?? '';
        $testQueArr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $getTextAllQue->question_for_review;
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $getTextAllQue->skip_question;

        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];


        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($testQueArr as $key => $quesId) {
                //if($quesId=='20'){
                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();

                if (in_array($quesId, $reviewArr) && in_array($quesId, $skipArr)) {
                    if (isset($getTestDt->id)) {
                        $que_status = "Complete";
                        $que_status_color = "green";
                        $check_already_review = '1';
                    } else {
                        $que_status = "Incomplete";
                        $que_status_color = "orange";
                        $check_already_review = '1';
                    }
                    // $check_already_review= (isset($getTestDt->id)) ? "1": "0";
                } else if (in_array($quesId, $reviewArr)) {
                    if (isset($getTestDt->id)) {
                        $que_status = "Complete";
                        $que_status_color = "green";
                    } else {
                        $que_status = "Flagged";
                        $que_status_color = "yellow";
                    }
                    $check_already_review = (isset($getTestDt->id)) ? "1" : "0";
                } else if (in_array($quesId, $skipArr)) {
                    $que_status = "Incomplete";
                    $que_status_color = "orange";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Complete";
                    $que_status_color = "green";
                } else {
                    $que_status = "Unseen";
                    $que_status_color = "red";
                }

                $getSrNo = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId])->first(['sr_no']);

                $sr_no_is =  @$getSrNo->sr_no ?? '';

                if ($que_status == "Incomplete" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] =  [
                    'sr_no' => $sr_no_is,
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_for_review' => $que_status_new,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];
                $price = array();
                foreach ($temp_test_dt_arr as $key1 => $row1) {
                    $price[$key1] = $row1['sr_no'];
                }
                array_multisort($price, SORT_ASC, $temp_test_dt_arr);


                $req_data['review_list'] = $temp_test_dt_arr;

                // }
            }
        }
        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;

        $req_data['review_list'] = $temp_test_dt_arr;
        $req_message = "No test Found";
        return $this->json_view(false, $req_data, $req_message);
    }
    public function report_issue_data(Request $request)
    {

        $data =  [
            1 => 'Something not working properly',
            2 => 'Mistake in Stem',
            3 => 'Mistake in Question',
            4 => 'Mistake in Answers',
            5 => 'Right answer marked as wrong',
            6 => 'Mistake in Explanation',
            7 => 'Other',
        ];
        $arr = [];
        foreach ($data as $key => $val) {
            $row = [];
            $row['key']    = $key;
            $row['value']    = $val;
            $arr[] = $row;
        }

        return $this->json_view(true, $arr, 'report issue select data');
    }
    public function report_issue(Request $request)
    {
        try {


            $user_id = Auth::id();
            $data = $request->all();

            $req_data = [];
            if ($request->email != "mughal50@hotmail.com") {
                $row = new Reportissue();
                $row->options = !empty($request->options) ? json_encode($request->options) : '[{"key":7,"value":"Other"}]';
                $row->email = $request->email ? $request->email : Auth::user()->email;
                $row->description = $request->description;
                $row->question_id = $request->question_id;
                $row->user_id = $user_id;
                $row->created_at = date('Y-m-d H:i:s');
                if ($row->save()) {
                    return $this->json_view(true, $req_data, 'Issue Sended Successfully');
                }
            }

            return $this->json_view(false, $req_data, 'Something Went Wrong!');
        } catch (\Exception $e) {
            return $this->json_view(false, [], 'Something Went Wrong!');
        }
    }


    public function json_view($req_status = false, $req_data = "", $req_message = "")
    {
        $this->status = $req_status;
        $this->code = ($req_status == false) ? "404" : "101";
        $this->data = $req_data;
        $this->message = $req_message;
        return  response()->json($this);
    }


    public function clearWatchTime(Request $request)
    {
        try {
            $tutorialId = $request->tutorial_id;
            $user_id = Auth::id();
            $courseId = $request->course_id;


            $validator = Validator::make($request->all(), [
                'tutorial_id' => 'required',
                'course_id' => 'required'
            ]);


            $req_data = [];
            if ($validator->fails()) {
                $error = '';
                if (!empty($validator->errors())) {
                    $error = $validator->errors()->first();
                }
                $message = $error;
                return $this->json_view(false, $req_data, $message);
            }

            $courseId = (int) $courseId;
            $tutorialId = (int) $tutorialId;

            $findWatchTimeTutorials = WatchedTutorial::where([
                'user_id' => $user_id,
                'course_id' => $courseId,
                'tutorial_id' => $tutorialId,
            ])->first();

            if (!empty($findWatchTimeTutorials)) {
                $find = WatchedTutorial::find($findWatchTimeTutorials->id);

                $find->delete();
                return $this->json_view(true, [], 'Cleared watchtime!');
            } else {
                $findQuestion = Tutorial::find($tutorialId);
                $row = new WatchedTutorial;

                $time = (float) $findQuestion->total_video_time;
                $time = $time * 60;
                $row->user_id = $user_id;
                $row->course_id = $courseId;
                $row->tutorial_id = $tutorialId;
                $row->category_id = $findQuestion->category_id;;
                $row->total_video_time = $findQuestion->total_video_time;
                $row->watched_time = !empty($request->video_time) ? $request->video_time : $time;;

                $row->save();
                return $this->json_view(true, [], 'Watchtime Filled Successfully!');;
            }
        } catch (\Exception $e) {
            return $this->json_view(false, [], 'Something Went Wrong!');
        }
    }
    function timeToSeconds($time)
    {

        if (!empty($time)) {
            $timeExploded = explode(':', $time);

            if (isset($timeExploded[2])) {
                return (int) $timeExploded[0] * 3600 + (int) $timeExploded[1] * 60 + (int) $timeExploded[2];
            }
            return (int) $timeExploded[0] * 3600 + (int) $timeExploded[1] * 60;
        } else {
            return 0;
        }
    }

    public function getPercentileData(Request $request)
    {
    }

    public function allOverAvgPercentile($userids, $currentId, $courseid)
    {
        $attemptedQuestionlist = AttemptQuestion::where(['user_id' => $currentId, 'course_id' => $courseid])->pluck('question_id');
        // current user attempt questions

        $userlist = AttemptQuestion::where('course_id', $courseid)
            ->whereIn('question_id', $attemptedQuestionlist)
            ->whereNotIn('user_id', [$currentId])
            ->groupBy('user_id')
            ->pluck('user_id');

        $totalManagedArr = [];
        if (!empty($userlist)) {
            foreach ($userlist as $k => $v) {

                //print_r(['user_id'=>$v,'course_id'=>$courseid]);die;
                $totalQuestions = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->get()
                    ->count();

                $totalCorrect = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])

                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->where('is_correct', 1)
                    ->get()
                    ->count();

                $totalIncorrect = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])
                    ->where('is_correct', 1)
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->where('is_correct', 0)
                    ->get()
                    ->count();
                $totalManagedArr['totalQuestion']['total'] = $totalQuestions;
                $totalManagedArr['totalQuestion']['correct'] = $totalCorrect;
                $totalManagedArr['totalQuestion']['incorrect'] = $totalIncorrect;
                $totalAvg = ($totalQuestions - ($totalCorrect + $totalIncorrect) - $totalCorrect);
                $totalManagedArr['totalQuestion']['avg'] = $totalAvg;
            }
        }

        print_r($totalManagedArr);
        die;
    }

    public function getUserWisePercentage($userids, $currentId, $courseid, $categoryid, $catAverage = NULL, $subcatid = null, $mainAll = NULL)
    {


        if (!empty($mainAll)) {
            $attemptedQuestionlist = AttemptQuestion::where(['user_id' => $currentId, 'course_id' => $courseid, 'category_id' => $categoryid])->pluck('question_id');
            $userlist = AttemptQuestion::where('course_id', $courseid)
                ->whereIn('question_id', $attemptedQuestionlist)
                ->whereNotIn('user_id', [$currentId])
                ->groupBy('user_id')
                ->pluck('user_id');
        } else {
            if (!empty($subcatid)) {
                $attemptedQuestionlist = AttemptQuestion::where(['user_id' => $currentId, 'course_id' => $courseid, 'category_id' => $categoryid, 'sub_category_ids' => $subcatid])->pluck('question_id');
                $userlist = AttemptQuestion::where('course_id', $courseid)
                    ->where('category_id', $categoryid)
                    ->where('sub_category_ids', $subcatid)
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->whereNotIn('user_id', [$currentId])
                    ->groupBy('user_id')
                    ->pluck('user_id');
            } else {
                $attemptedQuestionlist = AttemptQuestion::where(['user_id' => $currentId, 'course_id' => $courseid, 'category_id' => $categoryid])->pluck('question_id');
                $userlist = AttemptQuestion::where('course_id', $courseid)
                    ->where('category_id', $categoryid)
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->whereNotIn('user_id', [$currentId])
                    ->groupBy('user_id')
                    ->pluck('user_id');
            }
        }



        $totalManagedArr = [];
        $averageStoredArr = [];
        if (!empty($userlist)) {
            foreach ($userlist as $k => $v) {

                //print_r(['user_id'=>$v,'course_id'=>$courseid]);die;
                $totalQuestions = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->get()
                    ->count();

                $totalCorrect = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])

                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->where('is_correct', 1)
                    ->get()
                    ->count();

                $totalIncorrect = AttemptQuestion::where(['user_id' => $v, 'course_id' => $courseid])
                    ->where('is_correct', 1)
                    ->whereIn('question_id', $attemptedQuestionlist)
                    ->where('is_correct', 0)
                    ->get()
                    ->count();
                // $totalManagedArr['totalQuestion']['total'] = $totalQuestions;
                // $totalManagedArr['totalQuestion']['correct'] = $totalCorrect;
                // $totalManagedArr['totalQuestion']['incorrect'] = $totalIncorrect;
                $totalAvg = ($totalQuestions - ($totalCorrect + $totalIncorrect) - $totalCorrect);
                if ($totalAvg > 0) {
                    $averageStoredArr[] = $totalAvg;
                }

                // $totalManagedArr['totalQuestion']['avg'] = $totalAvg;
            }
        }
        if (!empty($averageStoredArr)) {
            sort($averageStoredArr);
        }
        $finalArrayScore = [];


        if (!empty($averageStoredArr)) {
            foreach ($averageStoredArr as $k => $v) {
                if ($v >= $catAverage) {

                    continue;
                } else {
                    $finalArrayScore[] = $v;
                }
            }
        }

        $totalAverageCount = count($finalArrayScore); // 4



        $bottomCount =  $totalAverageCount + 1; // 5

        if (!empty($totalAverageCount) && !empty($bottomCount)) {
            return ($totalAverageCount / $bottomCount) * 100;
        } else {
            return 0;
        }
    }

    public function get_score_after_test_finish_with_all_question(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
        ]);

        $data = $request->all();

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $course_id = $request->course_id;
            $user_id = Auth::id();
            $my_total_attempt = '0';
            $all_user_total_attempt = '0';
            $catTutArr = [];
            $catQueArr = [];
            $courseDetailsRow = Course::find($course_id);
            $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [])->orderBy('sort', 'asc');

            $getCatgory = $catQue->get();
            $category_count = $catQue->count();

            $avg_score_of_all_users = 0;
            $my_total_attempt_que = 0;

            $get_total_avg = 0;
            $get_my_correct_question = 0;


            $totalCRQ = 0;
            $totalIRQ = 0;
            $totalTRQ = 0;

            foreach ($getCatgory as $catDt) {

                $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();
                // return $totalQuestions; 
                $totalQuestions = (int)$totalQuestions;
                $my_total_attempt_que = $my_total_attempt_que + $totalQuestions;

                $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

                $totalUserQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

                // $totalAllUserAttemptCorrect = AttemptQuestion::where(['course_id'=>$course_id,'category_id'=>$catDt->id,'is_correct'=>'1'])->get()->count();

                $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;
                // return $totalMyAttemptCorrect;
                $totalAllUserAttemptCorrect = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
                $totalAllUserAttemptCorrect = (int)$totalAllUserAttemptCorrect;





                if ($totalAllUserAttemptCorrect > 0 && $totalUserQuestions > 0)
                    $avg_of_all_users =  ($totalAllUserAttemptCorrect * 100) / $totalUserQuestions;
                else
                    $avg_of_all_users = '0';


                $my_total_attempt = $my_total_attempt + $totalMyAttemptCorrect;
                $all_user_total_attempt = $all_user_total_attempt + $totalAllUserAttemptCorrect;

                if ($totalMyAttemptCorrect > 0 && $totalQuestions > 0)
                    $your_score_in_percent =  ($totalMyAttemptCorrect * 100) / $totalQuestions;
                else
                    $your_score_in_percent = '0';


                $get_total_avg = $get_total_avg +  $avg_of_all_users;
                $get_my_correct_question = $get_my_correct_question + $totalMyAttemptCorrect;

                $totalCorrectQuestionPercentage = 0;
                $totalInCorrectQuestionPercentage = 0;
                $totalAllAttemptedQuestion = $totalMyAttemptCorrect + $totalMyAttemptInCorrect;
                if ($totalAllAttemptedQuestion > 0 && $totalMyAttemptCorrect > 0) {
                    $totalCorrectQuestionPercentage = (($totalMyAttemptCorrect / $totalAllAttemptedQuestion) * 100);
                }
                if ($totalAllAttemptedQuestion > 0 && $totalMyAttemptInCorrect > 0) {
                    $totalInCorrectQuestionPercentage = (($totalMyAttemptInCorrect / $totalAllAttemptedQuestion) * 100);
                }

                $totalCRQ += $totalMyAttemptCorrect;
                $totalIRQ += $totalMyAttemptInCorrect;
                $totalTRQ += $totalMyAttemptInCorrect + $totalMyAttemptCorrect;

                $catQueArr[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'sort_category_name' => $this->short_string_char($catDt->category_name),
                    'avg_of_all_users' => round($avg_of_all_users, 2),
                    'total_questions' => $totalQuestions,
                    'my_total_correct_questions' => $totalMyAttemptCorrect,
                    'all_users_total_correct_questions' => $totalAllUserAttemptCorrect,
                    'your_score_in_percent' => round($your_score_in_percent, 2),
                    'total_correct_percentage' => round($totalCorrectQuestionPercentage, 2),
                    'total_incorrect_percentage' => round($totalInCorrectQuestionPercentage, 2),
                ];
            }


            $total_user_by_course = AttemptQuestion::where(['course_id' => $course_id])->groupBy('user_id')->get();
            $user_count_min = 0;
            foreach ($total_user_by_course as $key => $item) {
                $total_user_by_course_by_user = AttemptQuestion::where(['user_id' => $item->user_id, 'course_id' => $course_id, 'is_correct' => '1'])->groupBy('user_id')->get()->count();
                if ($get_my_correct_question > $total_user_by_course_by_user) {
                    $user_count_min = $user_count_min + 1;
                }
            }
            $you_perform_better_then = ($user_count_min / count($total_user_by_course)) * 100;
            // $get_my_correct_question
            // return ($user_count_min / count($total_user_by_course)) * 100;
            // $get_total_user_correct_question = $get_total_user_correct_question + $totalAllUserAttemptCorrect;



            $get_avg_score_of_all_users = $category_count > 0 ? $get_total_avg / $category_count : 0;
            $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();
            // return $totalQuestionsNoCategory;
            $my_total_attempt = (int)$my_total_attempt;
            $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

            /*if($my_total_attempt>0 && $totalQuestionsNoCategory>0)
                $your_percentile =  ($my_total_attempt*100)/$totalQuestionsNoCategory;
            else
                $your_percentile = '0';*/

            if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
                $your_percentile =  ($my_total_attempt * 100) / $my_total_attempt_que;
            else
                $your_percentile = '0';

            $your_total_average_score = $my_total_attempt * 100 / $category_count;

            if ($all_user_total_attempt > 0 && $totalQuestionsNoCategory > 0)
                $all_user_avg_score_in_percentile =  ($all_user_total_attempt * 100) / $totalQuestionsNoCategory;
            else
                $all_user_avg_score_in_percentile = '0';

            // $your_total_average_score = $my_total_attempt*100/$category_count;


            if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
                $your_total_average_score =  ($my_total_attempt * 100) / $my_total_attempt_que;
            else
                $your_total_average_score = '0';

            $avg_score_of_all_users = $all_user_avg_score_in_percentile;


            $totalCRQ += $totalMyAttemptCorrect;
            $totalIRQ += $totalMyAttemptInCorrect;
            $totalTRQ += $totalMyAttemptInCorrect + $totalMyAttemptCorrect;


            $totalFinalCorrectQuestionPercentage = 0;
            $totalFinalInCorrectQuestionPercentage = 0;
            if ($totalTRQ > 0 && $totalCRQ > 0) {
                $totalFinalCorrectQuestionPercentage = (($totalCRQ / $totalTRQ) * 100);
            }
            if ($totalTRQ > 0 && $totalIRQ > 0) {
                $totalFinalInCorrectQuestionPercentage = (($totalIRQ / $totalTRQ) * 100);
            }





            $req_data['your_score'] = $my_total_attempt;
            $req_data['avg_score_of_all_users'] = round($get_avg_score_of_all_users);
            $req_data['your_total_average_score'] = round($your_total_average_score);
            $req_data['your_percentile'] = round($your_percentile);
            $req_data['you_perform_better_then'] = $you_perform_better_then;
            $req_data['category_data'] = $catQueArr;
            $req_data['total_final_correct_percentage'] = round($totalFinalCorrectQuestionPercentage);
            $req_data['total_final_incorrect_percentage'] = round($totalFinalInCorrectQuestionPercentage);

            $getAllUserRank = AttemptQuestion::select('user_id', DB::raw('Count(is_correct) as total_score'))->where(['course_id' => $course_id, 'is_correct' => '1'])->orderBy('total_score', 'desc')->groupBy('user_id')->get();
            $rank_arr = [];
            $rank_i = 1;
            foreach ($getAllUserRank as $rnk_val) {
                if (isset($rnk_val['total_score'])) {
                    $rank_arr[$rnk_val['user_id']] = $rnk_val['total_score'];
                }
            }


            $user_rank = (count($rank_arr) > 0 && isset($rank_arr[$user_id])) ? $rank_arr[$user_id] : '0';
            $req_data['user_rank'] = $user_rank;


            if (count($getCatgory)) {
                $req_message = "Record Found";
                return $this->json_view(true, $req_data, $req_message);
            } else {
                $req_message = "No Record Found";
                return $this->json_view(true, $req_data, $req_message);
            }
        }
    }


    public function review_test_after_finish(Request $request)
    {
        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Not Attempted";
        $que_status_color = "#efefef";
        $que_status_new = "";
        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        if (empty($getTextAllQue)) {
            return $this->json_view(false, $req_data, 'no review records');
        }

        $questions_id = $getTextAllQue->questions_id ?? '';
        $testQueArr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? [];
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $getTextAllQue->skip_question;

        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];

        sort($testQueArr);
        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($testQueArr as $key => $quesId) {
                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();

                if (in_array($quesId, $reviewArr) && in_array($quesId, $skipArr)) {
                    if (isset($getTestDt->id) && $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                        $check_already_review = '1';
                    } elseif (isset($getTestDt->id) && $getTestDt->is_correct == "0") {
                        $que_status = "Incorrect";
                        $que_status_color = "red";
                        $check_already_review = '1';
                    } elseif (isset($getTestDt->id) && empty($getTestDt->answer)) {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";
                        $check_already_review = '1';
                    } else {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";
                        $check_already_review = '1';
                    }
                } else if (in_array($quesId, $reviewArr)) {
                    if (isset($getTestDt->id) &&  $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                    } else if ($getTestDt->is_correct == "0") {
                        $que_status = "Incorrect";
                        $que_status_color = "red";
                    } else {
                        $que_status = "Flagged";
                        $que_status_color = "yellow";
                    }
                    $check_already_review = (isset($getTestDt->id)) ? "1" : "0";
                } else if (in_array($quesId, $skipArr)) {
                    $que_status = "Not Attempted";
                    $que_status_color = "#efefef";
                } else if (!empty($getTestDt) && $getTestDt->is_correct == "0") {
                    $que_status = "Incorrect";
                    $que_status_color = "red";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Correct";
                    $que_status_color = "green";
                } else {
                    $que_status = "Not Attempted";
                    $que_status_color = "#efefef";
                }

                $getSrNo = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId])->first(['sr_no']);

                $sr_no_is =  @$getSrNo->sr_no ?? '';

                if ($que_status == "Incorrect" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] =  [
                    'sr_no' => $sr_no_is,
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_for_review' => $que_status_new,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];
                $price = array();
                foreach ($temp_test_dt_arr as $key1 => $row1) {
                    $price[$key1] = $row1['sr_no'];
                }
                array_multisort($price, SORT_ASC, $temp_test_dt_arr);


                $req_data['review_list'] = $temp_test_dt_arr;
            }
        }


        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;

        $req_data['review_list'] = $temp_test_dt_arr;
        $req_message = "Review Test Records";
        return $this->json_view(true, $req_data, $req_message);
    }
    //backup
    public function review_test_after_finishbeforcolorchange(Request $request)
    {

        $user_id = Auth::id();

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Unseen";
        $que_status_color = "red";
        $que_status_new = "";
        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        if (empty($getTextAllQue)) {
            return $this->json_view(false, $req_data, 'no review records');
        }

        $questions_id = $getTextAllQue->questions_id ?? '';
        $testQueArr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? [];
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $getTextAllQue->skip_question;

        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];

        sort($testQueArr);
        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($testQueArr as $key => $quesId) {
                //if($quesId=='20'){
                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();

                if (in_array($quesId, $reviewArr) && in_array($quesId, $skipArr)) {
                    if (isset($getTestDt->id) && $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                        $check_already_review = '1';
                    } elseif (isset($getTestDt->id) && $getTestDt->is_correct == "0") {
                        $que_status = "Incorrect";
                        $que_status_color = "orange";
                        $check_already_review = '1';
                    } elseif (isset($getTestDt->id) && empty($getTestDt->answer)) {
                        $que_status = "Incomplete";
                        $que_status_color = "orange";
                        $check_already_review = '1';
                    } else {
                        $que_status = "Incomplete";
                        $que_status_color = "orange";
                        $check_already_review = '1';
                    }
                    // $check_already_review= (isset($getTestDt->id)) ? "1": "0";
                } else if (in_array($quesId, $reviewArr)) {
                    if (isset($getTestDt->id) &&  $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                    } else {
                        $que_status = "Flagged";
                        $que_status_color = "yellow";
                    }
                    $check_already_review = (isset($getTestDt->id)) ? "1" : "0";
                } else if (in_array($quesId, $skipArr)) {
                    $que_status = "Incomplete";
                    $que_status_color = "orange";
                } else if (!empty($getTestDt) && $getTestDt->is_correct == "0") {
                    $que_status = "Incorrect";
                    $que_status_color = "orange";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Correct";
                    $que_status_color = "green";
                } else {
                    $que_status = "Unseen";
                    $que_status_color = "red";
                }

                $getSrNo = TempSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId])->first(['sr_no']);

                $sr_no_is =  @$getSrNo->sr_no ?? '';

                if ($que_status == "Incorrect" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] =  [
                    'sr_no' => $sr_no_is,
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_for_review' => $que_status_new,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];
                $price = array();
                foreach ($temp_test_dt_arr as $key1 => $row1) {
                    $price[$key1] = $row1['sr_no'];
                }
                array_multisort($price, SORT_ASC, $temp_test_dt_arr);


                $req_data['review_list'] = $temp_test_dt_arr;

                // }
            }
        }


        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;

        $req_data['review_list'] = $temp_test_dt_arr;
        $req_message = "Review Test Records";
        return $this->json_view(true, $req_data, $req_message);
    }
    public function getPageDetail(Request $request)
    {
        $type = $request->type;

        $pages = CmsPage::where("type", $type)->first();

        $testimonial = Testimonial::where("type", $type)->get();

        if ($type == "seminar") {
            $list = Seminar::query()->with(['addOns', 'tutor', 'testimonial']);
            $list = $list->orderBy('order', 'asc')->get();
        }

        if (strtolower($type) == "book") {
            $list = Book::query()->with(['addOns', 'tutor', 'testimonial']);
            $list = $list->orderBy('position', 'asc')->get();
        }

        if (strtolower($type) == "flashcard") {
            $list = FlashCard::query()->with(['addOns', 'tutor', 'testimonial']);
            $list = $list->orderBy('position', 'asc')->get();
        }

        return response()->json(['statusCode' => 200, 'message' => 'page detail.', 'data' => array("basicDetail" => $pages, "testimonial" => $testimonial, "list" => $list)], 200);
    }
    
    public function like_comment(Request $request)
    {
        $commentId = $request->commentId;

        $userId = Auth::user()->id;
        $type = $request->type;
        $checkComment = CommentLike::where("comment_id", $commentId)->where("user_id", $userId)->first();
        if ($checkComment) {


            if ($type != $checkComment->type) {
                if ($type == "like") {
                    $getComment = Comment::find($commentId);
                    $getComment->likeCount = $getComment->likeCount + 1;
                    $getComment->disLikeCount = $getComment->disLikeCount - 1;
                    $getComment->save();
                } else {
                    $getComment = Comment::find($commentId);
                    $getComment->likeCount = $getComment->likeCount - 1;
                    $getComment->disLikeCount = $getComment->disLikeCount + 1;
                    $getComment->save();
                }
            }

            $checkComment->type = $type;
            $checkComment->save();
        } else {
            $insert = new CommentLike();
            $insert->comment_id = $commentId;
            $insert->user_id = $userId;
            $insert->type = $type;
            $insert->save();
            if ($type == "like") {
                $getComment = Comment::find($commentId);
                $getComment->likeCount = $getComment->likeCount + 1;
                $getComment->save();
            } else {
                $getComment = Comment::find($commentId);
                $getComment->disLikeCount = $getComment->disLikeCount + 1;
                $getComment->save();
            }
        }


        return response()->json(['statusCode' => 200, 'message' => 'Comment ' . $type . ' successfully'], 200);
    }
    public function getprofile(Request $request)
    {
        $userId = Auth::user()->id;
        $userDetail = User::find($userId);
        return response()->json(['statusCode' => 200, 'message' => 'User Detail', 'data' => $userDetail], 200);
    }
    public function updateprofile(Request $request)
    {
        $userId = Auth::user()->id;
        $email = $request->email;
        $name = $request->name;
        // ->where("email",$email)
        $checkemail = User::where("id", $userId)->first();
        if (!empty($checkemail)) {
            if ($request->hasFile('image')) {
                $profile_photo_img_path = $request->image->store('profile_photo_path');
                $checkemail->profile_photo_path = $profile_photo_img_path;
            }

            $checkemail->name = $name;
            $checkemail->save();
        } else {

            $random_verify_str = substr(md5(mt_rand()), 0, 49);

            $user = User::find($userId);
            if ($request->hasFile('image')) {
                $profile_photo_img_path = $request->image->store('profile_photo_path');
                $user->profile_photo_path = $profile_photo_img_path;
            }
            $user->name = $name;
            $user->temp_email = $email;
            $user->verify_code = $random_verify_str;
            $user->save();
            // $this->sendVerificationMail($email,$verify_code,$name);
        }
        $userDetail = User::find($userId);
        return response()->json(['statusCode' => 200, 'message' => 'User Detail', 'data' => $userDetail], 200);
    }
    public function sendVerificationMail($email, $verify_code, $name)
    {
        $req_data = [];


        $verify_link = '<a href="' . env('APP_URL') . '/reset_password/verify_mail/' . $verify_code . '" style="background-color: #7087A3; font-size: 12px; padding: 10px 15px; color: #fff; text-decoration: none">Verify Email</a>';

        $user_email = $email;

        $mail_data = [
            'receiver' => ucwords($name),
            'email' => $user_email,
            'web_url' => env('APP_URL'),
            'verify_link' => $verify_link
        ];
        try {
            if (env('MAIL_ON_OFF_STATUS') == "on") {
                \Mail::send('mails.reset_password_mail', $mail_data, function ($message) use ($mail_data) {
                    $message->to($mail_data['email']);
                    $message->from(env('MAIL_FROM_ADDRESS'), env('APP_NAME'));
                    $message->subject(env('APP_NAME') . ' Verify Email');
                });
            }

            $message = 'Verify Email link is send to your email. Please check your account...!';
            return $this->json_view(true, $req_data, $message);
        } catch (\Throwable $e) {
            return $this->sendFailed($e->getMessage() . ' on line ' . $e->getLine(), 400);
        }
    }
    public function changePassword(Request $request)
    {
        $userId = Auth::user()->id;
        $oldPassword = $request->oldPassword;
        $password = $request->password;
        $user = User::where('id', $userId)->first();
        if ($user) {



            if (Hash::check($oldPassword, $user->password)) {
                $Data = array(
                    "password" => Hash::make($password),
                    "verify_code" => ''
                );
                $data = User::where('id', @$user->id)->update($Data);
                return response()->json(['statusCode' => 200, 'message' => 'Password changed successfully', 'data' => $user], 200);
            } else {
                $message = "Invalid credentials";
                return $this->json_view(false, [], $message, '101');
            }
        }
    }
    public function tranactions(Request $request)
    {
        $type = $request->type;
        $fromDate = $request->fromDate;
        $toDate = $request->toDate;
        $userId = Auth::user()->id;

        $type = $request->type == "book" ? 4 : ($request->type == "seminar" ? 2 : ($request->type == "flashcard" ? 3 : ($request->type == "course" ? 1 : '')));
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
            foreach ($val->orderDetails as $val1) {
                $allpackage[] = $val1->package->package_title;

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
            $result[$key]['packageName'] = $title;
            $result[$key]['courseName'] = $course;
            $result[$key]['enrollDate'] = $val->created_at;
            $result[$key]['expireDate'] = $expireDate;
            $result[$key]['status'] = $val->payment_status == 1 ? "Success" : "failed";
        }
        return response()->json(['statusCode' => 200, 'message' => 'transaction', 'data' => $result], 200);
    }
    public function contactDetail()
    {
        $result = array("contactNo" => 9876543210, "email" => "test@gmail.com");
        return response()->json(['statusCode' => 200, 'message' => 'Success', 'data' => $result], 200);
    }
    public function addVideoComment(Request $request)
    {
        $tutorial_id = $request->tutorial_id;
        $comment = $request->comment;
        $userId = Auth::user()->id;
        $VideoComment = new VideoComment();
        $VideoComment->user_id = $userId;
        $VideoComment->tutorial_id = $tutorial_id;
        $VideoComment->comment = $comment;
        $VideoComment->status = 1;
        $VideoComment->save();
        return response()->json(['statusCode' => 200, 'message' => 'Comment Added Successfully', 'data' => $VideoComment], 200);
    }
    public function applyfilter(Request $request)
    {
        $category_ids = $request->category_ids;
        $subcategoryIds = $request->subcategoryIds;

        if (!empty($subcategoryIds)) {
            $getallCategory = SubCategory::orderBy('id', 'asc')->whereIn('id', $subcategoryIds)->pluck('category_id')->toArray();

            $getallCategory = array_unique($getallCategory);

            if (!empty($category_ids)) {
                $selectedCategory = explode(",", $category_ids);

                $selectedCategory = array_merge($selectedCategory, $getallCategory);

                $getallCategory = array_unique($selectedCategory);

                $category_ids = implode(",", $getallCategory);

                $request->category_ids = $category_ids;
            } else {
                $category_ids = implode(",", $getallCategory);
            }
        }

        $response = $this->categorylistlogicfilter($request);

        Log::info('response' . json_encode($response));

        $selectedCategory = explode(",", $category_ids);

        // parse each element of selectedCategory to integer
        $selectedCategory = array_map('intval', $selectedCategory);

        $finalresult = [];

        foreach ($response['data']['category_list'] as $val) {
            if (in_array($val['category_id'], $selectedCategory)) {
                $questionIds = explode(",", $val['filter_questions_id']);

                foreach ($questionIds as $val2) {
                    if (!empty($val2)) {
                        $finalresult[] = $val2;
                    }
                }
            }
        }

        $finalResponse['selected_question_arr'] = implode(",", $finalresult);
        $finalResponse['totalQuestionCount'] = count($finalresult);

        return response()->json(['statusCode' => 200, 'message' => 'Comment Added Successfully', 'data' => $finalResponse], 200);
    }
    public function applyfilterNew(Request $request)
    {

        $request->validate([
            'subcategoryIds' => 'required',
            'questionsCount' => 'required',
            'filter' => 'required',
        ]);


        $subcategoryIds = $request->subcategoryIds;
        $questionsCount = $request->questionsCount;
        $filter = $request->filter;
        $course_id = $request->course_id;

        $user_id = auth()->user()->id;

        // check if user has bought the plan
        $userOrders = Order::where("user_id", $user_id)->pluck("id");
        $userOrderDetails = OrderDetail::where(['particular_record_id' => $course_id, 'package_for' => '1'])->whereIn("order_id", $userOrders);

        if (!empty($userOrderDetails)) {
            $planPurchased = true;
        } else {
            $planPurchased = false;
        }

        if (!$planPurchased) {
            $testModeQuestionIds = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
        }

        // fetch all the questions for the given subcategory ids
        $allSelectedQuestionIdsQuery = QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '", course_id)')->whereIn('sub_category_ids', $subcategoryIds)->where('status', 1);

        if (!$planPurchased) {
            $allSelectedQuestionIdsQuery->whereIn('id', $testModeQuestionIds);
        }

        $allSelectedQuestionIds = $allSelectedQuestionIdsQuery->pluck('id')->toArray();



        if ($filter == 'all') {
            $selectedQuestionIds = $allSelectedQuestionIds;
        } else if ($filter == 'new') {
            $attemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();

            $selectedQuestionIds = array_diff($allSelectedQuestionIds, $attemptedQuestionIds);
        } else if ($filter == 'newAndIncorrect') {
            $correctAttemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();

            $selectedQuestionIds = array_diff($allSelectedQuestionIds, $correctAttemptedQuestionIds);
        }

        // randomly pick values from the array
        $questionsCount = max(0, min($questionsCount, count($selectedQuestionIds)));

        $randomKeys = array_rand($selectedQuestionIds, $questionsCount);
        $randomQuestionIds = [];

        foreach ((array) $randomKeys as $key) {
            $randomQuestionIds[] = $selectedQuestionIds[$key];
        }

        return response()->json(['selectedQuestionIds' => $randomQuestionIds]);
    }

    public function categorylistlogicfilter(Request $request)
    {
        if ($request->filter_type == 3) {
            return $this->allQuestionFilterType($request);
        } elseif ($request->filter_type == 2) {
            return $this->newWithIncorrectFilterType($request);
        } else {
            return $this->newQuestionFilterType($request);
        }
    }

    public function newQuestionFilterType($request)
    {
        $data = $request->all();

        $req_data = [];

        $record_type = $request->record_type; // 1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3
        $tutorial_id = $request->tutorial_id; // 1
        $category_ids = $request->category_ids; // 1
        $subcategoryIds = $request->subcategoryIds; // 1
        $subcategoryIds = explode(",", $subcategoryIds); // 1
        $questionCount = $request->questionCount; // 1
        $totalQuestion = $request->total_question; // 1
        $percent = 100;

        if ($totalQuestion > 0) {
            $percent = ($questionCount / $totalQuestion * 100);
        }

        if (empty($category_ids) && !empty($subcategoryIds)) {
            $getallCategory = SubCategory::orderBy('id', 'asc')->whereIn('id', $subcategoryIds)->pluck('category_id')->toArray();
            $category_ids = implode(",", $getallCategory);
        }

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];

        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();

        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);

            if (in_array($course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        $buy_question_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_question_id')->join(',');

        $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',', $buy_question_ids) : [];
        $buyQuesIdArr = array_unique($buyQuesIdArr);

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);

        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::with('subCategory')->where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::with('subCategory')->whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }

        if (!empty($questionCount) && $questionCount > 0) {
            $allCategory = explode(",", $category_ids);
            $parcentToDeduct = ($questionCount / $totalQuestion) * 100;

            foreach ($allCategory as $categoryId) {
                $totalQuestionCount = QuestionAnswer::where(['status' => 1, 'category_id' => $categoryId])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

                $selectedQuestion = ($totalQuestionCount * $parcentToDeduct) / 100;
                $selectedQuestion = ceil($selectedQuestion);

                $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $categoryId)->first();

                $data = array("user_id" => $user_id, "category_id" => $categoryId, "question_count" => $selectedQuestion);

                if (!empty($checkCategory)) {
                    QuestionTempFilter::where("id", $checkCategory->id)->update($data);
                } else {
                    QuestionTempFilter::insert($data);
                }
            }
        } else {
            QuestionTempFilter::where("user_id", $user_id)->delete();
        }

        $questionAssignCount = 1;
        $overallQuestion = 0;

        $tstModeQId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();


        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();
            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();

            $checkSelectedSubCategory = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->whereIn("id", $subcategoryIds)->first();

            $isApplySubCategoryCondition = 0;

            if (!empty($checkSelectedSubCategory)) {
                $isApplySubCategoryCondition = 1;
            }

            $subcategoryWiseQuestionList = array();
            $subcategoryWiseQuestionListCount = 0;

            foreach ($getSubCat as $subCatDt) {
                $sub_filter_questions_id = [];

                $sub_attenptQuess = AttemptQuestion::select('question_id')->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                if ($is_plan_exist == 1) {
                    $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                }

                $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();

                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else {
                    $sub_queQueryCount->whereIn('id', $tstModeQId);
                }

                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;


                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                if (!empty(@$allCategory)) {
                    $sub_queQuery = $sub_queQuery->whereIn("category_id", $allCategory);
                }

                if (!empty($subcategoryIds) && $isApplySubCategoryCondition == 1) {
                    $sub_queQuery = $sub_queQuery->whereIn("sub_category_ids", $subcategoryIds);
                }

                if ($is_plan_exist == 1) {
                    $sub_queQuery->whereIn('id', $buyQuesIdArr);
                } else {
                    $sub_queQuery->whereIn('id', $tstModeQId);
                }

                if (!empty($checkCategory)) {
                    $sub_queQuery = $sub_queQuery->limit($checkCategory->question_count);
                }

                $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();
                $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                $subcategoryQuestionCount = $tot_sub_cat_que_dt * $percent / 100;
                $subcategoryQuestionCount = ceil($subcategoryQuestionCount);
                $totalquestionAssigncountInSubCate = 0;
                $newSubCategoryQuestion = array();

                if ($percent < 100) {

                    $countQuestion = 1;
                    foreach ($sub_filter_questions_id as $q) {
                        if ($subcategoryQuestionCount >= $countQuestion && $questionAssignCount <= $questionCount) {
                            $newSubCategoryQuestion[] = $q;
                            $questionAssignCount++;
                            $totalquestionAssigncountInSubCate++;
                            $subcategoryWiseQuestionList[] = $q;
                        }
                        $countQuestion++;
                    }
                } else {
                    foreach ($sub_filter_questions_id as $q) {
                        $newSubCategoryQuestion[] = $q;
                        $questionAssignCount++;
                        $totalquestionAssigncountInSubCate++;
                        $subcategoryWiseQuestionList[] = $q;
                    }
                }

                $tot_sub_cat_que_dt = count($newSubCategoryQuestion);

                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                    $newSubCategoryQuestion = array_unique($newSubCategoryQuestion);
                }

                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id);
                if (!empty($checkCategory)) {
                    $getAllQueCount = $getAllQueCount->limit($checkCategory->question_count);
                }

                $getAllQueCount = $getAllQueCount->count();

                $total_all_question_list = $totalquestionAssigncountInSubCate;

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $sub_queQueryCount,
                    'total_attenpt' => 0,
                    'total_correct' => 0,
                    'total_incorrect' => 0,
                    'total_correct_percentage' => 0,
                    'total_incorrect_percentage' => 0,
                    'total_white_percentage' => 100,
                    'sub_filter_questions_id' => (count($newSubCategoryQuestion) > 0) ? implode(',', $newSubCategoryQuestion) : "",
                ];
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];

            $filter_questions_id = $subcategoryWiseQuestionList;

            $tot_que_dt = $subcategoryWiseQuestionListCount;

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);

            $allAttemptQuestionList = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get();
            $total_attenpt = count($allAttemptQuestionList);
            $tot_cat_correct = 0;
            foreach ($allAttemptQuestionList as $vall) {
                if ($vall->is_correct == "1") {
                    $tot_cat_correct = $tot_cat_correct + 1;
                }
                if ($vall->is_correct == "0") {
                    $total_cat_incorrect = $total_cat_incorrect + 1;
                }
            }

            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }

            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }

            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect),
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            $overallQuestion = $overallQuestion + $tot_que_dt;
            if ($filter_type != '2') {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }
            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') {
                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';


                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }
                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }

                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;
                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }

            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();
        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }
        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];


        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->get()->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->get()->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }


            $req_data['total_questions'] = $overallQuestion;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;
            $req_data['total_Attempted'] = 0;
            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2') {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }
    public function newWithIncorrectFilterType($request)
    {
        $data = $request->all();

        $req_data = [];

        $record_type = $request->record_type; //1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3

        $tutorial_id = $request->tutorial_id; // 1
        $category_ids = $request->category_ids; // 1
        $subcategoryIds = $request->subcategoryIds; // 1
        $subcategoryIds = explode(",", $subcategoryIds); // 1
        $questionCount = $request->questionCount; // 1
        $totalQuestion = $request->total_question; // 1

        $percent = 100;

        if ($totalQuestion > 0) {
            $percent = ($questionCount / $totalQuestion * 100);
        }

        if (empty($category_ids) && !empty($subcategoryIds)) {
            $getallCategory = SubCategory::orderBy('id', 'asc')->whereIn('id', $subcategoryIds)->pluck('category_id')->toArray();
            $category_ids = implode(",", $getallCategory);
        }

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];

        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();

        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        $buy_question_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_question_id')->join(',');

        $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',', $buy_question_ids) : [];
        $buyQuesIdArr = array_unique($buyQuesIdArr);

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);

        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }

        if (!empty($questionCount) && $questionCount > 0) {
            $allCategory = explode(",", $category_ids);
            $parcentToDeduct = ($questionCount / $totalQuestion) * 100;

            foreach ($allCategory as $categoryId) {

                $totalQuestionCount = QuestionAnswer::where(['status' => 1, 'category_id' => $categoryId])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

                $selectedQuestion = ($totalQuestionCount * $parcentToDeduct) / 100;
                $selectedQuestion = ceil($selectedQuestion);

                $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $categoryId)->first();

                $data = array("user_id" => $user_id, "category_id" => $categoryId, "question_count" => $selectedQuestion);

                if (!empty($checkCategory)) {
                    QuestionTempFilter::where("id", $checkCategory->id)->update($data);
                } else {
                    QuestionTempFilter::insert($data);
                }
            }
        } else {
            QuestionTempFilter::where("user_id", $user_id)->delete();
        }

        $overallQuestion = 0;
        $overallAttemptedQuestion = 0;


        $questionAssignCount = 1;
        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();
            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();

            $checkSelectedSubCategory = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->whereIn("id", $subcategoryIds)->first();

            $isApplySubCategoryCondition = 0;

            if (!empty($checkSelectedSubCategory)) {
                $isApplySubCategoryCondition = 1;
            }


            $subcategoryWiseQuestionList = array();
            $subcategoryWiseQuestionListCount = 0;

            foreach ($getSubCat as $subCatDt) {
                $sub_filter_questions_id = [];

                $sub_attenptQuess = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)');

                if ($is_plan_exist == 1) {
                    $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                }

                $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();

                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereNotIn('id', $sub_attenptQueArr)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $sub_queQuery->where('test_mode',1); 

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $sub_queQueryCount->whereIn('id', $testModeQueId);
                }
                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;

                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                if (!empty(@$allCategory)) {
                    $sub_queQuery = $sub_queQuery->whereIn("category_id", $allCategory);
                }

                if (!empty($subcategoryIds) && $isApplySubCategoryCondition == 1) {
                    $sub_queQuery = $sub_queQuery->whereIn("sub_category_ids", $subcategoryIds);
                }

                if ($is_plan_exist == 1) {
                    $sub_queQuery->whereIn('id', $buyQuesIdArr);
                } else {
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $sub_attenptQueArr)->pluck('question_id')->toArray();

                    $sub_queQuery->whereIn('id', $testModeQueId);
                }

                if (!empty($checkCategory)) {
                    $sub_queQuery = $sub_queQuery->limit($checkCategory->question_count);
                }

                $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();
                $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                $subcategoryQuestionCount = $tot_sub_cat_que_dt * $percent / 100;
                $subcategoryQuestionCount = ceil($subcategoryQuestionCount);
                $totalquestionAssigncountInSubCate = 0;
                $newSubCategoryQuestion = array();

                if ($percent < 100) {

                    $countQuestion = 1;
                    foreach ($sub_filter_questions_id as $q) {
                        if ($subcategoryQuestionCount >= $countQuestion && $questionAssignCount <= $questionCount) {
                            $newSubCategoryQuestion[] = $q;
                            $questionAssignCount++;
                            $totalquestionAssigncountInSubCate++;
                            $subcategoryWiseQuestionList[] = $q;
                        }
                        $countQuestion++;
                    }
                } else {
                    foreach ($sub_filter_questions_id as $q) {

                        $newSubCategoryQuestion[] = $q;
                        $questionAssignCount++;
                        $totalquestionAssigncountInSubCate++;
                        $subcategoryWiseQuestionList[] = $q;
                    }
                }

                $tot_sub_cat_que_dt = count($newSubCategoryQuestion);

                $queQuery2 = AttemptQuestion::whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id])->where('is_correct', '0');

                if ($is_plan_exist == 1) {
                    $queQuery2->whereIn('question_id', $buyQuesIdArr);
                }

                $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                $sub_filter_questions_id = array_merge($newSubCategoryQuestion, $questions_id_Arr2);

                if (!empty($filter_questions_id)) {
                    $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
                } else {
                    $filter_questions_id = $sub_filter_questions_id;
                }

                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                    $newSubCategoryQuestion = array_unique($newSubCategoryQuestion);
                }


                if ($filter_type == '1' || $filter_type == '2') {
                    $tot_sub_cat_attempt = 0;
                }

                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id);
                if (!empty($checkCategory)) {
                    $getAllQueCount = $getAllQueCount->limit($checkCategory->question_count);
                }

                $getAllQueCount = $getAllQueCount->count();

                $tot_cat_correct = 0;

                $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->count();


                $total_all_question_list = $totalquestionAssigncountInSubCate;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;


                $at = ($total_all_correct + $total_cat_incorrect);
                $c = $total_all_correct;
                $i = $total_cat_incorrect;

                if ($total_all_question_list == 0) {
                    $at = 0;
                    $c = 0;
                    $i = 0;
                }

                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                if ($total_all_question_list == 0) {
                    $total_correct_percentage = 0;
                    $total_incorrect_percentage = 0;
                    $total_white_percentage = 0;
                }

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $sub_queQueryCount,
                    'total_attenpt' => $at,
                    'total_correct' => $c,
                    'total_incorrect' => $i,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_filter_questions_id' => (count($newSubCategoryQuestion) > 0) ? implode(',', $newSubCategoryQuestion) : "",
                ];
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];


            $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

            $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

            $total_attenpt = count($attenptQueArr);

            $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

            $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

            if ($is_plan_exist == 1) {
                $queQuery->whereIn('id', $buyQuesIdArr);
            } else {
                $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();

                $queQuery->whereIn('id', $testModeQueId);
            }

            if (!empty($checkCategory)) {
                $queQuery = $queQuery->limit($checkCategory->question_count);
            }

            $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
            $queQuery_new_1Que = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

            if ($is_plan_exist == 1) {
                $queQuery_newQue->whereIn('id', $buyQuesIdArr);
            } else {
                $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                $queQuery_new_1Que->whereIn('id', $testModeQueId);
            }

            $queQuery_new = $queQuery_newQue->count();

            $tot_que_dt =  $subcategoryWiseQuestionListCount;
            $tot_all_que = $tot_all_que + $queQuery_new;

            $queQuery2 = AttemptQuestion::where(['user_id' => $user_id, 'category_id' => $catDt->id])->where('is_correct', '0');

            if (isset($request->tutorial_id)) {
                $queQuery2->where('tutorial_id', $tutorial_id);
            }
            if ($is_plan_exist == 1) {
                $queQuery2->whereIn('question_id', $buyQuesIdArr);
            } else {
                $queQuery2->whereIn('question_id', $testModeQueId);
            }

            $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

            $tot_cat_correct = count($questions_id_Arr2);

            $filter_questions_id = $subcategoryWiseQuestionList;

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);

            $total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->count();

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();

            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }

            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }

            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect), //$total_attenpt,
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') {
                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';


                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }
                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }

                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;
                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }
            $overallQuestion = $overallQuestion + $tot_que_dt;
            $overallAttemptedQuestion = $overallAttemptedQuestion + $total_attenpt;
            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();

        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }

        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];

        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->get()->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->get()->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $overallQuestion) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $overallQuestion) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $overallQuestion) * 100);
            }


            $req_data['total_questions'] = $overallQuestion;
            $req_data['total_Attempted'] = $overallAttemptedQuestion;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;

            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2') {
            $req_data['category_list'] = $catArrTutorial;
        }

        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }

    public function allQuestionFilterType($request)
    {
        $data = $request->all();
        $start = microtime(true);
        $req_data = [];

        $record_type = $request->record_type; //1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3

        $tutorial_id = $request->tutorial_id; // 1
        $category_ids = $request->category_ids; // 1
        $subcategoryIds = $request->subcategoryIds; // 1
        $subcategoryIds = explode(",", $subcategoryIds); // 1
        $questionCount = $request->questionCount; // 1
        $totalQuestion = $request->total_question; // 1

        $percent = 100;
        if ($totalQuestion > 0) {
            $percent = ($questionCount / $totalQuestion * 100);
        }
        if (empty($category_ids) && !empty($subcategoryIds)) {
            $getallCategory = SubCategory::orderBy('id', 'asc')->whereIn('id', $subcategoryIds)->pluck('category_id')->toArray();
            $category_ids = implode(",", $getallCategory);
        }

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];

        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();

        $is_plan_exist = ($check_plan > 0) ? '1' : '0';
        $is_plan_exist = ($is_plan_exist == 0 && in_array($course_id, $this->getfreecourse($user_id))) ? 1 : $is_plan_exist;

        $buy_question_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_question_id')->join(',');

        $buyQuesIdArr = (!empty($buy_question_ids)) ? explode(',', $buy_question_ids) : [];
        $buyQuesIdArr = array_unique($buyQuesIdArr);

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);

        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }

        if (!empty($questionCount) && $questionCount > 0) {
            $allCategory = explode(",", $category_ids);
            $parcentToDeduct = ($questionCount / $totalQuestion) * 100;

            foreach ($allCategory as $categoryId) {
                $totalQuestionCount = QuestionAnswer::where(['status' => 1, 'category_id' => $categoryId])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

                $selectedQuestion = ($totalQuestionCount * $parcentToDeduct) / 100;
                $selectedQuestion = ceil($selectedQuestion);

                $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $categoryId)->first();

                $data = array("user_id" => $user_id, "category_id" => $categoryId, "question_count" => $selectedQuestion);

                if (!empty($checkCategory)) {
                    QuestionTempFilter::where("id", $checkCategory->id)->update($data);
                } else {
                    QuestionTempFilter::insert($data);
                }
            }
        } else {
            QuestionTempFilter::where("user_id", $user_id)->delete();
        }

        $overallQuestion = 0;
        $overallAttemptedQuestion = 0;


        $questionAssignCount = 1;
        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();

        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();
            $getSubCat = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->get();

            $checkSelectedSubCategory = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->whereIn("id", $subcategoryIds)->first();

            $isApplySubCategoryCondition = 0;

            if (!empty($checkSelectedSubCategory)) {
                $isApplySubCategoryCondition = 1;
            }

            $subcategoryWiseQuestionList = array();
            $subcategoryWiseQuestionListCount = 0;

            foreach ($getSubCat as $subCatDt) {
                $sub_filter_questions_id = [];

                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else {
                    $sub_queQueryCount->whereIn('id', $testModeQueId);
                }
                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;

                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id]);

                if (!empty(@$allCategory)) {
                    $sub_queQuery = $sub_queQuery->whereIn("category_id", $allCategory);
                }

                $sub_queQuery = $sub_queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if (!empty($subcategoryIds) && $isApplySubCategoryCondition == 1) {
                    $sub_queQuery = $sub_queQuery->whereIn("sub_category_ids", $subcategoryIds);
                }

                if ($is_plan_exist == 1) {
                    $sub_queQuery->whereIn('id', $buyQuesIdArr);
                } else {
                    $sub_queQuery->whereIn('id', $testModeQueId);
                }

                $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();

                $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                $subcategoryQuestionCount = $tot_sub_cat_que_dt * $percent / 100;
                $subcategoryQuestionCount = ceil($subcategoryQuestionCount);
                $totalquestionAssigncountInSubCate = 0;
                $newSubCategoryQuestion = array();

                if ($percent < 100) {
                    $countQuestion = 1;

                    foreach ($sub_filter_questions_id as $q) {
                        if ($subcategoryQuestionCount >= $countQuestion && $questionAssignCount <= $questionCount) {
                            $questionAssignCount++;
                            $newSubCategoryQuestion[] = $q;
                            $subcategoryWiseQuestionList[] = $q;
                        }
                        $countQuestion++;
                        $totalquestionAssigncountInSubCate++;
                    }
                } else {
                    foreach ($sub_filter_questions_id as $q) {

                        $questionAssignCount++;
                        $totalquestionAssigncountInSubCate++;
                        $newSubCategoryQuestion[] = $q;
                        $subcategoryWiseQuestionList[] = $q;
                    }
                }

                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                    $newSubCategoryQuestion = array_unique($newSubCategoryQuestion);
                }

                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id);

                if (!empty($checkCategory)) {
                    $getAllQueCount = $getAllQueCount->limit($checkCategory->question_count);
                }
                $getAllQueCount = $getAllQueCount->count();

                $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '1'])->count();

                $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id, 'is_correct' => '0'])->count();


                $total_all_question_list = $totalquestionAssigncountInSubCate;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;


                $at = ($total_all_correct + $total_cat_incorrect);
                $c = $total_all_correct;
                $i = $total_cat_incorrect;


                if ($total_all_question_list == 0) {
                    $at = 0;
                    $c = 0;
                    $i = 0;
                }


                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }


                if ($total_all_question_list == 0) {
                    $total_correct_percentage = 0;
                    $total_incorrect_percentage = 0;
                    $total_white_percentage = 0;
                }

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $sub_queQueryCount,
                    'total_attenpt' => $at,
                    'total_correct' => $c,
                    'total_incorrect' => $i,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_filter_questions_id' => (count($newSubCategoryQuestion) > 0) ? implode(',', $newSubCategoryQuestion) : "",
                ];

                $filter_questions_id = array_merge($filter_questions_id, $newSubCategoryQuestion);
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];

            if ($filter_type == '1' || $filter_type == '') {
                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

                $queQueryT = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                if (!empty($attenptQueArr)) {
                    $queQueryT->whereNotIn('id', $attenptQueArr);
                }


                $queQuery = $queQueryT->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else {
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }

                $filter_questions_id = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '2') {
                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

                $total_attenpt = count($attenptQueArr);

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else {
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }
                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                $queQuery_new_1Que = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery_newQue->whereIn('id', $buyQuesIdArr);
                } else {
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery_new_1Que->whereIn('id', $testModeQueId);
                }


                $queQuery_new = $queQuery_newQue->count();
                $queQuery_new_1 = $queQuery_new_1Que->count();

                $tot_que_dt =  $queQuery_new_1;
                $tot_all_que = $tot_all_que + $queQuery_new;

                $queQuery2 = AttemptQuestion::where(['user_id' => $user_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                if (isset($request->tutorial_id)) {
                    $queQuery2->where('tutorial_id', $tutorial_id);
                }
                if ($is_plan_exist == 1) {
                    $queQuery2->whereIn('question_id', $buyQuesIdArr);
                } else {
                    $queQuery2->whereIn('question_id', $testModeQueId);
                }

                $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                $tot_cat_correct = count($questions_id_Arr2);

                $filter_questions_id = array_merge($filter_questions_id, $questions_id_Arr1, $questions_id_Arr2);
                $questions_id_Arr1 = $subcategoryWiseQuestionList;

                $tot_que_dt = $subcategoryWiseQuestionListCount;

                $que_total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id]);

                if ($is_plan_exist == 0) {
                    $que_total_attenpt->whereIn('question_id', $buyQuesIdArr);
                }
                $total_attenpt = $que_total_attenpt->get()->count();

                $que_tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '1');

                if ($is_plan_exist == 0) {
                    $que_tot_cat_correct->whereIn('question_id', $buyQuesIdArr);
                }
                $tot_cat_correct = $que_tot_cat_correct->get()->count();

                $que_total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                if ($is_plan_exist == 0) {
                    $que_total_cat_incorrect->whereIn('question_id', $buyQuesIdArr);
                }
                $total_cat_incorrect = $que_total_cat_incorrect->get()->count();

                $filter_questions_id = $subcategoryWiseQuestionList;
            }

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);

            $tot_que_dt = count($filter_questions_id);

            $total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->count();

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();


            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }

            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }

            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;

            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }

            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect),
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            if ($filter_type != '2') {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }

            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') {
                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';

                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }

                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }

                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;

                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }
            $overallQuestion = $overallQuestion + $tot_que_dt;
            $overallAttemptedQuestion = $overallAttemptedQuestion + $total_attenpt;
            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();
        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }
        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];


        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->get()->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->get()->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;

            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $overallQuestion) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $overallQuestion) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $overallQuestion) * 100);
            }


            $req_data['total_questions'] = $overallQuestion;
            $req_data['total_Attempted'] = $overallAttemptedQuestion;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;

            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2')  // for tutorial
        {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }
    public function allQuestionFilterTypeNew(Request $request)
    {
        $data = $request->all();
        $start = microtime(true);
        $req_data = [];

        $record_type = $request->record_type; //1
        $course_id = $request->course_id; // 1
        $filter_type = $request->filter_type; // 3

        $tutorial_id = $request->tutorial_id; // 1
        $category_ids = $request->category_ids; // 1
        $subcategoryIds = $request->subcategoryIds; // 1
        $subcategoryIds = explode(",", $subcategoryIds); // 1
        $questionCount = $request->questionCount; // 1
        $totalQuestion = $request->total_question; // 1

        $percent = 100;
        if ($totalQuestion > 0) {
            $percent = ($questionCount / $totalQuestion * 100);
        }
        if (empty($category_ids) && !empty($subcategoryIds)) {
            $getallCategory = SubCategory::orderBy('id', 'asc')->whereIn('id', $subcategoryIds)->pluck('category_id')->toArray();
            $category_ids = implode(",", $getallCategory);
        }

        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';
        $tot_all_que = '0';

        $selected_question_arr = [];

        TempSrQuestion::where(['user_id' => $user_id])->delete();

        $order = Order::where('user_id', $user_id)
            ->whereHas('orderDetails', function ($query) use ($course_id) {
                $query->where('particular_record_id', $course_id)
                    ->where('package_for', 1)
                    ->where('expiry_date', '>', date('Y-m-d H:i:s'));
            })->first();

        $is_plan_exist = !empty($order)  ? '1' : '0';
        $is_plan_exist = ($is_plan_exist == 0 && in_array($course_id, $this->getfreecourse($user_id))) ? 1 : $is_plan_exist;
        $buyQuesIdArr = array();
        $buyTutIdArr = array();
        foreach ($order->orderDetails as $val) {
            $allquestion = explode(',', $val->package->assign_question_id);
            $buyQuesIdArr = array_merge($buyQuesIdArr, $allquestion);

            $alltutorial = explode(',', $val->package->assign_tutorial_id);
            $buyTutIdArr = array_merge($buyTutIdArr, $alltutorial);
        }

        $buyQuesIdArr = array_unique($buyQuesIdArr);
        $buyTutIdArr = array_unique($buyTutIdArr);

        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }
        if (!empty($questionCount) && $questionCount > 0) {
            $allCategory = explode(",", $category_ids);
            $parcentToDeduct = ($questionCount / $totalQuestion) * 100;

            foreach ($allCategory as $categoryId) {

                $totalQuestionCount = QuestionAnswer::where(['status' => 1, 'category_id' => $categoryId])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();
                $selectedQuestion = ($totalQuestionCount * $parcentToDeduct) / 100;
                $selectedQuestion = ceil($selectedQuestion);

                $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $categoryId)->first();
                $data = array("user_id" => $user_id, "category_id" => $categoryId, "question_count" => $selectedQuestion);
                if (!empty($checkCategory)) {
                    QuestionTempFilter::where("id", $checkCategory->id)->update($data);
                } else {
                    QuestionTempFilter::insert($data);
                }
            }
        } else {
            QuestionTempFilter::where("user_id", $user_id)->delete();
        }

        $overallQuestion = 0;
        $overallAttemptedQuestion = 0;


        $questionAssignCount = 1;
        $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();



        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $tot_sub_cat_attempt = '';
            $tot_sub_cat_correct = '';
            $tot_sub_cat_incorrect = '';
            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();

            $checkSelectedSubCategory = $catDt->subCategory->whereIn("id", $subcategoryIds)->first();

            $isApplySubCategoryCondition = 0;
            if (!empty($checkSelectedSubCategory)) {
                $isApplySubCategoryCondition = 1;
            }


            $subcategoryWiseQuestionList = array();
            $subcategoryWiseQuestionListNew = array();
            $subcategoryWiseQuestionListCount = 0;

            foreach ($catDt->subCategory as $subCatDt) {
                $sub_filter_questions_id = [];


                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $sub_queQuery->where('test_mode',1); 


                    $sub_queQueryCount->whereIn('id', $testModeQueId);
                }
                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;


                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id]);
                if (!empty(@$allCategory)) {
                    $sub_queQuery = $sub_queQuery->whereIn("category_id", $allCategory);
                }

                $sub_queQuery = $sub_queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                if (!empty($subcategoryIds) && $isApplySubCategoryCondition == 1) {
                    $sub_queQuery = $sub_queQuery->whereIn("sub_category_ids", $subcategoryIds);
                }

                if ($is_plan_exist == 1) {
                    $sub_queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $sub_queQuery->where('test_mode',1); 

                    $sub_queQuery->whereIn('id', $testModeQueId);
                }
                $sub_filter_questions_id = $sub_queQuery->pluck('id')->toArray();



                $tot_sub_cat_que_dt = count($sub_filter_questions_id);
                $subcategoryQuestionCount = $tot_sub_cat_que_dt * $percent / 100;
                $subcategoryQuestionCount = ceil($subcategoryQuestionCount);
                $totalquestionAssigncountInSubCate = 0;
                $newSubCategoryQuestion = array();
                if ($percent < 100) {

                    $countQuestion = 1;
                    foreach ($sub_filter_questions_id as $q) {

                        // print_r($sub_filter_questions_id); exit;
                        if ($subcategoryQuestionCount >= $countQuestion && $questionAssignCount <= $questionCount) {
                            $newSubCategoryQuestion[] = $q;
                            $questionAssignCount++;

                            $subcategoryWiseQuestionList[] = $q;
                        }
                        $countQuestion++;
                        // this in before inside the if for reduce subcatgory count
                        $totalquestionAssigncountInSubCate++;
                        $subcategoryWiseQuestionListNew[] = $q;
                    }
                } else {
                    foreach ($sub_filter_questions_id as $q) {

                        $newSubCategoryQuestion[] = $q;
                        $questionAssignCount++;
                        $totalquestionAssigncountInSubCate++;
                        $subcategoryWiseQuestionList[] = $q;
                        $subcategoryWiseQuestionListNew[] = $q;
                    }
                }

                $allAttempt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->whereIn('question_id', $newSubCategoryQuestion);
                $tot_sub_cat_attempt = $allAttempt->count();
                $tot_sub_cat_correct =  $allAttempt->where('is_correct', '1')->count();
                $tot_sub_cat_incorrect =  $allAttempt->where('is_correct', '0')->count();



                if (count($sub_filter_questions_id) > 0) {
                    $sub_filter_questions_id = array_unique($sub_filter_questions_id);
                    $newSubCategoryQuestion = array_unique($newSubCategoryQuestion);
                }


                if ($filter_type == '1' || $filter_type == '2') {
                    $tot_sub_cat_attempt = 0;
                }



                $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->where('category_id', $subCatDt->category_id)->where('sub_category_ids', $subCatDt->id);
                if (!empty($checkCategory)) {

                    $getAllQueCount = $getAllQueCount->limit($checkCategory->question_count);
                }
                $getAllQueCount = $getAllQueCount->count();

                $totalAttemptedSubCategory = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $subCatDt->category_id, 'sub_category_ids' => $subCatDt->id]);

                $tot_cat_correct =  $totalAttemptedSubCategory->where("is_correct", '1')->count();
                $total_cat_incorrect =  $totalAttemptedSubCategory->where("is_correct", '0')->count();


                $total_all_question_list = $totalquestionAssigncountInSubCate;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;


                $at = ($total_all_correct + $total_cat_incorrect);
                $c = $total_all_correct;
                $i = $total_cat_incorrect;


                if ($total_all_question_list == 0) {
                    $at = 0;
                    $c = 0;
                    $i = 0;
                }


                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }


                if ($total_all_question_list == 0) {
                    $total_correct_percentage = 0;
                    $total_incorrect_percentage = 0;
                    $total_white_percentage = 0;
                }

                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => $sub_queQueryCount,
                    'total_attenpt' => $at,
                    'total_correct' => $c,
                    'total_incorrect' => $i,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_filter_questions_id' => (count($newSubCategoryQuestion) > 0) ? implode(',', $newSubCategoryQuestion) : "",
                ];
                /*   if($subCatDt->id=="162")
                    {
                        print_r($sub_cat_arr); exit;
                    } */
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';
            $sub_filter_questions_id = [];

            if ($filter_type == '1' || $filter_type == '') { // for new question

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                /*  if(isset($request->tutorial_id)){
                                $attenptQue->where('tutorial_id',$tutorial_id);
                            } */
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();


                $queQueryT = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id]);

                if (!empty($attenptQueArr)) {
                    $queQueryT->whereNotIn('id', $attenptQueArr);
                }


                $queQuery = $queQueryT->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*  if(isset($request->tutorial_id)){   
                                $queQuery->where('tutorial_id',$tutorial_id);
                            }  */

                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }

                $filter_questions_id = $queQuery->pluck('id')->toArray();

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '2') { // for new question & prev. incorrect

                $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

                /*    if(isset($request->tutorial_id)){
                                $attenptQue->where('tutorial_id',$tutorial_id);
                            } */
                // if($is_plan_exist==1){
                //     $attenptQue->whereIn('question_id',$buyQuesIdArr);
                // }
                $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

                $total_attenpt = count($attenptQueArr);

                $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

                // $queQuery->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');


                $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                /*   if(isset($request->tutorial_id)){
                                $queQuery->where('tutorial_id',$tutorial_id);
                            }   */
                if ($is_plan_exist == 1) {
                    $queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();
                    $queQuery->whereIn('id', $testModeQueId);
                }
                if (!empty($checkCategory)) {
                    $queQuery = $queQuery->limit($checkCategory->question_count);
                }
                $questions_id_Arr1 = $queQuery->pluck('id')->toArray();

                $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
                $queQuery_new_1Que = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $queQuery_newQue->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $queQuery_new_1Que->where('test_mode',1);

                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $queQuery_new_1Que->whereIn('id', $testModeQueId);
                }


                $queQuery_new = $queQuery_newQue->count();
                $queQuery_new_1 = $queQuery_new_1Que->count();

                $tot_que_dt =  $queQuery_new_1;
                // $tot_que_dt = count($questions_id_Arr1);
                $tot_all_que = $tot_all_que + $queQuery_new;
                // return $tot_que_dt;

                $queQuery2 = AttemptQuestion::where(['user_id' => $user_id, 'category_id' => $catDt->id])->where('is_correct', '0');

                if (isset($request->tutorial_id)) {
                    $queQuery2->where('tutorial_id', $tutorial_id);
                }
                if ($is_plan_exist == 1) {
                    $queQuery2->whereIn('question_id', $buyQuesIdArr);
                } else {
                    $queQuery2->whereIn('question_id', $testModeQueId);
                }

                $questions_id_Arr2 = $queQuery2->pluck('question_id')->toArray();

                $tot_cat_correct = count($questions_id_Arr2);

                //dd($filter_questions_id);
                $filter_questions_id = array_merge($filter_questions_id, $questions_id_Arr1, $questions_id_Arr2);
            } else if ($filter_type == '3') { // for all types


                $questions_id_Arr1 = $subcategoryWiseQuestionList;

                $tot_que_dt = $subcategoryWiseQuestionListCount;


                $que_total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id]);


                if ($is_plan_exist == 0) {
                    $que_total_attenpt->whereIn('question_id', $buyQuesIdArr);
                }
                $total_attenpt = $que_total_attenpt->count();

                $que_tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '1');

                if ($is_plan_exist == 0) {
                    $que_tot_cat_correct->whereIn('question_id', $buyQuesIdArr);
                }
                $tot_cat_correct = $que_tot_cat_correct->count();

                $que_total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->where('is_correct', '0');


                if ($is_plan_exist == 0) {
                    $que_total_cat_incorrect->whereIn('question_id', $buyQuesIdArr);
                }
                $total_cat_incorrect = $que_total_cat_incorrect->count();

                $filter_questions_id = $subcategoryWiseQuestionList;
            }

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);
            // return $filter_questions_id; 
            $total_attenpt = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->count();

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();
            $cr_attenpt = count($filter_questions_id);
            if ($filter_type == '1') {
                $cr_attenpt = $total_attenpt = 0;
            } else if ($filter_type == '2') {
                $cr_attenpt = 0;

                $tot_que_dt = count($filter_questions_id);
            } else if ($filter_type == '3') {
                $cr_attenpt = $tot_cat_correct + $total_cat_incorrect;
            }



            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }



            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }





            $catArr[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $total_all_question_list,
                'total_attenpt' => ($tot_cat_correct + $total_cat_incorrect), //$total_attenpt,
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_correct_percentage' => $total_correct_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
            ];

            if ($filter_type == '2') {
                $tot_all_que = $tot_all_que;
            } else {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }
            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;

            if ($record_type == '2') {
                $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $totalTutorialsQue->whereIn('id', $buyTutIdArr);
                }

                $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

                $totalTutorials = count($getAssingIds);

                $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

                if ($is_plan_exist == 1) {
                    $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
                } else {
                    $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
                }

                $seenedTutorials = $seenedTutorialsQue->get()->count();

                if ($seenedTutorials > 0 && $totalTutorials > 0)
                    $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
                else
                    $score_in_percent = '0';


                if ($filter_type == 2) {
                    $tot_cat_correct = 0;
                }
                if ($filter_type == 1 || $filter_type == '') {
                    $total_cat_incorrect = 0;
                    $tot_cat_correct = 0;
                }

                $total_all_question_list = $tot_que_dt;
                $tot_all_incorrect = (int) $total_cat_incorrect;
                $total_all_correct = (int) $tot_cat_correct;
                $total_attenpt = $total_all_correct + $tot_all_incorrect;;
                $total_attenpt = (int) $total_attenpt;
                $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

                $total_incorrect_percentage = 0;
                $total_correct_percentage = 0;
                $total_white_percentage = 0;
                if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                    $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
                }

                if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                    $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
                }

                if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                    $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
                }

                $catArrTutorial[] = [
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'category_id' => $catDt->id,
                    'category_name' => $catDt->category_name,
                    'time_of_each_question_attempt' => $catDt->time ?? '',
                    'total_questions' => $tot_que_dt,
                    'total_attenpt' => $total_attenpt,
                    'total_correct' => $tot_cat_correct,
                    'total_incorrect' => $total_cat_incorrect,
                    'total_correct_percentage' => $total_correct_percentage,
                    'total_incorrect_percentage' => $total_incorrect_percentage,
                    'total_white_percentage' => $total_white_percentage,
                    'sub_category_arr' => $sub_cat_arr,
                    'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                    'total_tutorial' => $totalTutorials,
                    'seened_tutorial' => $seenedTutorials,
                    'score_in_percent' => $score_in_percent,
                ];
            }
            $overallQuestion = $overallQuestion + $tot_que_dt;
            $overallAttemptedQuestion = $overallAttemptedQuestion + $total_attenpt;
            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }
        $time = microtime(true) - $start;
        //  echo $time; exit;
        $selected_question_arr = array_unique($selected_question_arr);
        $countDownTime = 0;
        foreach ($selected_question_arr as $que_val) {
            $getQueDt = QuestionAnswer::where('question_answer_tbl.id', $que_val)->where('question_answer_tbl.status', 1)->leftjoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')->first(['category_tbl.time']);

            if (isset($getQueDt->time)) {

                $countDownTime += strtotime($getQueDt->time);
            }
        }

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";
        // } 
        $now = Carbon::now();
        /*  $weekStartDate = $now->startOfWeek()->format('Y-m-d');
            $weekEndDate = $now->endOfWeek()->format('Y-m-d');
 
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', $weekStartDate)->whereDate('tip_date', '<=', $weekEndDate)->orderBy('tip_date', 'asc')->first(); */
        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();
        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }
        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];


        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($record_type == '1') {
            $ParentTotalIncorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 0])->count();
            $ParentTotalCorrect = AttemptQuestion::where(['course_id' => $course_id, 'user_id' => $user_id, 'is_correct' => 1])->count();

            if ($filter_type == 2) {
                $ParentTotalCorrect = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $ParentTotalIncorrect = 0;
                $ParentTotalCorrect = 0;
            }

            $total_all_question_list = count($selected_question_arr);
            $tot_all_incorrect = (int) $ParentTotalIncorrect;
            $total_all_correct = (int) $ParentTotalCorrect;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $overallQuestion) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $overallQuestion) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $overallQuestion) * 100);
            }


            $req_data['total_questions'] = $overallQuestion;
            $req_data['total_Attempted'] = $overallAttemptedQuestion;
            $req_data['total_all_correct'] = $total_all_correct;
            $req_data['tot_all_incorrect'] = $tot_all_incorrect;

            $req_data['tot_all_remaining'] = 0;
            $req_data['total_correct_percentage'] = $total_correct_percentage;
            $req_data['total_incorrect_percentage'] = $total_incorrect_percentage;
            $req_data['total_white_percentage'] = $total_white_percentage;
            $req_data['selected_question_arr'] = (count($selected_question_arr) > 0) ? implode(',', $selected_question_arr) : "";
            $req_data['category_list'] = $catArr;
        }

        $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);

        if ($request->record_type == '2')  // for tutorial
        {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }

    // zain - api for fetch details for report
    public function getPerformanceAll(Request $request)
    {
        $performanceId = $request->id;
        $performanceData = TempPerformance::where("uniqueId", $performanceId)->get();
        $data = array();

        foreach ($performanceData as $key => $val) {
            $res = $this->courseDetailLogicPerformance($val->course_id, $val->user_id);

            if (!empty($res['data'])) {
                $data[$key] = $res['data'];
            }
        }

        $data = array_values($data);
        $summaryData = $this->summaryDataLogic($data);

        $this->status = true;
        $this->code =  "101";
        $this->data = $data;
        $this->summaryData = @$summaryData;
        $this->message = "priview data";

        return  response()->json($this);
    }

    public function summaryDataLogic($data)
    {
        $allSummary = array();
        $courseWiseArray = array();

        foreach ($data as $val) {
            if (!empty($val['course_detail']))
                $courseWiseArray[$val['course_detail']['id']][] = $val;
        }

        $singleData = array();
        $index = 0;

        foreach ($courseWiseArray as $key => $val) {
            $tutorialCategory = array();
            $questionCategory = array();

            foreach ($val as $key1 => $val1) {
                $singleData[$index]['course_detail'] = $val1['course_detail'];
                $singleData[$index]['total_tutorials'] = @$singleData[$index]['total_tutorials'] + $val1['total_tutorials'];
                $singleData[$index]['seen_tutorial'] = @$singleData[$index]['seen_tutorial'] + $val1['seen_tutorial'];
                $singleData[$index]['remaining_tutorial'] = @$singleData[$index]['remaining_tutorial'] + $val1['remaining_tutorial'];
                $singleData[$index]['percentage_tutorial'] = @$singleData[$index]['percentage_tutorial'] + $val1['percentage_tutorial'];
                $singleData[$index]['your_total_average_score'] = @$singleData[$index]['your_total_average_score'] + $val1['your_total_average_score'];
                $singleData[$index]['your_percentile'] = @$singleData[$index]['your_percentile'] + $val1['your_percentile'];
                $singleData[$index]['totalrecord'] = @$singleData[$index]['totalrecord'] + 1;

                foreach ($val1['tutorial_category_data'] as $val2) {
                    $tutorialCategory[$val2['category_id']]['category_id'] = $val2['category_id'];
                    $tutorialCategory[$val2['category_id']]['category_name'] = $val2['category_name'];
                    $tutorialCategory[$val2['category_id']]['total_tutorials'] = @$tutorialCategory[$val2['category_id']]['total_tutorials'] + $val2['total_tutorials'];
                    $tutorialCategory[$val2['category_id']]['tutorial_seen_by_me'] = @$tutorialCategory[$val2['category_id']]['tutorial_seen_by_me'] + $val2['tutorial_seen_by_me'];
                    $tutorialCategory[$val2['category_id']]['totalrecord'] = @$tutorialCategory[$val2['category_id']]['totalrecord'] + 1;
                }

                foreach ($val1['question_category_data'] as $val2) {
                    $questionCategory[$val2['category_id']]['category_id'] = $val2['category_id'];
                    $questionCategory[$val2['category_id']]['category_name'] = $val2['category_name'];
                    $questionCategory[$val2['category_id']]['sort_category_name'] = $val2['sort_category_name'];
                    $questionCategory[$val2['category_id']]['mm_score'] = @$questionCategory[$val2['category_id']]['mm_score'] + $val2['mm_score'];
                    $questionCategory[$val2['category_id']]['your_score'] = @$questionCategory[$val2['category_id']]['your_score'] + $val2['your_score'];
                    $questionCategory[$val2['category_id']]['totalrecord'] = @$questionCategory[$val2['category_id']]['totalrecord'] + 1;
                }

                $singleData[$index]['tutorial_category_data'] = $tutorialCategory;
                $singleData[$index]['question_category_data'] = $questionCategory;
            }
            $index++;
        }
        $finalData = array();
        $index = 0;

        foreach ($singleData as $val) {
            $finalData[$index] = $val;

            $finalData[$index]['total_tutorials'] = ceil($val['total_tutorials'] / $val['totalrecord']);
            $finalData[$index]['seen_tutorial'] =  ceil($val['seen_tutorial'] / $val['totalrecord']);
            $finalData[$index]['remaining_tutorial'] = ceil($val['remaining_tutorial'] / $val['totalrecord']);
            $finalData[$index]['percentage_tutorial'] = ceil($val['percentage_tutorial'] / $val['totalrecord']);
            $finalData[$index]['your_total_average_score'] = ceil($val['your_total_average_score'] / $val['totalrecord']);
            $finalData[$index]['your_percentile'] = ceil($val['your_percentile'] / $val['totalrecord']);

            $tutorialCategory = array();
            $questionCategory = array();
            foreach ($val['tutorial_category_data'] as $key => $val1) {
                $tutorialCategory[$key]['category_id'] = $val1['category_id'];
                $tutorialCategory[$key]['category_name'] = $val1['category_name'];

                if ($val1['total_tutorials'] > 0) {
                    $tutorialCategory[$key]['total_tutorials'] = ceil($val1['total_tutorials'] / $val1['totalrecord']);
                } else {
                    $tutorialCategory[$key]['total_tutorials'] = 0;
                }

                if ($val1['tutorial_seen_by_me'] > 0) {
                    $tutorialCategory[$key]['tutorial_seen_by_me'] = ceil($val1['tutorial_seen_by_me'] / $val1['totalrecord']);
                } else {
                    $tutorialCategory[$key]['tutorial_seen_by_me'] = 0;
                }
            }
            foreach ($val['question_category_data'] as $key => $val1) {
                $questionCategory[$key]['category_id'] = $val1['category_id'];
                $questionCategory[$key]['category_name'] = $val1['category_name'];
                $questionCategory[$key]['sort_category_name'] = $val1['sort_category_name'];
                if ($val1['mm_score'] > 0) {
                    $questionCategory[$key]['mm_score'] = ceil($val1['mm_score'] / $val1['totalrecord']);
                } else {
                    $questionCategory[$key]['mm_score'] = 0;
                }

                if ($val1['your_score'] > 0) {
                    $questionCategory[$key]['your_score'] = ceil($val1['your_score'] / $val1['totalrecord']);
                } else {
                    $questionCategory[$key]['your_score'] = ceil($val1['your_score'] / $val1['totalrecord']);
                }
            }
            $questionCategory = array_values($questionCategory);
            $tutorialCategory = array_values($tutorialCategory);
            $finalData[$index]['question_category_data'] = $questionCategory;
            $finalData[$index]['tutorial_category_data'] = $tutorialCategory;
            $index++;
        }


        return $finalData;
    }
    public function edit_tutorial_note(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'tutorial_id' => 'required|integer',
            'note' => 'required',
        ]);

        $user_id = Auth::id();
        $tutorial_id = $request->tutorial_id;
        $id = $request->id;
        $note = $request->note;

        $req_data = [];
        if ($validator->fails()) {
            $error = '';
            if (!empty($validator->errors())) {
                $error = $validator->errors()->first();
            }
            $message = $error;
            return $this->json_view(false, $req_data, $message);
        } else {
            $newUser = TutorialNote::where("id", $id)->update([
                'user_id' => $user_id,
                'tutorial_id' => $tutorial_id,
                'notes' => $note,
            ]);
            $req_message = "Tutorial Note Updated";
        }
        return $this->json_view(true, $req_data, $req_message);
    }
    public function delete_tutorial_note(Request $request)
    {
        $id = $request->id;
        $req_data = array();
        $newUser = TutorialNote::where("id", $id)->delete();
        $req_message = "Tutorial Note Deleted";
        return $this->json_view(true, $req_data, $req_message);
    }
    public function videoCommentLike(Request $request)
    {

        $comment = $request->id;
        $type = $request->type;
        $userId = Auth::user()->id;
        $checkData = VideoCommentLike::where("video_comment_id", $comment)->where("user_id", $userId)->first();
        if (!empty($checkData)) {
            if ($checkData->type != $type) {
                if ($type == "like") {
                    VideoComment::where("id", $checkData->video_comment_id)->increment('likecount');
                    VideoComment::where("id", $checkData->video_comment_id)->decrement('unlikecount');
                } else {
                    VideoComment::where("id", $checkData->video_comment_id)->decrement('likecount');
                    VideoComment::where("id", $checkData->video_comment_id)->increment('unlikecount');
                }
                $VideoComment =  VideoCommentLike::find($checkData->id);
                $VideoComment->user_id = $userId;
                $VideoComment->video_comment_id = $comment;
                $VideoComment->type = $type;

                $VideoComment->save();
            }
        } else {
            if ($type == "like") {
                VideoComment::where("id", $comment)->increment('likecount');
            } else {

                VideoComment::where("id", $comment)->increment('unlikecount');
            }
            $VideoComment = new VideoCommentLike();
            $VideoComment->user_id = $userId;
            $VideoComment->video_comment_id = $comment;
            $VideoComment->type = $type;

            $VideoComment->save();
        }

        return response()->json(['statusCode' => 200, 'message' => 'Comment ' . $type . ' Successfully', 'data' => $VideoComment], 200);
    }
    public function getComment(Request $request)
    {
        $tutorialId = $request->tutorialId;
        $videoComment = VideoComment::where("tutorial_id", $tutorialId)->where("parent_id", 0)->with("user")->orderBy("likecount", "DESC")->get();
        $allComment = array();
        foreach ($videoComment as $cmtKey => $cmt) {
            $adminComment = VideoComment::where('parent_id', $cmt->id)->where('admin_reply', 1)->orderBy("likecount", "DESC")->get();
            $adminData = array();
            foreach ($adminComment as $key1 => $admin) {
                $userDetail = User::find($admin->user_id);
                $adminData[$key1] = $admin;
                $adminData[$key1]['user'] = $userDetail;
            }
            $allComment[$cmtKey] = $cmt;
            $allComment[$cmtKey]['adminComment'] = $adminData;
        }
        return response()->json(['statusCode' => 200, 'message' => 'Comment list Successfully', 'data' => $allComment], 200);
    }
    public function courseInstruction(Request $request)
    {
        $courseId = $request->id;
        $courseDetail = Course::find($courseId);
        // print_r
        return response()->json(['statusCode' => 200, 'message' => 'Comment list Successfully', 'data' => array("instruction" => $courseDetail->instruction)], 200);
    }
    public function categorylistloginTutorial($request)
    {
        $data = $request->all();

        $req_data = [];

        $record_type = $request->record_type;
        $course_id = $request->course_id;
        $filter_type = $request->filter_type;
        $tutorial_id = $request->tutorial_id;
        $category_ids = $request->category_ids;
        $subcategoryIds = $request->subcategoryIds;
        $subcategoryIds = explode(",", $subcategoryIds);
        $questionCount = $request->questionCount;
        $totalQuestion = $request->total_question;
        $percent = 100;



        $courseDetailsRow = Course::find($course_id);

        $user_id = Auth::id();
        $catArr = [];
        $catArrTutorial = [];
        $total_all_correct = '0';
        $tot_all_incorrect = '0';

        $selected_question_arr = [];



        $check_plan = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')
            ->where(['order_detail.package_for' => '1'])
            ->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))
            ->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])
            ->count();

        $is_plan_exist = ($check_plan > 0) ? '1' : '0';

        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        $buy_tutorial_ids = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->leftjoin('package_tbl', 'package_tbl.id', '=', 'order_detail.package_id')->where(['order_detail.package_for' => '1'])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->where(['order_detail.particular_record_id' => $course_id, 'order_detail.package_for' => '1', 'order_tbl.user_id' => $user_id])->pluck('package_tbl.assign_tutorial_id')->join(',');

        Log::info('buy_tutorial_ids' . print_r($buy_tutorial_ids, true));

        $buyTutIdArr = (!empty($buy_tutorial_ids)) ? explode(',', $buy_tutorial_ids) : [];
        $buyTutIdArr = array_unique($buyTutIdArr);
        $ids = !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [];

        if (!empty($tutorial_id)) {
            $tutorialDetail = Tutorial::find($tutorial_id);
            $getCatgory = Category::with('subCategory')->where('id', $tutorialDetail->category_id)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        } else {
            $getCatgory = Category::with('subCategory')->whereIn('id', $ids)->where('status', 1)
                ->orderBy('sort', 'asc')->get();
        }

        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            foreach ($catDt->subCategory as $subCatDt) {
                $sub_cat_arr[] = [
                    'sub_category_id' => $subCatDt->id,
                    'sub_main_category_id' => $subCatDt->category_id,
                    'sub_category_name' => $subCatDt->sub_category_name,
                    'total_questions' => 0,
                    'total_attenpt' => 0,
                    'total_correct' => 0,
                    'total_incorrect' => 0,
                    'total_correct_percentage' => 0,
                    'total_incorrect_percentage' => 0,
                    'total_white_percentage' => 0,
                    'sub_filter_questions_id' => 0,
                ];
            }

            $tot_que_dt = '0';
            $total_attenpt = '0';
            $tot_cat_correct = '0';
            $total_cat_incorrect = '0';

            $totalTutorialsQue = Tutorial::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

            if ($is_plan_exist == 1) {
                $totalTutorialsQue->whereIn('id', $buyTutIdArr);
            }

            $getAssingIds = $totalTutorialsQue->pluck('id')->toArray();

            $totalTutorials = count($getAssingIds);

            $seenedTutorialsQue = WatchedTutorial::where(['category_id' => $catDt->id, 'course_id' => $course_id, 'user_id' => $user_id]);

            if ($is_plan_exist == 1) {
                $seenedTutorialsQue->whereIn('tutorial_id', $buyTutIdArr);
            } else {
                $seenedTutorialsQue->whereIn('tutorial_id', $getAssingIds);
            }

            $seenedTutorials = $seenedTutorialsQue->get()->count();

            if ($seenedTutorials > 0 && $totalTutorials > 0)
                $score_in_percent = ($seenedTutorials * 100) / $totalTutorials;
            else
                $score_in_percent = '0';


            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }
            if ($filter_type == 1 || $filter_type == '') {
                $total_cat_incorrect = 0;
                $tot_cat_correct = 0;
            }

            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;
            $total_attenpt = (int) $total_attenpt;
            $total_remaining = (int) $total_all_question_list - ($total_all_correct + $tot_all_incorrect);

            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $total_white_percentage = 0;
            if (($tot_all_incorrect != 0) && ($total_all_question_list != 0)) {
                $total_incorrect_percentage = round(($tot_all_incorrect  / $total_all_question_list) * 100);
            }

            if (($total_all_correct != 0) && ($total_all_question_list != 0)) {
                $total_correct_percentage = round(($total_all_correct  / $total_all_question_list) * 100);
            }

            if (($total_remaining != 0) && ($total_all_question_list != 0)) {
                $total_white_percentage = round(($total_remaining  / $total_all_question_list) * 100);
            }

            $catArrTutorial[] = [
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'category_id' => $catDt->id,
                'category_name' => $catDt->category_name,
                'time_of_each_question_attempt' => $catDt->time ?? '',
                'total_questions' => $tot_que_dt,
                'total_attenpt' => $total_attenpt,
                'total_correct' => $tot_cat_correct,
                'total_incorrect' => $total_cat_incorrect,
                'total_correct_percentage' => $total_correct_percentage,
                'total_incorrect_percentage' => $total_incorrect_percentage,
                'total_white_percentage' => $total_white_percentage,
                'sub_category_arr' => $sub_cat_arr,
                'filter_questions_id' => (count($filter_questions_id) > 0) ? implode(',', $filter_questions_id) : "",
                'total_tutorial' => $totalTutorials,
                'seened_tutorial' => $seenedTutorials,
                'score_in_percent' => $score_in_percent,
            ];


            $selected_question_arr = array_merge($selected_question_arr, $filter_questions_id);
        }

        $selected_question_arr = array_unique($selected_question_arr);

        $getLastWatchedDtQue = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.user_id' => $user_id, 'watched_tutorial.course_id' => $course_id]);

        if ($is_plan_exist == 1) {
            $getLastWatchedDtQue->whereIn('watched_tutorial.tutorial_id', $buyTutIdArr);
        }

        $getLastWatchedDt = $getLastWatchedDtQue->first(['tutorial_tbl.id', 'category_tbl.id as category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

        $req_data['last_watched'] = (isset($getLastWatchedDt->id)) ? $getLastWatchedDt->toArray() : "";
        // } 
        $now = Carbon::now();

        $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '>=', date('Y-m-d'))->orderBy('tip_date', 'asc')->first();
        if (empty($getWeeklyWebinarDt)) {
            $getWeeklyWebinarDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '2'])->whereDate('tip_date', '<', date('Y-m-d'))->orderBy('tip_date', 'desc')->first();
        }
        $weekly_webinar_dt = (isset($getWeeklyWebinarDt->id)) ? $getWeeklyWebinarDt->toArray() : [];


        $req_data['weekly_webinar'] = $weekly_webinar_dt;

        $getOneDayWorkshopDt = Tips::orderBy('id', 'desc')->where(['course_id' => $course_id, 'type' => '3'])->first();

        $one_day_workshop_dt = (isset($getOneDayWorkshopDt->id)) ? $getOneDayWorkshopDt->toArray() : [];
        $req_data['one_day_workshop'] = $one_day_workshop_dt;

        if ($request->record_type == '2')  // for tutorial
        {
            $req_data['category_list'] = $catArrTutorial;
        }
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        }
    }
}
