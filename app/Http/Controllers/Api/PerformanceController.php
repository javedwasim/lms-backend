<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssingQuestionMocktest;
use App\Models\AttemptMocktestQuestion;
use App\Models\AttemptQuestion;
use App\Models\Category;
use App\Models\CategoryUcatScore;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseExamDate;
use App\Models\MocktestCategory;
use App\Models\Order;
use App\Models\QuestionAnswer;
use App\Models\TempPerformance;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\WatchedTutorial;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $performanceId = $request->id;
        $performanceData = TempPerformance::where("uniqueId", $performanceId)->get();
        $data = array();

        if (empty($performanceData)) {
            $req_data = [
                "status" => false,
                "code" => "101",
                "data" => $data,
                "summaryData" => [],
                "message" => "No data found",
            ];
            return response()->json($req_data);
        }

        foreach ($performanceData as $key => $val) {
            $res = [];

            $course = Course::find($val->course_id);

            $category_ids = CourseCategory::where('course_id', $val->course_id)->pluck('category_id')->toArray();

            $user_average = AttemptQuestion::where(['course_id' => $val->course_id, 'user_id' => $val->user_id])->avg('is_correct') ?? 0;

            $less_than_average = AttemptQuestion::where('course_id', $val->course_id)
                ->selectRaw('user_id, avg(is_correct) as average')
                ->having('average', '<', $user_average)->groupBy('user_id')->count();

            $users_count = AttemptQuestion::where('course_id', $val->course_id)->distinct()->count('user_id');

            $res['is_question_number'] = @$course->is_tutorial == 1 && $course->is_test == 0 && $course->is_question == 0 ? 0 : 1;

            $res['is_package_purchased'] = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1', 'order_tbl.user_id' => $val->user_id, 'order_detail.particular_record_id' => $val->course_id])->where('order_detail.expiry_date', '>', date('Y-m-d H:m:s'))->count() > 0 ? 1 : 0;

            $res['course_exam_date'] = CourseExamDate::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->first(['exam_date'])->exam_date ?? '';

            $res['course_detail'] = $course;

            $res['last_watched'] = WatchedTutorial::leftjoin('tutorial_tbl', 'tutorial_tbl.id', '=', 'watched_tutorial.tutorial_id')->leftjoin('category_tbl', 'category_tbl.id', '=', 'watched_tutorial.category_id')->orderBy('watched_tutorial.updated_at', 'desc')->where(['watched_tutorial.course_id' => $val->course_id, 'watched_tutorial.user_id' => $val->user_id])->first(['tutorial_tbl.id', 'tutorial_tbl.category_id', 'category_tbl.category_name', 'tutorial_tbl.chapter_name', 'tutorial_tbl.video_url', 'watched_tutorial.total_video_time', 'watched_tutorial.watched_time']);

            $res['total_tutorials'] = Tutorial::whereHas('courses', function ($query) use ($val) {
                $query->where('course_id', $val->course_id);
            })->where(['status' => 1])->count();

            $res['seen_tutorial'] = WatchedTutorial::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->count();

            $res['remaining_tutorial'] = $res['total_tutorials'] - $res['seen_tutorial'];

            $res['percentage_tutorial'] = $res['total_tutorials'] ? ($res['seen_tutorial'] / $res['total_tutorials']) * 100 : 0;

            $res['tutorial_category_data'] = Category::where('status', 1)->whereIn('id', $category_ids)->withCount([
                'tutorials' => function ($query) use ($val) {
                    $query->whereHas('course_tutorials', function ($query) use ($val) {
                        $query->where('course_id', $val->course_id);
                    });
                },
                'watched_tutorials' => function ($query) use ($val) {
                    $query->where(['user_id' => $val->user_id, 'course_id' => $val->course_id]);
                },
            ])->get();

            $res['my_total_attempt'] = AttemptQuestion::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->get()->count();

            $res['total_questions'] = QuestionAnswer::whereHas('courses', function ($query) use ($val) {
                $query->where('course_id', $val->course_id);
            })->whereIn("category_id", $category_ids)->get()->count();

            $res['your_total_average_score'] = AttemptQuestion::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->avg('is_correct');

            $res['your_percentile'] = $users_count ? round(($less_than_average / $users_count) * 100) : 0;

            $res['question_category_data'] = Category::where('status', 1)->whereIn('id', $category_ids)->withCount([
                'attempted_questions' => function ($query) use ($val) {
                    $query->where('course_id', $val->course_id);
                },
                'correct_attempted_questions' => function ($query) use ($val) {
                    $query->where('course_id', $val->course_id);
                },
                'attempted_questions_by_user' => function ($query) use ($val) {
                    $query->where(['user_id' => $val->user_id, 'course_id' => $val->course_id]);
                },
                'correct_attempted_questions_by_user' => function ($query) use ($val) {
                    $query->where(['user_id' => $val->user_id, 'course_id' => $val->course_id]);
                },
            ])->with('sub_categories', function ($query) use ($val) {
                $query->withCount([
                    'attempted_questions' => function ($query) use ($val) {
                        $query->where('course_id', $val->course_id);
                    },
                    'correct_attempted_questions' => function ($query) use ($val) {
                        $query->where('course_id', $val->course_id);
                    },
                    'attempted_questions_by_user' => function ($query) use ($val) {
                        $query->where(['user_id' => $val->user_id, 'course_id' => $val->course_id]);
                    },
                    'correct_attempted_questions_by_user' => function ($query) use ($val) {
                        $query->where(['user_id' => $val->user_id, 'course_id' => $val->course_id]);
                    },
                ]);
            })->get();

            $res['user_data'] = User::find($val->user_id);

            $res['total_attempted_questions'] = AttemptQuestion::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->get()->count();

            $res['total_attempted_questions_by_month'] = AttemptQuestion::where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->get()->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('m');
            })->map(function ($item) {
                return $item->count();
            });


            // Mocktest Data

            $res['total_mock_questions_attempted'] = AttemptMocktestQuestion::where(['user_id' => $val->user_id])->get()->count();

            $res['total_correct_mock_questions'] = AttemptMocktestQuestion::where(['user_id' => $val->user_id, 'is_correct' => 1])->get()->count();

            $mockTests = AttemptMocktestQuestion::leftJoin('mocktests', 'attempt_mocktest_questions.mocktest_id', '=', 'mocktests.id')->select('mocktests.id', 'mocktests.name')->where(['user_id' => $val->user_id, 'course_id' => $val->course_id])->groupBy('mocktest_id')->get();

            $mockTestIds = $mockTests->pluck('id');

            $mockTestResults = [];

            // fetch the mock test data from the ids
            foreach ($mockTestIds as $index => $mockTestId) {
                // get the category ids of the mock test
                $mockTestCategoryIds = MocktestCategory::where("mocktest_id", $mockTestId)->pluck("category_id");

                // get the category data
                $mockTestCategories = Category::whereIn('id', $mockTestCategoryIds)->orderBy('sort', 'asc')->get();

                foreach ($mockTestCategories as $category) {
                    $totalQuestionsAttempted = AttemptMocktestQuestion::where(['user_id' => $val->user_id, 'mocktest_id' => $mockTestId, 'category_id' => $category->id])->get()->count();

                    if ($totalQuestionsAttempted == 0) {
                        continue;
                    }

                    // find the total questions in each category
                    $totalQuestionsInMockCategory = AssingQuestionMocktest::where(["category_id" => $category->id, "mocktest_id" => $mockTestId])->get()->count();

                    $totalQuestionsCorrect = AttemptMocktestQuestion::where(['user_id' => $val->user_id, 'mocktest_id' => $mockTestId, 'category_id' => $category->id, 'is_correct' => '1'])->get()->count();

                    $percentageScore = round(($totalQuestionsCorrect / $totalQuestionsInMockCategory) * 100);

                    $ucatScore = CategoryUcatScore::where("category_id", $category->id)->where("min_score", '<=', $percentageScore)->where("max_score", '>=', $percentageScore)->where("course_type_id", $course->course_type_id)->first();

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

            $res['mock_test_results'] = $mockTestResults;

            $data[$key] = $res;
        }

        $data = array_values($data);

        $req_data = [
            "status" => true,
            "code" => "101",
            "data" => $data,
            "message" => "Preview data",
        ];

        return response()->json($req_data);
    }

    public function createUserPerformanceReport(Request $request)
    {
        $courseId = $request->courseId;
        $userId = auth()->user()->id;

        $insertData = array();
        $i = 0;
        $rand = rand(000000, 999999);

        $insertData[$i]['course_id'] = $courseId;
        $insertData[$i]['user_id'] = $userId;
        $insertData[$i]['uniqueId'] = $rand;
        $i++;

        TempPerformance::insert($insertData);

        return response()->json(['statusCode' => 200, 'message' => 'Created Temp Performance Successfully', 'data' => array("reportId" => $rand)], 200);
    }
}
