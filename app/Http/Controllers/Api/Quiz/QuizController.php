<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\AssignQuestion;
use App\Models\AttemptQuestion;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\QueOption;
use App\Models\QuestionAnswer;
use App\Models\TempBeforeFinishTest;
use App\Models\TempSrQuestion;
use App\Models\TempTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
//    public function applyfilter(Request $request)
//    {
//        $request->validate([
//            'course_id' => 'required',
//            'subcategoryIds' => 'required',
//            'questionsCount' => 'required',
//            'filter' => 'required',
//        ]);
//
//        $subcategoryIds = $request->subcategoryIds;
//        $questionsCount = $request->questionsCount;
//        $filter = $request->filter;
//        $course_id = $request->course_id;
//
//        $user_id = auth()->user()->id;
//
//        // check if user has bought the plan
//        $userOrderDetails = OrderDetail::where(['particular_record_id' => $course_id, 'package_for' => '1'])->with([
//            'order' => function ($query) use ($user_id) {
//                $query->where('user_id', $user_id);
//            }
//        ])->get();
//
//        if (!empty($userOrderDetails)) {
//            $planPurchased = true;
//        } else {
//            $planPurchased = false;
//        }
//
//        if (!$planPurchased) {
//            $testModeQuestionIds = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
//        }
//
//        // fetch all the questions for the given subcategory ids
//        $allSelectedQuestionIdsQuery = QuestionAnswer::whereHas(
//            'courses',
//            function ($query) use($course_id){
//                $query->where('course_id', $course_id);
//            }
//        )->whereIn('sub_category_ids', $subcategoryIds)->where('status', 1);
//
//        if (!$planPurchased) {
//            $allSelectedQuestionIdsQuery->whereIn('id', $testModeQuestionIds);
//        }
//
//        $allSelectedQuestionIds = $allSelectedQuestionIdsQuery->pluck('id')->toArray();
//
//        if ($filter == 'all') {
//            $selectedQuestionIds = $allSelectedQuestionIds;
//        } else if ($filter == 'new') {
//            $attemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();
//
//            $selectedQuestionIds = array_diff($allSelectedQuestionIds, $attemptedQuestionIds);
//        } else if ($filter == 'newAndIncorrect') {
//            $correctAttemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();
//
//            $selectedQuestionIds = array_diff($allSelectedQuestionIds, $correctAttemptedQuestionIds);
//        }
//
//        // randomly pick values from the array
//        $questionsCount = max(0, min($questionsCount, count($selectedQuestionIds)));
//
//        $randomKeys = array_rand($selectedQuestionIds, $questionsCount);
//        $randomQuestionIds = [];
//
//        foreach ((array) $randomKeys as $key) {
//            $randomQuestionIds[] = $selectedQuestionIds[$key];
//        }
//
//        $questionsStr = implode(',', $randomQuestionIds);
//
//        $existingTempTest = TempBeforeFinishTest::where(['user_id' => $user_id])->first();
//
//        if (isset($existingTempTest->id)) {
//            TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
//        }
//
//        // create new test in temp test for the user
//        $tempTest =  [
//            'user_id' => $user_id,
//            'questions_id' => $questionsStr,
//            'is_practice' => 0,
//        ];
//
//        TempBeforeFinishTest::create($tempTest);
//
//        return response()->json(['selectedQuestionIds' => $randomQuestionIds]);
//    }

    public function applyfilter(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
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
        $userOrderDetails = OrderDetail::where(['particular_record_id' => $course_id, 'package_for' => '1'])->with([
            'order' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }
        ])->get();

        if (!empty($userOrderDetails)) {
            $planPurchased = true;
        } else {
            $planPurchased = false;
        }

        $questionList = $this->getSubCategoriesQuestionsList($request, $planPurchased);

        if ($filter == 'all') {
            $selectedQuestionIds = $questionList['selectedQuestionIds'] ?? [];
        } else if ($filter == 'new') {
            $attemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();
            $selectedQuestionIds = array_values(array_diff($questionList['selectedQuestionIds'], $attemptedQuestionIds));
        } else if ($filter == 'newAndIncorrect') {
            $correctAttemptedQuestionIds = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'is_correct' => 1])->whereIn('sub_category_ids', $subcategoryIds)->pluck('question_id')->toArray();
            $selectedQuestionIds = array_diff($questionList['selectedQuestionIds'], $correctAttemptedQuestionIds);
        }

        $existingTempTest = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        if (isset($existingTempTest->id)) {
            TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        }

        // create new test in temp test for the user
        $tempTest =  [
            'user_id' => $user_id,
            'questions_id' => $questionList['questionStr'] ?? [],
            'is_practice' => 0,
        ];

        TempBeforeFinishTest::create($tempTest);
        return response()->json(['selectedQuestionIds' => $selectedQuestionIds]);
    }

    public function getSubCategoriesQuestionsList($request, $planPurchased){
        $subcategoryIds = $request->subcategoryIds;
        $questionsCount = $request->questionsCount;
        $filter = $request->filter;
        $course_id = $request->course_id;
        $user_id = auth()->user()->id;
        $totalLimit = $request->questionsCount;
        // Fetch all questions and group them by subcategory
        $questions = QuestionAnswer::whereHas('courses', function ($query) use ($course_id) {
            $query->where('course_id', $course_id);
        })->whereIn('sub_category_ids', $subcategoryIds)
            ->where('status', 1)
            ->whereNull('deleted_at')
            //->orderBy('sub_category_ids')  // Order by subcategory to maintain order
            ->get(['id', 'sub_category_ids'])
            ->groupBy('sub_category_ids');

        if (!$planPurchased) {
            $testModeQuestionIds = AssignQuestion::where('course_id', $course_id)->pluck('question_id')->toArray();
        }

        if (!$planPurchased) {
            $questions->whereIn('id', $testModeQuestionIds);
        }


        $selectedQuestionIds = collect();
        $remainingLimit = $totalLimit;

        // Separate smaller and larger subcategories
        $smallerSubcategories = [];
        $largerSubcategories = [];
        $accumulatedCount = 0;
        foreach ($questions as $subcategoryId => $questionGroup) {
            $questionGroupCount = count($questionGroup);
            $accumulatedCount += $questionGroupCount;
            //Log::info('$questionGroup'.count($questionGroup));
            if ($accumulatedCount <= $remainingLimit) {
                $smallerSubcategories[$subcategoryId] = $questionGroup;
            } else {
                $largerSubcategories[$subcategoryId] = $questionGroup;
            }
        }
        // Select all questions from smaller subcategories
        foreach ($smallerSubcategories as $subcategoryId => $questionGroup) {
            $selectedQuestionIds = $selectedQuestionIds->merge($questionGroup->pluck('id'));
            $remainingLimit -= count($questionGroup);

        }
        if ($remainingLimit > 0 && count($largerSubcategories) > 0) {
            $remainingSubcategoriesCount = count($largerSubcategories);
            $limitPerSubcategory = floor($remainingLimit / $remainingSubcategoriesCount);
            $extraLimit = $remainingLimit % $remainingSubcategoriesCount;

            foreach ($largerSubcategories as $subcategoryId => $questionGroup) {
                $selectedQuestionIds = $selectedQuestionIds->merge($questionGroup->take($limitPerSubcategory)->pluck('id'));
                if ($extraLimit > 0) {
                    $selectedQuestionIds = $selectedQuestionIds->merge($questionGroup->skip($limitPerSubcategory)->take(1)->pluck('id'));
                    $extraLimit--;
                }
            }

        }
        // Now $selectedQuestionIds contains the desired set of question IDs, limited to 200
        $selectedQuestionIds = $selectedQuestionIds->take($totalLimit);
        $selectedQuestionIdsArray = $selectedQuestionIds->toArray();
        $questionsStr = implode(',', $selectedQuestionIdsArray);
        return  [
            'selectedQuestionIds' => $selectedQuestionIdsArray,
            'questionStr' => $questionsStr,
        ];
    }

    public function makeTest(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer',
        ]);

        $req_data = [];

        $isPractice = $request->isPractice ? $request->isPractice : 0;
        $user_id = auth()->user()->id;
        $question_id = $request->question_id;

        $correct_option_json = "";

        $findAlreadyQuestionSaved = TempTest::where(['user_id' => $user_id, 'question_id' => $question_id])->first();
        $findAlreadyQuestionSaved_correct_option_json = $findAlreadyQuestionSaved->correct_option_json ?? '';
        $correct_option_json = !empty($request->correct_option_json) ? $request->correct_option_json : $findAlreadyQuestionSaved_correct_option_json;;


        $temp_before_finish_test = TempBeforeFinishTest::where('user_id', $user_id)->first();
        $all_filter_question_id = $temp_before_finish_test->questions_id;

        if (!isset($temp_before_finish_test->id)) {
            $new_temp_before_finish_test =  [
                'user_id' => $user_id,
                'questions_id' => $all_filter_question_id,
                'is_practice' => $isPractice,
            ];
            TempBeforeFinishTest::create($new_temp_before_finish_test);
        }


        $question = QuestionAnswer::where('id', $question_id)->first();

        $course_id = $question->course_id;
        $tutorial_id = $question->tutorial_id;
        $category_id = $question->category_id;
        $sub_category_ids = $question->sub_category_ids;
        $question_type = $question->question_type;


        if ($question_type == 2 || $question_type == 3 || $question_type == 4) {
            $option_decode = json_decode($correct_option_json);

            $correct_option_count = 0;
            $total_options = ($correct_option_json != "") ? count($option_decode) : 0;

            if (!is_null($option_decode)) {
                foreach ($option_decode as $option) {
                    $option_id = $option->option_id;
                    $option_value_id = $option->option_value_id;

                    $getCorrectOption = QueOption::where(["id" => $option_id, "option_value_id" => $option_value_id])->count();

                    if ($getCorrectOption > 0) {
                        $correct_option_count++;
                    }
                }
            }

            $is_correct = ($total_options == $correct_option_count) ? 1 : 0;
        } else {
            $correctArr = explode(',', $question->correct_answer);

            $is_correct = (in_array($request->answer, $correctArr)) ? 1 : 0;
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
        $req_data['is_correct'] = $is_correct;

        $req_message = "Test Submitted Successfully";

        return response()->json(['data' => $req_data, 'message' => 'Test Submitted Successfully.']);
    }

    public function finishTest(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
        ]);

        $user_id = auth()->user()->id;
        $req_data = [];
        $course_id = $request->course_id;
        $isPractice = $request->isPractice ? $request->isPractice : 0;

        $getTestDt = TempTest::where(['user_id' => $user_id])->get();

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

                $getAttemptedQuestion = AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $tstVal->question_id])->first();

                if (isset($getAttemptedQuestion->id)) {
                    AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $question_id])->update($temp_test_dt_arr);
                } else {
                    AttemptQuestion::create($temp_test_dt_arr);
                }
            }

            $req_message = "Test Submitted Successfully";
            return response()->json([
                'status' => true,
                'code' => 200,
                'data' => $req_data,
                'message' => $req_message
            ]);
        }
        $req_message = "Please attempt atleast one question";
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => $req_message
        ]);
    }


    public function resetQuiz()
    {
        $user_id = auth()->user()->id;

        TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        TempTest::where(['user_id' => $user_id])->delete();

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [],
            'message' => 'Quiz reset successfully.'
        ]);
    }

    public function additionalTimeCategory(Request $request)
    {
        $req_data = [];

        $request->validate([
            'course_id' => 'required',
        ]);

        $user_id = auth()->user()->id;
        $courseid = $request->course_id;
        $findCourse = Course::find($courseid);

        $popupStatus = !empty($findCourse->is_modal) ? 1 : 0;

        $count_down_time = '';

        // $queIdArr = $request->filter_questions_id;

        $queId = TempBeforeFinishTest::where(['user_id' => $user_id])->first()->questions_id;
        $queIdArr = explode(',', $queId);

        $questionTimes = QuestionAnswer::whereIn('question_answer_tbl.id', $queIdArr)
            ->leftJoin('category_tbl', 'category_tbl.id', '=', 'question_answer_tbl.category_id')
            ->pluck('category_tbl.time', 'question_answer_tbl.id');

        $question_time = '00:00:00';

        foreach ($questionTimes as $queId => $que_time) {
            if ($que_time !== null) {
                $question_time = $this->sum_the_time($question_time, $que_time);
            }
        }

        $count_down_time = date('H:i:s', strtotime($question_time));
        $hours = date('H', strtotime($question_time));
        $min = date('i', strtotime($question_time));
        $sec = date('s', strtotime($question_time));

        $total_min = ($hours * 60) + $min + ($sec / 60);

        $after_per_25 = ($total_min * 1.25);

        $after_per_25_explode = explode('.', $after_per_25);
        $after_per_25_min_whole = $after_per_25_explode[0];

        // split minutes to hours and minutes
        $after_per_25_hour = floor($after_per_25_min_whole / 60);
        $after_per_25_min = ($after_per_25_min_whole % 60);
        $after_per_25_sec = ($after_per_25 * 60) % 60;

        $final_time_25 = date('H:i:s', strtotime($after_per_25_hour . ':' . $after_per_25_min . ':' . $after_per_25_sec));

        $after_per_50 = ($total_min * 1.5);

        $after_per_50_explode = explode('.', $after_per_50);
        $after_per_50_min_whole = $after_per_50_explode[0];

        $after_per_50_hour = floor($after_per_50_min_whole / 60);
        $after_per_50_min = ($after_per_50_min_whole % 60);
        $after_per_50_sec = ($after_per_50 * 60) % 60;

        $final_time_50 = date('H:i:s', strtotime($after_per_50_hour . ':' . $after_per_50_min . ':' . $after_per_50_sec));


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
        $req_data['popup_status'] = $findCourse->course_type_id == 1 ? $popupStatus : 0;

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => 'Additional Time Data'
        ]);
    }

    public function destroy()
    {
        $user_id = auth()->user()->id;

        TempTest::where(['user_id' => $user_id])->delete();
        TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        TempSrQuestion::where(['user_id' => $user_id])->delete();

        AttemptQuestion::where(['user_id' => $user_id, 'is_practice' => 1])->delete();

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [],
            'message' => 'Data Deleted Successfully'
        ]);
    }

    public function sum_the_time($time1, $time2)
    {
        $times = array($time1, $time2);
        $hour = 0;
        $minute = 0;
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
}
