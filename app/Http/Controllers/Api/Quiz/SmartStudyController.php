<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\AttemptQuestion;
use App\Models\Course;
use App\Models\QuestionAnswer;
use App\Models\TempBeforeFinishTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmartStudyController extends Controller
{
    public function __invoke(Course $course)
    {
        $user_id = auth()->user()->id;

        $subCategoriesWithAverages = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course->id])
            ->select('sub_category_ids', DB::raw('avg(is_correct) as average'))
            ->groupBy('sub_category_ids')
            ->orderBy('average')
            ->limit(5)
            ->get();

        $subCategoryIds = $subCategoriesWithAverages->pluck('sub_category_ids');

        $questionIdsByCategory = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course->id])
            ->whereIn('sub_category_ids', $subCategoryIds)
            ->pluck('question_id');

        if (empty($questionIdsByCategory->toArray())) {
            $questionIdsByCategory = QuestionAnswer::where(['course_id' => $course->id])
                ->inRandomOrder()
                ->limit(20)
                ->pluck('id');
        }

        $questionIdsByCategoryStr = implode(',', $questionIdsByCategory->toArray());

        $existingTempTest = TempBeforeFinishTest::where(['user_id' => $user_id])->first();

        if (isset($existingTempTest->id)) {
            TempBeforeFinishTest::where(['user_id' => $user_id])->delete();
        }

        TempBeforeFinishTest::create([
            'user_id' => $user_id,
            'course_id' => $course->id,
            'questions_id' => $questionIdsByCategoryStr
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $questionIdsByCategory
        ]);
    }
}
