<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\QuestionAnswer;
use App\Models\TempBeforeFinishTest;
use App\Models\TempSrQuestion;
use App\Models\TempTest;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $user_id = auth()->user()->id;

        // fetch questions from temp test
        $temp_before_finish_test = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
        $all_question_id = $temp_before_finish_test->questions_id ?? '';

        $question_id = $request->question_id;

        $courseId = $request->courseId;
        $categoryIds = $request->categoryIds;

        $isPractice = $request->isPractice ? $request->isPractice : 0;

        if (!empty($courseId) && !empty($categoryIds)) {
            TempTest::where(['user_id' => $user_id])->delete();
            TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
            TempSrQuestion::where(['user_id' => $user_id])->delete();
        }

        $req_data = [];
        $req_message = '';

        $checked_status = '';

        if (isset($temp_before_finish_test->id)) {
            $reviewed_ques_id = $temp_before_finish_test->question_for_review;
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

        if ($all_question_id != "") {
            $queArr = explode(',', $all_question_id);
            sort($queArr);

            $existingQuestions = TempSrQuestion::where('user_id', $user_id)
                ->whereIn('question_id', $queArr)
                ->pluck('question_id')
                ->toArray();

            $toInsert = [];

            $sr = 1;
            foreach ($queArr as $qId) {
                if (!in_array($qId, $existingQuestions)) {
                    $toInsert[] = [
                        'sr_no' => $sr,
                        'user_id' => $user_id,
                        'question_id' => $qId,
                        'is_practice' => $isPractice,
                    ];
                    $sr++;
                }
            }

            if (!empty($toInsert)) {
                TempSrQuestion::insert($toInsert);
            }
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => $req_message
        ]);
    }

    public function reviewTestBeforeFinish(Request $request)
    {
        $user_id = auth()->user()->id;

        $req_data = [];

        $temp_test_dt_arr = [];

        $que_status = "Not Attempted";
        $que_status_color = "#efefef";

        $temp_before_finish_test = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
        $questions_id = $temp_before_finish_test->questions_id ?? '';
        $questions_id_arr = (!empty($questions_id)) ? explode(',', $questions_id) : [];

        $reviewed_ques_id = $temp_before_finish_test->question_for_review;
        $reviewArr = (!empty($reviewed_ques_id)) ? explode(',', $reviewed_ques_id) : [];

        $skip_ques_id = $temp_before_finish_test->skip_question;
        $skipArr = (!empty($skip_ques_id)) ? explode(',', $skip_ques_id) : [];

        $inComplete_QidArr = [];
        $flagged_QidArr = [];

        if (count($questions_id_arr) > 0) {
            foreach ($questions_id_arr as $key => $quesId) {

                $check_already_review = "0";
                $getQueDt = QuestionAnswer::where(['id' => $quesId])->first(['question_name']);

                $getTestDt = TempTest::where(['user_id' => $user_id, 'question_id' => $quesId])->first();

                if (in_array($quesId, $reviewArr) && in_array($quesId, $skipArr)) {
                    if (isset($getTestDt->id)) {
                        $que_status = "Complete";
                        $que_status_color = "green";

                        $check_already_review = 1;
                    } else {
                        $que_status = "Not Attempted";
                        $que_status_color = "#efefef";

                        $check_already_review = 1;
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
                    $que_status_color = "#efefef";
                } else if (isset($getTestDt->is_correct)) {
                    $que_status = "Complete";
                    $que_status_color = "green";
                } else {
                    $que_status = "Not Attempted";
                    $que_status_color = "#efefef";
                }


                if ($que_status == "Not Attempted") {
                    $inComplete_QidArr[] = $quesId;
                }
                if ($que_status == "Flagged" || $check_already_review == 1) {
                    $flagged_QidArr[] = $quesId;
                }

                $temp_test_dt_arr[] =  [
                    'question_id' => $quesId,
                    'question' => $getQueDt->question_name ?? '',
                    'que_status' => $que_status,
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];


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

        return response()->json([
            'data' => $req_data,
            'message' => $req_message
        ]);
    }

    public function reviewTestAfterFinish(Request $request)
    {
        $user_id = auth()->user()->id;

        $req_data = [];

        $temp_test_dt_arr = [];
        $que_status = "Not Attempted";
        $que_status_color = "#efefef";

        $getTextAllQue = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        if (empty($getTextAllQue)) {
            return response()->json([
                'data' => $req_data,
                'message' => 'No review records'
            ]);
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
                    'que_status_color' => $que_status_color,
                    'check_already_review' => $check_already_review,
                ];
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

        return response()->json([
            'data' => $req_data,
            'message' => $req_message
        ]);
    }
}
