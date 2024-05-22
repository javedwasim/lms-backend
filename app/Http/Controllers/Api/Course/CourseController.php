<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseTypeResource;
use App\Models\AttemptQuestion;
use App\Models\Course;
use App\Models\OrderDetail;

class CourseController extends Controller
{
    public function index()
    {
        if (auth()->user()) {
            // TODO: check if implemented correctly
            $courses = Course::with([
                'course_type',
                'tutorials',
                'order_details' => function ($query) {
                    $query->whereHas('order', function ($query) {
                        $query->where('user_id', auth()->user()->id);
                    })->with('order');
                },
                'watched_tutorials' => function ($query) {
                    $query->where('user_id', auth()->user()->id)->with('tutorial', 'course');
                },
            ])->where('status', 1)->get();

            return CourseResource::collection($courses);
        } else {
            $courses = Course::with([
                'course_type',
                'tutorials',
            ])->where('status', 1)->get();

            return CourseTypeResource::collection($courses);
        }
    }

    public function show(Course $course)
    {
        $package_ids = OrderDetail::where('particular_record_id', $course->id)->whereHas(
            'order',
            function ($query) {
                $query->where('user_id', auth()->user()->id);
            }
        )->pluck('package_id');

        $course_id = $course->id;

        $course_detail = $course->load([
            'order_details' => function ($query) {
                $query->where('expiry_date', '>', date('Y-m-d H:m:s'))->where('package_for', '1')->whereHas('order', function ($query) {
                    $query->where('user_id', auth()->user()->id);
                })->with('order');
            },
            'watched_tutorials' => function ($query) {
                $query->where('user_id', auth()->user()->id)->with('category')->with('tutorial');
            },
            'exam_date' => function ($query) {
                $query->where('user_id', auth()->user()->id);
            },
            'questions_by_category',
            'tutorials_by_category' => function ($query) use($course_id) {
                $query->withCount([
                    'tutorials' => function ($query) use($course_id) {
                        $query->whereHas('courses', function($query) use($course_id){
                            $query->where('course_id', $course_id);
                        });
                    },
                    'watched_tutorials' => function ($query) use($course_id) {
                        $query->where(['user_id' => auth()->user()->id, 'course_id' => $course_id]);
                    },
                ]);
            },
            'tips',
            'supports',
        ])->loadCount([
            'attempted_questions',
            'correct_attempted_questions',
            'user_attempted_questions',
            'user_correct_attempted_questions',
        ]);

        $user_average = AttemptQuestion::where(['course_id'=> $course->id, 'user_id' => auth()->user()->id ])->avg('is_correct') ?? 0;
        $less_than_average = AttemptQuestion::where('course_id', $course->id)
            ->selectRaw('user_id, avg(is_correct) as average')
            ->having('average', '<', $user_average)->groupBy('user_id')->count();
        $users_count = AttemptQuestion::where('course_id', $course->id)->distinct()->count('user_id');

        $course_detail['percentile'] = $users_count ? round(($less_than_average / $users_count) * 100) : 0;

        $course_detail['watched_tutorial_count'] = $course_detail->watched_tutorials->count();

        return CourseDetailResource::collection([$course_detail]);
    }

    public function courseInstructions(Course $course)
    {
        return response()->json(['statusCode' => 200, 'message' => 'Comment list Successfully', 'data' => array("instruction" => $course->instruction)], 200);
    }
}
