<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\AssignQuestion;
use App\Models\AssingQuestionMocktest;
use App\Models\AttemptMocktestQuestion;
use App\Models\AttemptQuestion;
use App\Models\Course;
use App\Models\LikeUnlike;
use App\Models\OrderDetail;
use App\Models\QueOption;
use App\Models\QueOptionAnswerType;
use App\Models\QuestionAnswer;
use App\Models\Rating;
use App\Models\TempBeforeFinishTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function show(Request $request, QuestionAnswer $question)
    {
        $req_data = [];

        $tmp_before_finish_test = TempBeforeFinishTest::where('user_id', auth()->user()->id)->first();

        $total_question = explode(',', $tmp_before_finish_test->questions_id);

        $review_questions = explode(',', $tmp_before_finish_test->question_for_review);

        $req_data['total_question'] = count($total_question);
        $req_data['is_question_reviewed'] = array_search($question->id, $review_questions) !== false ? '1' : '0';
        $req_data['serial_no'] = array_search($question->id, $total_question) + 1;

        $req_data['current_question_id'] = $question->id;

        // if last question
        if (count($total_question) == array_search($question->id, $total_question) + 1) {
            $req_data['next_question_id'] = null;
        } else {
            $req_data['next_question_id'] = (int)$total_question[array_search($question->id, $total_question) + 1] ?? null;
        }

        // if first question
        if (array_search($question->id, $total_question) == 0) {
            $req_data['previous_question_id'] = null;
        } else {
            $req_data['previous_question_id'] = (int)$total_question[array_search($question->id, $total_question) - 1] ?? null;
        }

        $req_data['question_details'] = $question;

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => "Question Details"
        ]);
    }

    public function courseQuestions(Request $request, Course $course)
    {
        $package_ids = OrderDetail::where('particular_record_id', $course->id)->whereHas(
            'order',
            function ($query) {
                $query->where('user_id', auth()->user()->id);
            }
        )->pluck('package_id');

        $question_ids = [];

        if (count($package_ids) == 0) {
            $question_ids = AssignQuestion::where('course_id', $course->id)->pluck('question_id');
        } else {
            $all_question_ids = QuestionAnswer::whereHas(
                'packages',
                function ($query) use ($package_ids) {
                    $query->whereIn('id', $package_ids);
                }
            )->pluck('id');

            if ($request->filter_type == 'all') {
                // all questions
                $question_ids = $all_question_ids;
            } elseif ($request->filter_type == 'newAndIncorrect') {
                // new and incorrect questions
                $attempted_question_ids = AttemptQuestion::where('user_id', auth()->user()->id)->where('course_id', $course->id)->where('is_correct', 1)->pluck('question_id');
                $question_ids = $all_question_ids->diff($attempted_question_ids);
            } else {
                // new questions
                $attempted_question_ids = AttemptQuestion::where('user_id', auth()->user()->id)->where('course_id', $course->id)->pluck('question_id');
                $question_ids = $all_question_ids->diff($attempted_question_ids);
            }
        }

        $questions = $course->load([
            'categories_detail' => function ($query) use ($question_ids) {
                return $query->with('sub_categories', function ($query) use ($question_ids) {
                    return $query->withCount([
                        'questions' => function ($query) use ($question_ids) {
                            $query->whereIn('id', $question_ids)->where('status', 1);
                        },
                        'attempted_questions' => function ($query) use ($question_ids) {
                            $query->whereIn('question_id', $question_ids)->where('user_id', auth()->user()->id);
                        },
                        'correct_attempted_questions' => function ($query) use ($question_ids) {
                            $query->whereIn('question_id', $question_ids)->where('user_id', auth()->user()->id);
                        },
                        'incorrect_attempted_questions' => function ($query) use ($question_ids) {
                            $query->whereIn('question_id', $question_ids)->where('user_id', auth()->user()->id);
                        }
                    ]);
                });
            }
        ]);

        return new QuestionResource($questions);
    }

    public function react(Request $request, QuestionAnswer $question)
    {
        $request->validate([
            'like_unlike_status' => 'required',
        ]);

        $user_id = auth()->user()->id;

        $req_data = [];
        $question_id = $question->id;

        if ($request->like_unlike_status == '1') {
            $req_message = "Liked Successful";
        } else if ($request->like_unlike_status == '2') {
            $req_message = "Disliked Successful";
        }

        $check_record = LikeUnlike::where(['user_id' => $user_id, 'question_id' => $question->id, 'like_unlike_status' => $request->like_unlike_status])->first(['id', 'question_id', 'like_unlike_status']);

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

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => $req_message
        ]);
    }

    public function questionFeedback(Request $request, QuestionAnswer $question)
    {
        $req_data = [];

        $user_id = auth()->user()->id;

        $req_data['user_id'] = $user_id;
        $req_data['question_list'] = $question;

        $req_data['total_question_attempt_user'] = AttemptQuestion::where(['question_id' => $question->id])->groupBy('user_id')->get('user_id')->count();
        $req_data['my_answer'] = AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['answer'])->answer ?? '';
        $req_data['my_correct_option_json'] = AttemptQuestion::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['correct_option_json'])->correct_option_json ?? '';
        $req_data['my_question_rating'] = Rating::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['rating'])->rating ?? '0';
        $req_data['like_count'] = LikeUnlike::where(['question_id' => $question->id, 'like_unlike_status' => '1'])->get(['id'])->count();
        $req_data['dislike_count'] = LikeUnlike::where(['question_id' => $question->id, 'like_unlike_status' => '2'])->get(['id'])->count();

        $req_data['options_count'] = AttemptQuestion::where('question_id', $question->id)
            ->groupBy('answer')
            ->selectRaw('answer, COUNT(id) as count')
            ->pluck('count', 'answer')
            ->toArray();

        $req_data['total_options_count'] = AttemptQuestion::where('question_id', $question->id)->get(['id'])->count();

        $getAllOption = QueOption::where("question_id", $question->id)->get();
        $allOption = array();
        foreach ($getAllOption as $key => $val) {
            $getAttempt = AttemptQuestion::where("question_id", $question->id)->where("user_id", $user_id)->first() ?? AttemptMocktestQuestion::where("question_id", $question->id)->where("user_id", $user_id)->first();

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

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => "Question Details"
        ]);
    }

    public function mockQuestionFeedback(Request $request, QuestionAnswer $question)
    {
        $req_data = [];

        $user_id = auth()->user()->id;

        $req_data['user_id'] = $user_id;
        $req_data['question_list'] = $question;

        $req_data['total_question_attempt_user'] = AttemptMocktestQuestion::where(['question_id' => $question->id])->groupBy('user_id')->get('user_id')->count();
        $req_data['my_answer'] = AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['answer'])->answer ?? '';
        $req_data['my_correct_option_json'] = AttemptMocktestQuestion::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['correct_option_json'])->correct_option_json ?? '';
        $req_data['my_question_rating'] = Rating::where(['user_id' => $user_id, 'question_id' => $question->id])->first(['rating'])->rating ?? '0';
        $req_data['like_count'] = LikeUnlike::where(['question_id' => $question->id, 'like_unlike_status' => '1'])->get(['id'])->count();
        $req_data['dislike_count'] = LikeUnlike::where(['question_id' => $question->id, 'like_unlike_status' => '2'])->get(['id'])->count();

        $req_data['options_count'] = AttemptMocktestQuestion::where('question_id', $question->id)
            ->groupBy('answer')
            ->selectRaw('answer, COUNT(id) as count')
            ->pluck('count', 'answer')
            ->toArray();

        $req_data['total_options_count'] = AttemptMocktestQuestion::where('question_id', $question->id)->get(['id'])->count();

        $getAllOption = QueOption::where("question_id", $question->id)->get();
        $allOption = array();
        foreach ($getAllOption as $key => $val) {
            $getAttempt = AttemptMocktestQuestion::where("question_id", $question->id)->where("user_id", $user_id)->first();

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

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $req_data,
            'message' => "Question Details"
        ]);
    }
}
