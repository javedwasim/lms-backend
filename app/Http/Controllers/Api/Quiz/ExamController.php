<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseExamDate;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function courseExamdate(Request $request, Course $course)
    {
        $request->validate([
            'exam_date' => 'required',
        ]);

        $user_id = auth()->user()->id;
        $exam_date = $request->exam_date;

        $req_data = [];

        $is_exist = CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course->id])->count();

        if ($is_exist == 0) {
            $examDate = CourseExamDate::create([
                'user_id' => $user_id,
                'course_id' => $course->id,
                'exam_date' => $exam_date,
            ]);
            $req_message = "Exam Date Added";
        } else {
            CourseExamDate::where(['user_id' => $user_id, 'course_id' => $course->id])->update(['exam_date' => $exam_date]);
            $req_message = "Exam Date Updated";
        }

        response()->json([
            'status' => 'success',
            'message' => $req_message,
            'data' => $req_data,
        ]);
    }
}
