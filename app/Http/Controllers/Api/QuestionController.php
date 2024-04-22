<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Auth;

use Carbon\Carbon;
use App\Models\Category;

use App\Models\SubCategory;
use App\Models\QuestionTempFilter;
use App\Models\Course;
use App\Models\Tutorial;
use App\Models\QuestionAnswer;
use App\Models\WatchedTutorial;
use App\Models\TempTest;
use App\Models\AttemptQuestion;

use App\Models\Order;
use App\Models\Package;
use App\Models\OrderDetail;

use App\Models\Tips;
use App\Models\TempSrQuestion;

use App\Models\AssignQuestion;
use Exception;

class QuestionController extends Controller
{


    public function __construct()
    {
    }

    public function json_view($req_status = false, $req_data = "", $req_message = "")
    {
        $this->status = $req_status;
        $this->code = ($req_status == false) ? "404" : "101";
        $this->data = $req_data;
        $this->message = $req_message;
        return  response()->json($this);
    }
    //zain - category list api
    public function get_category_list(Request $request)
    {
        $response = $this->categorylistlogicfilter($request);

        return $this->json_view($response['status'], $response['data'], $response['message']);
    }

    public function applyfilter(Request $request)
    {
        $category_ids = $request->category_ids;
        $subcategoryIds = $request->subcategoryIds;

        if (!empty($subcategoryIds)) {
            $subcategoryIds = explode(",", $subcategoryIds);
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

        $selectedCategory = explode(",", $category_ids);
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
        // $finalresult=array_filter($finalresult);
        $finalResponse['selected_question_arr'] = implode(",", $finalresult);
        $finalResponse['totalQuestionCount'] = count($finalresult);
        // $finalResponse=$response['data'];
        return response()->json(['statusCode' => 200, 'message' => 'Comment Added Successfully', 'data' => $finalResponse], 200);
    }

    //zain - filter category list api
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




        $questionAssignCount = 1;
        $overallQuestion = 0;
        $tstModeQId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();

        foreach ($getCatgory as $catDt) {
            $filter_questions_id = [];

            $sub_cat_arr = [];

            $checkCategory = QuestionTempFilter::where("user_id", $user_id)->where("category_id", $catDt->id)->first();

            $checkSelectedSubCategory = SubCategory::orderBy('id', 'asc')->where(['category_id' => $catDt->id, 'status' => 1])->whereIn("id", $subcategoryIds)->first();
            $isApplySubCategoryCondition = 0;
            if (!empty($checkSelectedSubCategory)) {
                $isApplySubCategoryCondition = 1;
            }


            $subcategoryWiseQuestionList = array();

            $subcategoryWiseQuestionListCount = 0;

            foreach ($catDt->subCategory as $subCatDt) {
                $sub_filter_questions_id = [];

                $sub_attenptQuess = AttemptQuestion::select('question_id')->whereRaw('FIND_IN_SET("' . $subCatDt->id . '",sub_category_ids)')->where(['user_id' => $user_id, 'course_id' => $course_id]);

                if ($is_plan_exist == 1) {
                    $sub_attenptQuess->whereIn('question_id', $buyQuesIdArr);
                }

                $sub_attenptQueArr = $sub_attenptQuess->pluck('question_id')->toArray();

                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else { // for free trial
                    // $sub_queQuery->where('test_mode',1); 

                    // $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();

                    $sub_queQueryCount->whereIn('id', $tstModeQId);
                }
                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;


                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);
                if (!empty($subcategoryIds) && $isApplySubCategoryCondition == 1) {
                    $sub_queQuery = $sub_queQuery->whereIn("sub_category_ids", $subcategoryIds);
                }

                if ($is_plan_exist == 1) {
                    $sub_queQuery->whereIn('id', $buyQuesIdArr);
                } else { // for free trial


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
            // return $filter_questions_id; 
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


            if ($filter_type == '1') {
                $total_attenpt = 0;
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
            $overallQuestion = $overallQuestion + $tot_que_dt;
            if ($filter_type == '2') {
                $tot_all_que = $tot_all_que;
            } else {
                $tot_all_que = $tot_all_que + $tot_que_dt;
            }
            $total_all_correct = $total_all_correct + $tot_cat_correct;
            $tot_all_incorrect = $tot_all_incorrect + $total_cat_incorrect;



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


        // $getAllQueCount = QuestionAnswer::where('status', 1)->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->count();

        // $req_data['all_questions_count'] = $getAllQueCount;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        // $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);


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
                } else {
                    // for free trial
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $sub_queQueryCount->whereIn('id', $testModeQueId);
                }
                $sub_queQueryCount = $sub_queQueryCount->count();
                $subcategoryWiseQuestionListCount = $subcategoryWiseQuestionListCount + $sub_queQueryCount;

                $sub_queQuery = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->whereNotIn('id', $sub_attenptQueArr);
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


            /*  $attenptQue = AttemptQuestion::select('question_id')->where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => 1]);

          
            $attenptQueArr = $attenptQue->pluck('question_id')->toArray();

            $total_attenpt = count($attenptQueArr); */

            /*    $queQuery = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereNotIn('id', $attenptQueArr);

        
            $queQuery->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

         
            if ($is_plan_exist == 1) {
                $queQuery->whereIn('id', $buyQuesIdArr);
            } else { // for free trial
              

                $testModeQueId = AssignQuestion::where('course_id', $course_id)->whereNotIn('question_id', $attenptQueArr)->pluck('question_id')->toArray();
                $queQuery->whereIn('id', $testModeQueId);
            }
            if (!empty($checkCategory)) {
                $queQuery = $queQuery->limit($checkCategory->question_count);
            }
            $questions_id_Arr1 = $queQuery->pluck('id')->toArray(); */

            $queQuery_newQue = QuestionAnswer::where(['status' => 1, 'category_id' => $catDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

            if ($is_plan_exist == 1) {
                $queQuery_newQue->whereIn('id', $buyQuesIdArr);
            }

            $queQuery_new = $queQuery_newQue->count();


            $tot_que_dt =  $subcategoryWiseQuestionListCount;
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

            $filter_questions_id = $subcategoryWiseQuestionList;

            $filter_questions_id = array_merge($filter_questions_id, $sub_filter_questions_id);
            $filter_questions_id = array_unique($filter_questions_id);

            $tot_cat_correct = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->count();

            $total_cat_incorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->count();

            if ($filter_type == 2) {
                $tot_cat_correct = 0;
            }

            $total_all_question_list = $tot_que_dt;
            $tot_all_incorrect = (int) $total_cat_incorrect;
            $total_all_correct = (int) $tot_cat_correct;
            $total_attenpt = $total_all_correct + $tot_all_incorrect;;

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

            if ($record_type == '2') // for tutorial
            {
                $totalTutorials = 0;
                $seenedTutorials = 0;

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
    public function allQuestionFilterType($request)
    {
        $data = $request->all();
        $req_data = [];

        $course_id = $request->course_id;
        $filter_type = $request->filter_type;
        $tutorial_id = $request->tutorial_id;
        $category_ids = $request->category_ids;
        $subcategoryIds = $request->subcategoryIds;
        $subcategoryIds = explode(",", $subcategoryIds);
        $questionCount = $request->questionCount;
        $totalQuestion = $request->total_question;
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

                $sub_queQueryCount = QuestionAnswer::where(['status' => 1, 'sub_category_ids' => $subCatDt->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');

                if ($is_plan_exist == 1) {
                    $sub_queQueryCount->whereIn('id', $buyQuesIdArr);
                } else {
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
                    $sub_queQueryCount->whereIn('id', $testModeQueId);
                }

                // code for counting questions subcategory wise
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
                    $testModeQueId = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
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
                            $newSubCategoryQuestion[] = $q;
                            $questionAssignCount++;

                            $subcategoryWiseQuestionList[] = $q;
                        }

                        $countQuestion++;
                        $totalquestionAssigncountInSubCate++;
                    }
                } else {
                    foreach ($sub_filter_questions_id as $q) {
                        $newSubCategoryQuestion[] = $q;
                        $questionAssignCount++;
                        $totalquestionAssigncountInSubCate++;
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
            } else if ($filter_type == '3') {
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


        $req_data['all_questions_count'] = 0;
        $req_data['count_down_time_for_exam'] = date('H:i:s', $countDownTime);
        $req_data['smart_study_question'] = Category::score_question_list($user_id, $course_id);
        $req_data['is_plan_exist'] = $is_plan_exist;

        if (count($catArr) > 0 || count($catArrTutorial) > 0) {
            $req_message = "Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
        } else {
            $req_message = "No Record Found";
            return array("status" => true, "data" => $req_data, "message" => $req_message);
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
}
