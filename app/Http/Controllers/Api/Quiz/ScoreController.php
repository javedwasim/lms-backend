<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\AttemptQuestion;
use App\Models\Category;
use App\Models\CategoryUcatScore;
use App\Models\Course;
use App\Models\QuestionAnswer;
use App\Models\TempBeforeFinishTest;
use App\Models\TempTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoreController extends Controller
{
    public function getScoreAfterTestFinish(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
        ]);

        $req_data = [];

        $user_id = auth()->user()->id;

        $course_id = $request->course_id;
        $course = Course::find($course_id);

        $tempTest = TempBeforeFinishTest::where("user_id", $user_id)->orderBy("id", "DESC")->first();
        $question_ids = explode(",", $tempTest->questions_id);

        $categoryIds = QuestionAnswer::whereIn('id', $question_ids)->pluck('category_id')->toArray();
        $categoryIds = array_unique($categoryIds);

        $categories = Category::whereIn('id', $categoryIds)->orderBy('sort', 'asc')->get();

        $categoryResponse = [];

        $myAttemptedQuestions = 0;
        $myCorrectAttemptedQuestions = 0;

        $totalAttemptedQuestions = 0;
        $totalCorrectAttemptedQuestions = 0;

        foreach ($categories as $category) {
            $myAttemptedQuestionsForCategory = TempTest::where(['user_id' => $user_id, 'category_id' => $category->id])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();
            $myAttemptedQuestions += $myAttemptedQuestionsForCategory;

            $myCorrectAttemptedQuestionsForCategory = TempTest::where(['user_id' => $user_id, 'category_id' => $category->id, 'is_correct' => '1'])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();
            $myCorrectAttemptedQuestions += $myCorrectAttemptedQuestionsForCategory;

            $myIncorrectAttemptedQuestionsForCategory = $myAttemptedQuestionsForCategory - $myCorrectAttemptedQuestionsForCategory;


            $totalAttemptedQuestionsForCategory = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $category->id])->get()->count();
            $totalAttemptedQuestions += $totalAttemptedQuestionsForCategory;

            $totalCorrectAttemptedQuestionsForCategory = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $category->id, 'is_correct' => '1'])->get()->count();
            $totalCorrectAttemptedQuestions += $totalCorrectAttemptedQuestionsForCategory;


            $myAvgScoreForCategory = 0;
            if ($myAttemptedQuestionsForCategory > 0) {
                $myAvgScoreForCategory = ($myCorrectAttemptedQuestionsForCategory / ($myAttemptedQuestionsForCategory)) * 100;
            }

            $userAvgScoreForCategory = 0;
            if ($totalAttemptedQuestionsForCategory > 0) {
                $userAvgScoreForCategory = ($totalCorrectAttemptedQuestionsForCategory / ($totalAttemptedQuestionsForCategory)) * 100;
            }

            $myRoundedAvgScoreForCategory = round($myAvgScoreForCategory);

            $ucatScore = CategoryUcatScore::where("category_id", $category->id)->where("min_score", '<=', $myRoundedAvgScoreForCategory)->where("max_score", '>=', $myRoundedAvgScoreForCategory)->where("course_type_id", $course->course_type_id)->first();

            $percentile = $this->percentileCalculation($category->id, $myRoundedAvgScoreForCategory, $user_id, $course_id);

            $categoryResponse[] = [
                "category_name" => $category->category_name,
                "my_total_correct_questions" => $myRoundedAvgScoreForCategory,
                "avg_of_all_users" => round($userAvgScoreForCategory),
                "correct" => $myCorrectAttemptedQuestionsForCategory,
                "incorrect" => $myIncorrectAttemptedQuestionsForCategory,
                "your_score_in_percent" => $myRoundedAvgScoreForCategory,
                "your_score_in_percentile" => $percentile,
                "ucat_score" => $ucatScore ? $ucatScore->score : 0,
            ];
        }

        $myTotalAvgScore = 0;
        if ($myAttemptedQuestions > 0) {
            $myTotalAvgScore = ($myCorrectAttemptedQuestions / ($myAttemptedQuestions)) * 100;
        }

        $userTotalAvgScore = 0;
        if ($totalAttemptedQuestions > 0) {
            $userTotalAvgScore = ($totalCorrectAttemptedQuestions / ($totalAttemptedQuestions)) * 100;
        }


        $req_data['your_score'] = round($myTotalAvgScore);
        $req_data['avg_score_of_all_users'] = round($userTotalAvgScore);
        $req_data['category_data'] = $categoryResponse;

        if (count($categories)) {
            $req_message = "Record Found";
            return response()->json([
                'data' => $req_data,
                'message' => $req_message
            ]);
        } else {
            $req_message = "No Record Found";
            return response()->json([
                'data' => $req_data,
                'message' => $req_message
            ]);
        }
    }

    public function short_string_char($str)
    {
        $ret = '';

        foreach (explode(' ', $str) as $word)
            $ret .= strtoupper($word[0]);

        return $ret;
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

    public function getScoreAfterTestFinishWithAllQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
        ]);

        $req_data = [];

        $course_id = $request->course_id;
        $user_id = auth()->user()->id;
        $my_total_attempt = '0';
        $all_user_total_attempt = '0';
        $catQueArr = [];
        $courseDetailsRow = Course::find($course_id);
        $catQue = Category::whereIn('id', !empty([$courseDetailsRow->categories]) ? explode(',', $courseDetailsRow->categories) : [])->orderBy('sort', 'asc');

        $getCatgory = $catQue->get();
        $category_count = $catQue->count();

        $my_total_attempt_que = 0;

        $get_total_avg = 0;
        $get_my_correct_question = 0;

        $totalCRQ = 0;
        $totalIRQ = 0;
        $totalTRQ = 0;

        foreach ($getCatgory as $catDt) {
            $totalQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();
            $totalQuestions = (int)$totalQuestions;
            $my_total_attempt_que = $my_total_attempt_que + $totalQuestions;

            $totalMyAttemptCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '1'])->get()->count();
            $totalMyAttemptInCorrect = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id, 'category_id' => $catDt->id, 'is_correct' => '0'])->get()->count();

            $totalUserQuestions = AttemptQuestion::where(['course_id' => $course_id, 'category_id' => $catDt->id])->get()->count();

            $totalMyAttemptCorrect = (int)$totalMyAttemptCorrect;
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

        $get_avg_score_of_all_users = $category_count > 0 ? $get_total_avg / $category_count : 0;
        $totalQuestionsNoCategory =  QuestionAnswer::whereRaw('FIND_IN_SET("' . $course_id . '",course_id)')->get()->count();
        $my_total_attempt = (int)$my_total_attempt;
        $totalQuestionsNoCategory = (int)$totalQuestionsNoCategory;

        if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
            $your_percentile =  ($my_total_attempt * 100) / $my_total_attempt_que;
        else
            $your_percentile = '0';

        $your_total_average_score = $my_total_attempt * 100 / $category_count;

        if ($my_total_attempt > 0 && $my_total_attempt_que > 0)
            $your_total_average_score =  ($my_total_attempt * 100) / $my_total_attempt_que;
        else
            $your_total_average_score = '0';

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
