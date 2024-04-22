<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AttemptQuestion;
use App\Models\Course;
use App\Models\CourseExamDate;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProgressSetting;
use App\Models\WatchedTutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $request->validate([
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        $req_data = [];

        $progressArr = [];

        $user_id = auth()->user()->id;

        $courseDetail = $course;

        Log::info('courseDetail' .  json_encode($course));

        if ($courseDetail->is_tutorial == 1 && $courseDetail->is_question == 0) {
            $progressArr = $this->tutorailPrograssLogic($request, $courseDetail->id);
        } else {
            $progressArr = $this->questionPrograssLogic($request, $courseDetail->id);
        }
        $calenderColor = ProgressSetting::all();


        $req_data['progress_record'] = $progressArr;
        $req_data['calenderColor'] = $calenderColor;

        $userAllOrder = Order::where("user_id", $user_id)->pluck("id");
        $orderType = OrderDetail::where(['particular_record_id' => $course->id, 'package_for' => '1'])->whereIn("order_id", $userAllOrder)->first();

        if (!empty($orderType)) {
            $courseType = "paid";
        } else {
            $courseType = "unpaid";
        }
        $req_data['courseType'] = $courseType;

        if (count($progressArr)) {
            return response()->json([
                'status' => true,
                'code' => 200,
                'data' => $req_data,
                'message' => 'Record Found Successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'code' => 200,
                'data' => $req_data,
                'message' => 'No Record Found'
            ]);
        }
    }

    public function tutorailPrograssLogic($request, $course_id)
    {
        $user_id = auth()->user()->id;
        $start_date = strtotime($request->start_date);
        $end_date = strtotime($request->end_date);

        $getColor1 = ProgressSetting::where('id', '1')->first();
        $getColor2 = ProgressSetting::where('id', '2')->first();
        $getColor3 = ProgressSetting::where('id', '3')->first();

        $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
        $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';

        $watchedTutorials = WatchedTutorial::where(['user_id' => $user_id, 'course_id' => $course_id])
            ->whereBetween('created_at', [date('Y-m-d', $start_date), date('Y-m-d', $end_date)])
            ->selectRaw('DATE(created_at) as date, count(*) as total_attempt')
            ->groupBy('date')
            ->get();

        for ($i = $start_date; $i <= $end_date; $i = $i + 86400) {
            $our_date = date('Y-m-d', $i); // 2010-05-01, 2010-05-02, etc

            $totalQueAttenpt = 0;
            $dateInfo = $watchedTutorials->where('date', $our_date)->first();
            if ($dateInfo) {
                $totalQueAttenpt = $dateInfo->total_attempt;
            }

            $testDateStatus = false;
            if ($course_exam_date != "" && $course_exam_date == $our_date) {
                $color = "#C32F5B";
                // $testDateStatus = false;
            } else if ($totalQueAttenpt == 0) {
                $color = '#C0C0C0';
            } else if ($totalQueAttenpt >= 1 && $totalQueAttenpt <= 50) {
                $color = $getColor3->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 50 && $totalQueAttenpt <= 100) {
                $color = $getColor1->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 100) {
                $color = $getColor2->color;
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

    public function questionPrograssLogic($request, $course_id)
    {
        $user_id = auth()->user()->id;
        $start_date = strtotime($request->start_date);
        $end_date = strtotime($request->end_date);

        $getColor1 = ProgressSetting::where('id', '1')->first();
        $getColor2 = ProgressSetting::where('id', '2')->first();
        $getColor3 = ProgressSetting::where('id', '3')->first();

        $courseExam = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course_id])->first();
        $course_exam_date = (isset($courseExam->id)) ? $courseExam->exam_date : '';

        $attemptQuestions = AttemptQuestion::where(['user_id' => $user_id, 'course_id' => $course_id])
            ->whereBetween('created_at', [date('Y-m-d', $start_date), date('Y-m-d', $end_date)])
            ->selectRaw('DATE(created_at) as date, count(*) as total_attempt')
            ->groupBy('date')
            ->get();

        for ($i = $start_date; $i <= $end_date; $i = $i + 86400) {
            $our_date = date('Y-m-d', $i); // 2010-05-01, 2010-05-02, etc

            $totalQueAttenpt = 0;
            $dateInfo = $attemptQuestions->where('date', $our_date)->first();
            if ($dateInfo) {
                $totalQueAttenpt = $dateInfo->total_attempt;
            }

            $testDateStatus = false;
            if ($course_exam_date != "" && $course_exam_date == $our_date) {
                $color = "#C32F5B";
                $testDateStatus = true;
            } else if ($totalQueAttenpt == 0) {
                $color = '#C0C0C0';
            } else if ($totalQueAttenpt >= 1 && $totalQueAttenpt <= 50) {
                $color = $getColor3->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 50 && $totalQueAttenpt <= 100) {
                $color = $getColor1->color;
                $testDateStatus = true;
            } else if ($totalQueAttenpt > 100) {
                $color = $getColor2->color;
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
}
