<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssingQuestionMocktest;
use App\Models\AttemptMocktestQuestion;
use App\Models\Category;
use App\Models\LikeUnlike;
use App\Models\Order;
use App\Models\Course;
use App\Models\CategoryUcatScore;
use App\Models\Package;
use App\Models\Mocktest;
use App\Models\MocktestCategory;
use App\Models\MocktestResume;
use App\Models\QueOption;
use App\Models\QueOptionAnswerType;
use App\Models\QuestionAnswer;
use App\Models\Rating;
use App\Models\TempMocktest;
use App\Models\TempMocktestBeforeFinishTest;
use App\Models\TempMocktestSrQuestion;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MocktestController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->user()->id;

        $mocktests = Mocktest::with([
            'resumes' => function ($query) use ($user_id) {
                return $query->where('user_id', $user_id)->count() > 0 ? 1 : 0;
            },
            'attemptQuestions',
            'assignQuestions'
        ])
            ->when($request->course_id, function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            })
            ->orderBy('sort', 'asc')
            ->get();

        $is_plan_exist = $this->isPlanExist($request->course_id);
        if ($is_plan_exist == 0) {
            $freeCourse = $this->getfreecourse($user_id);
            if (in_array($request->course_id, $freeCourse)) {
                $is_plan_exist = 1;
            }
        }

        try {
            return response()->json(['code' => 200, 'message' => 'mock test', "data" => $mocktests, "is_plan_exist" => $is_plan_exist], 200);
        } catch (Error $e) {
            return response()->json(['code' => 422, 'message' => 'mock test'], 200);
        }
    }
    public function mocktestCategory(Request $request)
    {
        $mocktestId = $request->category;
        $user_id = auth()->user()->id;
        $mocktest = MocktestCategory::where("mocktest_id", $mocktestId)->get();

        $result = array();
        foreach ($mocktest as $key => $val) {
            $getallAttemptquestion = TempMocktest::where("user_id", $user_id)->where("category_id", $val->category_id)->pluck('question_id');
            $attemptRecord = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mocktestId, 'category_id' => $val->category_id])->get();

            $totalCorrect = 0;
            $totalIncorrect = 0;
            foreach ($attemptRecord as $attR) {
                if ($attR->is_correct == 1) {
                    $totalCorrect++;
                }
                if ($attR->is_correct == 0) {
                    $totalIncorrect++;
                }
            }

            $cat_id = $val->category_id;
            $checkQuestion = AssingQuestionMocktest::where("category_id", $val->category_id)->where("mocktest_id", $mocktestId)->whereHas("question")
                ->whereHas('question', function ($q) use ($cat_id) {
                    $q->where('category_id', $cat_id);
                });

            if (!empty($getallAttemptquestion)) {
                $checkQuestion = $checkQuestion->whereNotIn("question_id", $getallAttemptquestion);
            }


            $checkQuestion = $checkQuestion->pluck("question_id");
            $result[$key] = $val;

            $result[$key]['totalquestionCount'] = count($checkQuestion);
            if (count($checkQuestion) > 0) {
                $questionIds = implode(",", $checkQuestion->toArray());
            } else {
                $questionIds = '';
            }
            $checkAttempt = MocktestResume::where(['user_id' => $user_id, "mocktest_id" => $mocktestId, "category_id" => $val->category_id])->get();
            $resume = 0;
            if (count($checkAttempt) > 0) {
                $resume = 1;
            }

            $get_all_attempt_que = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mocktestId, 'category_id' => $val->category_id])->pluck("question_id");
            $get_all_assigned_que = AssingQuestionMocktest::where("category_id", $val->category_id)->where("mocktest_id", $mocktestId)->pluck("question_id");

            if (($resume != 1) &&  !empty($get_all_attempt_que[0]) && !empty($get_all_assigned_que[0])) {
                $result_for_retake = array_diff($get_all_attempt_que->toArray(), $get_all_assigned_que->toArray());
                if (empty($result_for_retake)) {
                    $resume = 2;
                }
            }

            $total_white_percentage = 0;
            $total_incorrect_percentage = 0;
            $total_correct_percentage = 0;
            $totalQuestion = count($checkQuestion);
            $total_remaining = $totalQuestion - ($totalCorrect + $totalIncorrect);
            if (($total_remaining != 0) && ($totalQuestion != 0)) {
                $total_white_percentage = round(($total_remaining / $totalQuestion) * 100);
            }

            if (($totalCorrect != 0) && ($totalQuestion != 0)) {
                $total_correct_percentage = round(($totalCorrect / $totalQuestion) * 100);
            }

            if (($totalIncorrect != 0) && ($totalQuestion != 0)) {
                $total_incorrect_percentage = round(($totalIncorrect / $totalQuestion) * 100);
            }

            $finaltime = $val->time;

            if ($resume == 1) {
                $finalQuestionTotal = count($getallAttemptquestion) + $totalQuestion;
                $time = explode(":", $val->time);
                $hoursSecond = $time[0] * 3600;
                $minSecond = $time[1] * 60;
                $Second = $time[2];
                $totalSecond = $hoursSecond + $minSecond + $Second;
                $perQuestionSecond = $totalSecond / $finalQuestionTotal;
                $newTime = $perQuestionSecond * $totalQuestion;
                $newTime = ceil($newTime);
                if ($newTime > 0) {
                    $finaltime = gmdate("H:i:s", $newTime);
                }
            }

            $result[$key]['categoryName'] = $val->category->category_name;
            $result[$key]['totalquestion'] = $questionIds;
            $result[$key]['resume'] = $resume;
            $result[$key]['orgTime'] = $val['time'];
            $result[$key]['time'] = $finaltime;
            $result[$key]['total_attenpt'] = count($attemptRecord);
            $result[$key]['total_correct'] = $totalCorrect;
            $result[$key]['total_correct_percentage'] = $total_correct_percentage;
            $result[$key]['total_incorrect'] = $totalIncorrect;
            $result[$key]['total_incorrect_percentage'] = $total_incorrect_percentage;
            $result[$key]['total_questions'] = $totalQuestion;
            $result[$key]['total_white_percentage'] = $total_white_percentage;
            $result[$key]['getallAttemptquestion'] = $getallAttemptquestion;

            unset($result[$key]['category']);
        }
        try {

            return response()->json(['code' => 200, 'message' => 'mock test', "data" => $result], 200);
        } catch (Error $e) {
            return response()->json(['code' => 422, 'message' => 'mock test'], 200);
        }
    }

    public function startMocktest(Request $request)
    {
        $user_id = auth()->user()->id;
        $all_question_id = $request->all_question_id ?? "";
        $question_id = $request->question_id;
        $resume = $request->resume ? $request->resume : 0;
        $mocktest_id = $request->mocktest;
        $categoryIds = $request->categoryIds;
        $isPractice = $request->isPractice ? $request->isPractice : 0;

        $req_data = array();

        if (empty($request->mocktest)) {
            $req_message = "please send mocktest id";
            return response()->json(['status' => false, 'data' => $req_data, 'message' => $req_message]);
        }

        if (!empty($mocktest_id) && !empty($categoryIds) && ($resume == 0 || $resume == 2) && (empty($question_id) || $question_id == null || $question_id == "null")) {
            $allCategory = explode(",", $categoryIds);

            foreach ($allCategory as $val) {
                AttemptMocktestQuestion::where(['user_id' => $user_id, "mocktest_id" => $mocktest_id, "category_id" => $val])->delete();
            }

            TempMocktest::where(['user_id' => $user_id, "mocktest_id" => $mocktest_id, "category_id" => $categoryIds])->delete();
            TempMocktestBeforeFinishTest::where(['user_id' => $user_id, "mocktest_id" => $mocktest_id, "category_ids" => $categoryIds])->delete();
            TempMocktestSrQuestion::where(['user_id' => $user_id, "mocktest_id" => $mocktest_id, "category_id" => $categoryIds])->delete();
            MocktestResume::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $categoryIds])->delete();
        }

        $req_data = [];

        $req_message = "";
        $checked_status = '';
        $get_dt = TempMocktestBeforeFinishTest::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_ids' => $categoryIds])->first();

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
            TempMocktestBeforeFinishTest::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_ids' => $categoryIds])->update($addArr);
        } else {
            $crrArr = [];
            $crrArr[] = $question_id;
            $req_message = "Question added for review";
            $srv = array_unique($crrArr);

            $insert_srv = (count($srv) > 0) ? implode(',', $srv) : "";
            $addArr = ['user_id' => $user_id, 'questions_id' => $all_question_id, 'question_for_review' => $insert_srv, 'category_ids' => $categoryIds, "is_practice" => $isPractice, 'mocktest_id' => $mocktest_id];

            TempMocktestBeforeFinishTest::insert($addArr);

            MocktestResume::insert(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $categoryIds]);
        }

        if ($all_question_id != "") {
            $queArr = explode(',', $all_question_id);
            $queArr = explode(',', $all_question_id);
            sort($queArr);
            $sr = 1;
            foreach ($queArr as $qId) {
                $checkQueCount = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $qId])->count();
                if ($checkQueCount == 0) {
                    $addArr = ['sr_no' => $sr, 'user_id' => $user_id, 'question_id' => $qId, "is_practice" => $isPractice, 'mocktest_id' => $mocktest_id, "category_id" => $categoryIds];
                    TempMocktestSrQuestion::insert($addArr);

                    $sr++;
                }
            }
        }

        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
    }

    public function mocktestQuestions(Request $request, Mocktest $mocktest)
    {
        $count_down_time = '';

        $user_id = auth()->user()->id;
        $questions_ids_arr = [];
        $questionstype = $request->questionstype;
        $mock_test_id = $mocktest->id;
        $category_id = $request->category_id;
        $current_qestion_for_review = $request->current_qestion_for_review;
        $questions_ids_arr = explode(',', $request->filter_questions_id);
Log::info($request->current_question_id);
        if ($request->current_question_id > 0) {
            array_push($questions_ids_arr, $request->current_question_id);
        }

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
        if ($questions_ids_arr) {

            $QueQuery->whereIn('id', $questions_ids_arr);
        }

        $current_question_id = $request->current_question_id ?? "";

        if (isset($request->current_question_id)) {
            $QueQuery->where('id', $current_question_id);
        }

        $getQuesDt = $QueQuery->first();

        $show_question_id = @$getQuesDt->id;

        if (isset($getQuesDt->id)) {
            $getPreviousQuesDt = QuestionAnswer::orderBy('id', 'desc')->whereIn('id', $questions_ids_arr)->where('status', 1)->where('id', '<', $show_question_id)->first(['id']);

            $previous_question_id = @$getPreviousQuesDt->id;

            $getNextQuesDt = QuestionAnswer::orderBy('id', 'asc')->whereIn('id', $questions_ids_arr)->where('status', 1)->where('id', '>', $show_question_id)->first(['id']);
            $next_question_id = @$getNextQuesDt->id;
        }
        $my_curr_que_id = @$getQuesDt->id ?? "";

        $getTextAllQue = TempMocktestBeforeFinishTest::where(['user_id' => $user_id, 'category_ids' => $category_id, "mocktest_id" => $mock_test_id])->first();

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? '';
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $is_reviewed = (in_array($my_curr_que_id, $reviewArr)) ? '1' : '0';

        $getSrNo = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $my_curr_que_id, 'category_id' => $category_id])->first(['sr_no']);
        $sr_no_is = @$getSrNo->sr_no ?? 1;

        if ($sr_no_is > 1 && empty($previous_question_id) && $questionstype != "flagquestion" && $questionstype != "incompletequestion") {

            $getPreviousAttemptQuestion = TempMocktestSrQuestion::where(['user_id' => $user_id, 'sr_no' => ($sr_no_is - 1), "mocktest_id" => $mock_test_id, 'category_id' => $category_id])->first(['question_id']);
            $previous_question_id = $getPreviousAttemptQuestion->question_id;
        }
        $sr_no_is = (int) $sr_no_is;
        if ($questionstype != "flagquestion" && $questionstype != "incompletequestion") {
            $getNextAttemptQuestion = TempMocktestSrQuestion::where(['user_id' => $user_id, 'sr_no' => ($sr_no_is + 1), "mocktest_id" => $mock_test_id, 'category_id' => $category_id])->first(['question_id']);
            $next_question_id = @$getNextAttemptQuestion->question_id;
        }

        $getSrNo_count = TempMocktestSrQuestion::where(['user_id' => $user_id, "mocktest_id" => $mock_test_id, 'category_id' => $category_id])->count();
        $req_data['total_question'] = $getSrNo_count;

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
        $getTestDt = AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $show_question_id])->first();

        if (isset($getTestDt->id)) {
            $is_question_attempt = '0';
            $my_answer = $getTestDt->answer ?? '';
            $my_correct_option_json = $getTestDt->correct_option_json ?? '';
        }

        $req_data['is_question_attempt'] = $is_question_attempt;

        if ($request->is_feedback) {
            $attemptUserArr = AttemptMocktestQuestion::where(['question_id' => $show_question_id])->groupBy('user_id')->get('user_id')->toArray();

            $total_que_attempt_user = count($attemptUserArr);
            $req_data['total_question_attempt_user'] = $total_que_attempt_user;
            $req_data['question_selected'] = [
                [
                    'my_answer' => @$my_answer,
                    'my_correct_option_json' => (string) @$my_correct_option_json,
                ],

            ];
            $req_data['my_answer'] = @$my_answer;
            $req_data['my_correct_option_json'] = @$my_correct_option_json;

            $question_type = $getTestDt->question_type ?? '';

            $crr = [];

            if ($question_type == '2' || $question_type == '3' || $question_type == '5') {

                $getQueOptionAttr = QueOptionAnswerType::where('question_id', $show_question_id)->get(['id', 'answer_type_name']);

                $que_option_score = [];
                foreach ($getQueOptionAttr as $skey => $queVal_2) {
                    $option_val_id = $queVal_2->id;
                    $option_type_name = $queVal_2->answer_type_name;

                    $getAttemptUserArr = AttemptMocktestQuestion::where(['question_id' => $show_question_id])->get(['id', 'question_id', 'correct_option_json', 'answer']);

                    $opt_arr = [];
                    $option_count = '0';
                    foreach ($getAttemptUserArr as $skey1 => $queVal_3) {

                        $each_correct_option_json = $queVal_3->correct_option_json;
                        $each_json_ans = json_decode($each_correct_option_json);
                        if (!empty($each_json_ans)) {
                            foreach ($each_json_ans as $skey2 => $queVal_4) {

                                $option_value_id2 = $queVal_4->option_value_id;

                                if ($option_val_id == $option_value_id2) {
                                    $option_count++;
                                }
                            }
                        }
                    }

                    $avg_score = $total_que_attempt_user > 0 ? ($option_count * 100) / $total_que_attempt_user : 0;

                    $opt_arr["option_value_id"] = $option_val_id;
                    $opt_arr["option_type_name"] = $option_type_name;
                    $opt_arr["option_attempt"] = $option_count;
                    $opt_arr["option_avg_score"] = round($avg_score, 2);

                    $que_option_score[$skey] = $opt_arr;
                }

                $crr[] = $que_option_score;
            } else {

                $option_a_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "a"])->get('id')->count();
                $option_b_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "b"])->get('id')->count();
                $option_c_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "c"])->get('id')->count();
                $option_d_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "d"])->get('id')->count();
                $option_e_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "e"])->get('id')->count();
                $option_f_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "f"])->get('id')->count();

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

                $crr["option_a_score"] = round($avg_score_a, 2);
                $crr["option_b_score"] = round($avg_score_b, 2);
                $crr["option_c_score"] = round($avg_score_c, 2);
                $crr["option_d_score"] = round($avg_score_d, 2);
                $crr["option_e_score"] = round($avg_score_e, 2);
                $crr["option_f_score"] = round($avg_score_f, 2);
            }

            $req_data['question_option_user_score'] = $crr;
        }

        if($getQuesDt){
            $getAllOption = QueOption::where("question_id", $getQuesDt->id)->get();
        }else{
            $getAllOption = [];
        }

        $allOption = array();
        foreach ($getAllOption as $key => $val) {
            $getAttempt = AttemptMocktestQuestion::where("question_id", $getQuesDt->id)->where("user_id", $user_id)->first();
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

        if (count($getQuestion)) {
            $req_message = "Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        } else {
            $req_message = "No Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        }
    }

    public function mocktestQuestionsAfter(Request $request)
    {

        $count_down_time = '';

        $user_id = auth()->user()->id;
        $questions_ids_arr = [];
        $questionstype = $request->questionstype;
        $mock_test_id = $request->mock_test_id;
        $category_id = $request->category_id;
        $current_qestion_for_review = $request->current_qestion_for_review;
        $questions_ids_arr = explode(',', $request->filter_questions_id);
        $orgQuestion = $questions_ids_arr;
        if ($request->current_question_id > 0) {
            array_push($questions_ids_arr, $request->current_question_id);
        }
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
        if ($questions_ids_arr) {

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

        $user_id = auth()->user()->id;
        $getTextAllQue = TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->first();

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? '';
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $is_reviewed = (in_array($my_curr_que_id, $reviewArr)) ? '1' : '0';

        $getSrNo = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $my_curr_que_id])->first();
        $sr_no_is = @$getSrNo->sr_no ?? 1;

        if ($sr_no_is > 1 && empty($previous_question_id) && $questionstype != "flagquestion" && $questionstype != "incompletequestion") {

            $getPreviousAttemptQuestion = TempMocktestSrQuestion::where('user_id', $user_id)->where("id", "<", $getSrNo->id)->orderBy("id", "DESC")->first(['question_id']);
            $previous_question_id = $getPreviousAttemptQuestion->question_id;
        }
        $sr_no_is = (int) $sr_no_is;
        if ($questionstype != "flagquestion" && $questionstype != "incompletequestion") {
            $getNextAttemptQuestion = TempMocktestSrQuestion::where('user_id', $user_id)->where("id", ">", $getSrNo->id)->orderBy("id", "asc")->first(['question_id']);
            $next_question_id = $getNextAttemptQuestion->question_id;
        }

        $getSrNo_count = TempMocktestSrQuestion::where(['user_id' => $user_id, "mocktest_id" => $getSrNo->mocktest_id])->count();
        $req_data['total_question'] = $getSrNo_count;

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
        $getTestDt = AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $show_question_id])->first();

        if (isset($getTestDt->id)) {
            $is_question_attempt = '0';
            $my_answer = $getTestDt->answer ?? '';
            $my_correct_option_json = $getTestDt->correct_option_json ?? '';
        }

        $req_data['is_question_attempt'] = $is_question_attempt;

        if ($request->is_feedback) {
            $attemptUserArr = AttemptMocktestQuestion::where(['question_id' => $show_question_id])->groupBy('user_id')->get('user_id')->toArray();

            $total_que_attempt_user = count($attemptUserArr);
            $req_data['total_question_attempt_user'] = $total_que_attempt_user;

            $req_data['question_selected'] = [
                [
                    'my_answer' => @$my_answer,
                    'my_correct_option_json' => (string) @$my_correct_option_json,
                ],

            ];
            $req_data['my_answer'] = @$my_answer;
            $req_data['my_correct_option_json'] = @$my_correct_option_json;

            $question_type = $getTestDt->question_type ?? '';

            $option_id = '';
            $option_value_id = '';
            $crr = [];

            if ($question_type == '2' || $question_type == '3' || $question_type == '5') {
                $getQueOptionAttr = QueOptionAnswerType::where('question_id', $show_question_id)->get(['id', 'answer_type_name']);

                $que_option_score = [];
                foreach ($getQueOptionAttr as $skey => $queVal_2) {
                    $option_val_id = $queVal_2->id;
                    $option_type_name = $queVal_2->answer_type_name;

                    $getAttemptUserArr = AttemptMocktestQuestion::where(['question_id' => $show_question_id])->get(['id', 'question_id', 'correct_option_json', 'answer']);

                    $opt_arr = [];
                    $option_count = '0';
                    $storeAllJson = [];
                    foreach ($getAttemptUserArr as $skey1 => $queVal_3) {

                        $each_correct_option_json = $queVal_3->correct_option_json;
                        $each_json_ans = json_decode($each_correct_option_json);
                        $storeAllJson[] = $each_json_ans;

                        if (!empty($each_json_ans)) {
                            foreach ($each_json_ans as $skey2 => $queVal_4) {

                                $option_id = $queVal_4->option_id;
                                $option_value_id2 = $queVal_4->option_value_id;

                                if ($option_val_id == $option_value_id2) {
                                    $option_count++;
                                }
                            }
                        }
                    }

                    $avg_score = $total_que_attempt_user > 0 ? ($option_count * 100) / $total_que_attempt_user : 0;

                    $opt_arr["option_value_id"] = $option_val_id;
                    $opt_arr["option_type_name"] = $option_type_name;
                    $opt_arr["option_attempt"] = $option_count;
                    $opt_arr["option_avg_score"] = round($avg_score, 2);

                    $que_option_score[$skey] = $opt_arr;
                }

                $crr[] = $que_option_score;
                // }

            } else {

                $option_a_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "a"])->get('id')->count();
                $option_b_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "b"])->get('id')->count();
                $option_c_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "c"])->get('id')->count();
                $option_d_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "d"])->get('id')->count();
                $option_e_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "e"])->get('id')->count();
                $option_f_count = AttemptMocktestQuestion::where(['question_id' => $show_question_id, 'answer' => "f"])->get('id')->count();

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

                $crr["option_a_score"] = round($avg_score_a, 2);
                $crr["option_b_score"] = round($avg_score_b, 2);
                $crr["option_c_score"] = round($avg_score_c, 2);
                $crr["option_d_score"] = round($avg_score_d, 2);
                $crr["option_e_score"] = round($avg_score_e, 2);
                $crr["option_f_score"] = round($avg_score_f, 2);
            }

            $req_data['question_option_user_score'] = $crr;
        }

        $getAllOption = QueOption::where("question_id", $getQuesDt->id)->get();

        $allOption = array();
        foreach ($getAllOption as $key => $val) {
            $getAttempt = AttemptMocktestQuestion::where("question_id", $getQuesDt->id)->where("user_id", $user_id)->first();
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

        if (count($getQuestion)) {
            $req_message = "Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        } else {
            $req_message = "No Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        }
    }

    public function store(Request $request)
    {

        $request->validate([
            'question_id' => 'required|integer',
        ]);
        if (empty($request->mocktestId)) {
            $req_message = "please send mocktest id";
            return response()->json(['status' => true, 'data' => [], 'message' => $req_message]);
        }

        $req_data = [];

        $isPractice = $request->isPractice ? $request->isPractice : 0;
        $user_id = auth()->user()->id;
        $question_id = $request->question_id;
        $mocktest_id = $request->mocktestId;
        $category_id = $request->categoryId;

        $correct_option_json = "";

        $findAlreadyQuestionSaved = TempMocktest::where(['user_id' => $user_id, 'question_id' => $question_id])->first();
        $findAlreadyQuestionSaved_correct_option_json = $findAlreadyQuestionSaved->correct_option_json ?? '';
        $correct_option_json = !empty($request->correct_option_json) ? $request->correct_option_json : $findAlreadyQuestionSaved_correct_option_json;

        $all_filter_question_id = $request->all_filter_question_id;

        $get_before_finish = TempMocktestBeforeFinishTest::where('user_id', $user_id)->where("mocktest_id", $mocktest_id)->where("category_ids", $category_id)->first();
        if (!isset($get_before_finish->id)) {
            $temp_all_filter_dt_arr = [
                'user_id' => $user_id,
                'questions_id' => $all_filter_question_id,
                'is_practice' => $isPractice,
                'mocktest_id' => $mocktest_id,
                'category_ids' => $category_id,

            ];
            TempMocktestBeforeFinishTest::create($temp_all_filter_dt_arr);
        }

        $get_Que_Dt = QuestionAnswer::where('id', $question_id)->first();

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
        $temp_test_dt_arr = [
            'user_id' => $user_id,
            'mocktest_id' => $mocktest_id,
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

        $getTestDt = TempMocktest::where(['user_id' => $user_id, 'question_id' => $question_id])->first();

        if (isset($getTestDt->id)) {
            TempMocktest::where(['user_id' => $user_id, 'question_id' => $question_id])->update($temp_test_dt_arr);
        } else {

            if ($correct_option_json != "" || @$request->answer != "") {
                TempMocktest::create($temp_test_dt_arr);
            }
        }

        if ($correct_option_json == "" && @$request->answer == "") {

            $get_dt = TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->where("mocktest_id", $mocktest_id)->where("category_ids", $category_id)->first();
            if (isset($get_dt->id)) {
                $skip_ques_id = $get_dt->skip_question;

                $skipQueArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];
                if (!in_array($question_id, $skipQueArr)) {
                    $skipQueArr[] = $question_id;
                    $skip_srv = array_unique($skipQueArr);
                    $insert_skip_srv = (count($skip_srv) > 0) ? implode(',', $skip_srv) : "";

                    $addSkipArr = ['skip_question' => $insert_skip_srv];
                    TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->update($addSkipArr);
                }
            }
        } else {
            $get_dt = TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->where("mocktest_id", $mocktest_id)->where("category_ids", $category_id)->first();
            if (isset($get_dt->id)) {
                $skip_ques_id = $get_dt->skip_question;

                $skipQueArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];
                if (in_array($question_id, $skipQueArr)) {
                    $pos = array_search($question_id, $skipQueArr);

                    if ($pos !== false) {

                        unset($skipQueArr[$pos]);
                    }
                    $skipQueArr = array_values($skipQueArr);
                    $skip_srv = array_unique($skipQueArr);
                    $insert_skip_srv = (count($skip_srv) > 0) ? implode(',', $skip_srv) : "";

                    $addSkipArr = ['skip_question' => $insert_skip_srv];
                    TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->where("mocktest_id", $mocktest_id)->where("category_ids", $category_id)->update($addSkipArr);
                }
            }
        }
        $req_data['request_answer'] = $request->answer;

        $req_data['is_correct'] = $is_correct;

        $req_message = "Test Submitted Successfully";
        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
    }

    public function mocktestReviewBeforeFinish(Request $request)
    {

        $user_id = auth()->user()->id;

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Unseen";
        $que_status_color = "red";
        $que_status_new = "";
        $mocktestId = $request->mocktestId;
        $categoryId = $request->categoryId;
        $getTextAllQue = TempMocktestBeforeFinishTest::where(['user_id' => $user_id, 'mocktest_id' => $mocktestId, 'category_ids' => $categoryId])->first();
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

                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempMocktest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();
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
                } else if (in_array($quesId, $skipArr)) {
                    $que_status = "Not Attempted";
                    $que_statusNew = "Incomplete";
                    $que_status_color = "#efefef";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Complete";
                    $que_status_color = "green";
                } else if (isset($getTestDt)) {
                    $que_status = "Complete";
                    $que_status_color = "green";
                } else {
                    $que_status = "Not Attempted";
                    $que_status_color = "#efefef";
                }

                $getSrNo = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId, 'mocktest_id' => $mocktestId, 'category_id' => $categoryId])->first(['sr_no']);

                $sr_no_is = @$getSrNo->sr_no ?? '';

                if ($que_statusNew == "Incomplete" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] = [
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
        $req_message = "No test Found";
        return response()->json(['status' => false, 'data' => $req_data, 'message' => $req_message]);
    }
    public function show(Request $request)
    {
        $categoryId = $request->categoryId;
        $mocktestId = $request->mocktest;

        $mocktest = MocktestCategory::where("mocktest_id", $mocktestId)->where("category_id", $categoryId)->first();
        $result = $mocktest;

        $checkQuestion = AssingQuestionMocktest::where("category_id", $mocktest->category_id)->where("mocktest_id", $mocktestId)->whereHas("question")->pluck("question_id");

        $result['totalquestionCount'] = count($checkQuestion);

        if (count($checkQuestion) > 1) {
            $questionIds = implode(",", $checkQuestion->toArray());
        } else {
            $questionIds = '';
        }

        $result['categoryName'] = $mocktest->category->category_name;
        $result['totalquestion'] = $questionIds;

        unset($result['category']);

        try {

            return response()->json(['code' => 200, 'message' => 'mock test', "data" => $result], 200);
        } catch (Error $e) {
            return response()->json(['code' => 422, 'message' => 'mock test'], 200);
        }
    }
    public function mocktestReviewAfterFinish(Request $request)
    {

        $user_id = auth()->user()->id;

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Unseen";
        $que_status_color = "red";
        $que_status_new = "";
        $getTextAllQue = TempMocktestBeforeFinishTest::where(['user_id' => $user_id])->first();
        $allQuestion = TempMocktestSrQuestion::where(['user_id' => $user_id, "mocktest_id" => $request->mocktest_id])->orderBy("sr_no")->get();

        if (empty($getTextAllQue)) {
            return response()->json(['status' => false, 'data' => $req_data, 'message' => "No Review Records"]);
        }

        $questions_id = $getTextAllQue->questions_id ?? '';
        $testQueArr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $getTextAllQue->question_for_review ?? [];
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $getTextAllQue->skip_question;

        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];

        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($allQuestion as $key => $q) {

                $quesId = $q->question_id;
                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempMocktest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();

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
                    if (isset($getTestDt->id) && $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                    } else {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";
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

                $getSrNo = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId])->first(['sr_no']);

                $sr_no_is = @$getSrNo->sr_no ?? '';

                if ($que_status == "Incorrect" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr[] = [
                    'sr_no' => $sr,
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
                $allQuestionArray[] = $quesId;
                $req_data['review_list'] = $temp_test_dt_arr;
                $sr++;
            }
        }

        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        $allqustioniDs = '';
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        if (count($allQuestionArray) > 0) {
            $allqustioniDs = implode(',', $allQuestionArray);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;
        $req_data['allQuestionList'] = $allqustioniDs;

        $req_data['review_list'] = $temp_test_dt_arr;
        $req_message = "Review Test Records";
        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
    }
    public function mocktestReviewAfterFinishByCategory(Request $request)
    {

        $user_id = auth()->user()->id;

        $data = $request->all();
        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Unseen";
        $que_status_color = "red";
        $que_status_new = "";

        $allQuestion = TempMocktestSrQuestion::where(['user_id' => $user_id, "mocktest_id" => $request->mocktest_id])->orderBy("sr_no")->get();
        $resumeCategory = MocktestResume::where(['user_id' => $user_id, "mocktest_id" => $request->mocktest_id])->pluck("category_id");

        $newquery = $allQuestion;
        $allCategoryId = $newquery->pluck("category_id");
        if (count($allCategoryId) > 0) {
            $allCategoryId = $allCategoryId->toArray();
            $allCategoryId = array_unique($allCategoryId);
        }

        $questionIds = $newquery->pluck("question_id");
        $allQuestionNew = AssingQuestionMocktest::whereIn("category_id", $allCategoryId)->where("mocktest_id", $request->mocktest_id)->whereNotIn("category_id", $resumeCategory)->whereHas('question')->orderBy("id", "DESC")->get();

        if (!empty($questionIds)) {
            $questionIds = $questionIds->toArray();
        }

        $testQueArr = $questionIds;

        $reviewArr = array();
        $skipArr = array();

        $inComplete_QidArr = [];
        $flagged_QidArr = [];
        $finalArray = array();
        $allQuestionArray = array();
        sort($testQueArr);
        if (count($testQueArr) > 0) {
            $sr = 1;
            foreach ($allQuestionNew as $key => $q) {

                $quesId = $q->question_id;

                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name', 'category_id']);
                $categoryDetail = Category::where('id', $q->category_id)->first();
                $getTestDt = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $request->mocktest_id, 'category_id' => $q->category_id, 'question_id' => $quesId])->first();

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
                    if (isset($getTestDt->id) && $getTestDt->is_correct == "1") {
                        $que_status = "Correct";
                        $que_status_color = "green";
                    } else {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";
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

                $getSrNo = TempMocktestSrQuestion::where(['user_id' => $user_id, 'question_id' => $quesId, 'mocktest_id' => $request->mocktest_id, 'category_id' => $q->category_id])->first(['sr_no']);

                $sr_no_is = @$getSrNo->sr_no ?? '';

                if ($que_status == "Incorrect" || $que_status == "Unseen") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }
                $temp_test_dt_arr = [
                    'sr_no' => $sr_no_is,
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_for_review' => $que_status_new,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];

                $finalArray[$q->category_id]['categoryId'] = $q->category_id;
                $finalArray[$q->category_id]['categoryName'] = $categoryDetail->category_name;
                $finalArray[$q->category_id]['questionlist'][] = $temp_test_dt_arr;

                $allQuestionArray[] = $quesId;

                $sr++;
            }
        }
        $finalArray = array_values($finalArray);
        $flaggedQueIds = "";
        $inCompleteQueIds = "";
        $allqustioniDs = '';
        if (count($flagged_QidArr) > 0) {
            $flaggedQueIds = implode(',', $flagged_QidArr);
        }
        if (count($inComplete_QidArr) > 0) {
            $inCompleteQueIds = implode(',', $inComplete_QidArr);
        }
        if (count($allQuestionArray) > 0) {
            $allqustioniDs = implode(',', $allQuestionArray);
        }
        $req_data['flagged_question_list'] = $flaggedQueIds;
        $req_data['incomplete_question_list'] = $inCompleteQueIds;
        $req_data['allQuestionList'] = $allqustioniDs;

        $req_data['review_list'] = $finalArray;
        $req_message = "Review Test Records";
        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
    }

    public function mocktestFinish(Request $request)
    {

        $request->validate([
            'mocktest_id' => 'required',
        ]);

        $user_id = auth()->user()->id;

        $data = $request->all();
        $req_data = [];

        $mocktest_id = $request->mocktest_id;
        $category_id = $request->category_id ? $request->category_id : '';
        $resume = $request->resume ? $request->resume : 0;
        $isPractice = $request->isPractice ? $request->isPractice : 0;
        if (empty($request->mocktest_id)) {
            $req_message = "please send mocktest id";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        }

        $getTestDt = TempMocktest::where(['user_id' => $user_id])->whereRaw('FIND_IN_SET("' . $mocktest_id . '",mocktest_id)')->get();

        $checkMocktestExist = MocktestResume::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $category_id])->first();
        if (empty($checkMocktestExist)) {
            MocktestResume::insert(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $category_id]);
        }
        if ($getTestDt->count() > 0) {

            foreach ($getTestDt as $tstVal) {
                $question_id = $tstVal->question_id;
                $temp_test_dt_arr = [
                    'user_id' => $tstVal->user_id,
                    'mocktest_id' => $tstVal->mocktest_id,
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

                $getTestDt = AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $tstVal->question_id])->first();
                if (isset($getTestDt->id)) {
                    AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $question_id])->update($temp_test_dt_arr);
                } else {
                    AttemptMocktestQuestion::create($temp_test_dt_arr);
                }
            }
            if ($resume == 0) {
                TempMocktest::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $category_id])->delete();
                TempMocktestBeforeFinishTest::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_ids' => $category_id])->delete();
                MocktestResume::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $category_id])->delete();
            }

            $req_message = "Test Submitted Successfully";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        }
        $req_message = "Please attempt atleast one question";
        return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
    }
    public function mocktestScore(Request $request)
    {
        $req_data = [];

        $mocktest_id = $request->mocktest;
        $course_id = $request->course_id;
        $user_id = auth()->user()->id;

        $my_total_attempt = '0';
        $all_user_total_attempt = '0';
        $catQueArr = [];
        $testCategories = MocktestCategory::where("mocktest_id", $mocktest_id)->pluck("category_id");
        $courseDetailsRow = Course::find($course_id);
        $catQue = Category::whereIn('id', !empty([$testCategories]) ? $testCategories : [])->orderBy('sort', 'asc');
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
            $totalQuestions = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $catDt->id])->get()->count();

            if ($totalQuestions == 0) {
                continue;
            }

            $totalcategory[] = $catDt->id;

            $checkAssignQeustion = AssingQuestionMocktest::where("category_id", $catDt->id)->where("mocktest_id", $mocktest_id)->pluck("question_id");

            $totalQuestionsWithouAttempt = QuestionAnswer::whereIn('id', $checkAssignQeustion)->where(['category_id' => $catDt->id])->get()->count();

            $totalQuestions = (int) $totalQuestions;
            $my_total_attempt_que = $my_total_attempt_que + $totalQuestions;

            $totalMyAttemptCorrect = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();

            $totalMyAttemptInCorrect = AttemptMocktestQuestion::where(['user_id' => $user_id, 'mocktest_id' => $mocktest_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

            $totalAllQuestionAttemptByMe += $totalMyAttemptCorrect + $totalMyAttemptInCorrect;

            $totalFinalAttemptCorrectQuestionByMe += $totalMyAttemptCorrect;

            $totalUserQuestions = AttemptMocktestQuestion::where(['mocktest_id' => $mocktest_id, 'category_id' => $catDt->id])->get()->count();

            $totalMyAttemptCorrect = (int) $totalMyAttemptCorrect;

            $totalAllUserAttemptCorrect = AttemptMocktestQuestion::where(['mocktest_id' => $mocktest_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
            $totalAllUserAttemptCorrect = (int) $totalAllUserAttemptCorrect;

            $yScore = 0;
            if ($totalMyAttemptCorrect > 0 && $totalQuestions > 0) {
                $yScore = ($totalMyAttemptCorrect / ($totalQuestionsWithouAttempt)) * 100;
            }

            $xScore_avg = 0;
            if ($totalUserQuestions > 0 && $totalAllUserAttemptCorrect > 0) {
                $xScore_avg = ($totalAllUserAttemptCorrect / ($totalUserQuestions)) * 100;
            }

            $yPercentileScore = 0;
            $ttAtm = $totalMyAttemptCorrect + $totalMyAttemptInCorrect;
            if ($totalMyAttemptCorrect > 0) {
                $yPercentileScore = ($totalMyAttemptCorrect / ($totalMyAttemptInCorrect + $totalMyAttemptCorrect)) * 100;
            }
            $percentileArray[] = $yPercentileScore;

            if ($totalAllUserAttemptCorrect > 0 && $totalUserQuestions > 0) {
                $avg_of_all_users = ($totalAllUserAttemptCorrect * 100) / $totalUserQuestions;
            } else {
                $avg_of_all_users = '0';
            }

            $my_total_attempt = 0;

            if ($totalAllQuestionAttemptByMe > 0 && $totalMyAttemptCorrect > 0) {
                $my_total_attempt = ($totalMyAttemptCorrect * 100) / $totalAllQuestionAttemptByMe;
            }

            $all_user_total_attempt = $all_user_total_attempt + $totalAllUserAttemptCorrect;

            if ($totalMyAttemptCorrect > 0 && $totalAllQuestionAttemptByMe > 0) {
                $your_score_in_percent = ($totalMyAttemptCorrect * 100) / $totalAllQuestionAttemptByMe;
            } else {
                $your_score_in_percent = '0';
            }

            $get_total_avg = $get_total_avg + $avg_of_all_users;
            $get_my_correct_question = $get_my_correct_question + $totalMyAttemptCorrect;

            $ttCorrect += (int) $totalMyAttemptCorrect;
            $ttInCorrect += (int) $totalMyAttemptInCorrect;

            $roundOfScore = round($yScore);
            $roundOfPercentile = round($yPercentileScore);
            $roundOfAllUserAvg = round($xScore_avg);
            $totaluserCount = AttemptMocktestQuestion::select('user_id')->where(['mocktest_id' => $mocktest_id, 'category_id' => $catDt->id])->groupBy("user_id")->get()->count();

            $avgAllUserCategoryWise = ($totalAllUserAttemptCorrect / $totalUserQuestions) * 100;
            $avgAllUserCategoryWiseTotal = $avgAllUserCategoryWiseTotal + $avgAllUserCategoryWise;
            $ucatScore = CategoryUcatScore::where("category_id", $catDt->id)->where("min_score", '<=', $roundOfScore)->where("max_score", '>=', $roundOfScore)->where("course_type_id", $courseDetailsRow->course_type_id)->first();

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
                'total_question' => $totalQuestionsWithouAttempt,
                'ucat_score' => @$ucatScore->score ? $ucatScore->score : 0,
                'band_name' => @$ucatScore && @$ucatScore->band ? @$ucatScore->band->name : '',
                'total_attempt_question' => ($totalMyAttemptCorrect + $totalMyAttemptInCorrect),
            ];

            $ffScore_avg += $yScore;
            $allAvgScore_avg += $xScore_avg;
            $totalPercentileResponseCount += $yPercentileScore;
        }

        $checkAssignQeustion = AssingQuestionMocktest::where("mocktest_id", $mocktest_id)->pluck("question_id");

        $yourScore = 0;

        $total_user_by_course = AttemptMocktestQuestion::select('user_id')->where(['mocktest_id' => $mocktest_id])->groupBy('user_id')->get();
        $user_count_min = 0;
        foreach ($total_user_by_course as $key => $item) {
            $total_user_by_course_by_user = AttemptMocktestQuestion::select('user_id')->where(['user_id' => $item->user_id, 'mocktest_id' => $mocktest_id, 'is_correct' => '1'])->groupBy('user_id')->get()->count();
            if ($get_my_correct_question > $total_user_by_course_by_user) {
                $user_count_min = $user_count_min + 1;
            }
        }
        if ($user_count_min > 0 && count($total_user_by_course) > 0) {
            $you_perform_better_then = ($user_count_min / count($total_user_by_course)) * 100;
        } else {
            $you_perform_better_then = 0;
        }

        $get_avg_score_of_all_users = $category_count > 0 ?  $get_total_avg / $category_count : 0;
        $totalQuestionsNoCategory = QuestionAnswer::whereIn('id', $checkAssignQeustion)->get()->count();

        $my_total_attempt = (int) $my_total_attempt;
        $totalQuestionsNoCategory = (int) $totalQuestionsNoCategory;

        if ($my_total_attempt > 0 && $my_total_attempt_que > 0) {
            $your_percentile = ($my_total_attempt * 100) / $my_total_attempt_que;
        } else {
            $your_percentile = '0';
        }

        $your_total_average_score = $my_total_attempt * 100 / $category_count;

        if ($all_user_total_attempt > 0 && $totalQuestionsNoCategory > 0) {
            $all_user_avg_score_in_percentile = ($all_user_total_attempt * 100) / $totalQuestionsNoCategory;
        } else {
            $all_user_avg_score_in_percentile = '0';
        }

        if ($my_total_attempt > 0 && $my_total_attempt_que > 0) {
            $your_total_average_score = ($my_total_attempt * 100) / $my_total_attempt_que;
        } else {
            $your_total_average_score = '0';
        }

        $avg_score_of_all_users = $all_user_avg_score_in_percentile;

        $fnScore = 0;
        if (count($getCatgory) > 0 && $ffScore_avg > 0) {
            $fnScore = $ffScore_avg / count($getCatgory);
        }
        $fnAllUserScore = 0;
        if (count($getCatgory) > 0 && $allAvgScore_avg > 0) {
            $fnAllUserScore = $allAvgScore_avg / count($getCatgory);
        }

        $fnYourPercentile = 0;
        if (count($getCatgory) > 0 && $totalPercentileResponseCount > 0) {
            $fnYourPercentile = $totalPercentileResponseCount / count($getCatgory);
        }

        $totalCategoryQuestion = AttemptMocktestQuestion::whereIn("category_id", $totalcategory)->whereRaw('FIND_IN_SET("' . $mocktest_id . '",mocktest_id)')->where("user_id", $user_id)->count();
        if ($totalFinalAttemptCorrectQuestionByMe > 0 && $totalCategoryQuestion > 0) {
            $finalScore = ($totalFinalAttemptCorrectQuestionByMe / $totalCategoryQuestion) * 100;
        } else {
            $finalScore = 0;
        }
        if ($totalFinalAttemptCorrectQuestionByMe > 0) {
            $avgFinal = $avgAllUserCategoryWiseTotal / count($totalcategory);
        } else {
            $avgFinal = 0;
        }

        $req_data['your_score'] = round($finalScore);
        $req_data['avg_score_of_all_users'] = round($avgFinal);
        $req_data['your_total_average_score'] = round($your_total_average_score);
        $req_data['your_percentile'] = round($fnYourPercentile);
        $req_data['you_perform_better_then'] = $you_perform_better_then;
        $req_data['category_data'] = $catQueArr;

        $getAllUserRank = AttemptMocktestQuestion::select('user_id', DB::raw('Count(is_correct) as total_score'))->where(['mocktest_id' => $mocktest_id, 'is_correct' => '1'])->orderBy('total_score', 'desc')->groupBy('user_id')->get();
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
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        } else {
            $req_message = "No Record Found";
            return response()->json(['status' => true, 'data' => $req_data, 'message' => $req_message]);
        }
    }
    public function short_string_char($str)
    {
        $ret = '';
        foreach (explode(' ', $str) as $word) {
            $ret .= strtoupper($word[0]);
        }

        return $ret;
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
