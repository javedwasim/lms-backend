<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Order;
use App\Models\Tutorial;
use App\Models\QuestionAnswer;
use App\Models\AssignQuestion;
use App\Models\Package;
use App\Models\CourseType;



use App\Models\BlockPopup;
use App\Models\Category;
use App\Models\CourseCategory;
use Auth;
use Illuminate\Support\Facades\Storage;
use Validator;
use yajra\Datatables\Datatables;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        return view('admin.course.index', [
            'courseType' => CourseType::all()
        ]);
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        Course::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Course status updated.']);
    }

    public function call_data(Request $request)
    {
        $get_data = Course::with('categories', 'course_type')->orderBy('id', 'desc');
        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_id", function ($get_data) {
                return ($get_data->id) ?? "N/A";
            })
            ->editColumn("courseType", function ($get_data) {
                return ($get_data->course_type->name) ?? "N/A";
            })
            ->editColumn("course_name", function ($get_data) {
                $truncated = '';
                if (!empty(trim($get_data->course_name))) {
                    $truncated = Str::limit(strip_tags($get_data->course_name), 20, '...');
                }

                return ($truncated) ?? "N/A";
            })
            ->editColumn("course_image", function ($get_data) {
                if ($get_data->course_image) {
                    return '<a href="' . url('uploads/' . $get_data->course_image) . '" target="_blank" >
                                <img src="' . url('uploads/' . $get_data->course_image) . '" style="width: 47px; margin-left:3px;" />
                            </a>';
                } else {
                    return "N/A";
                }
            })
            ->editColumn("enrolled_user", function ($get_data) {

                $buy_user_arr = Order::leftjoin('order_detail', 'order_detail.order_id', '=', 'order_tbl.id')->where(['order_detail.package_for' => '1'])->where('order_detail.particular_record_id', $get_data->id)->pluck('order_tbl.user_id')->toArray();

                $buy_users_ids = array_unique($buy_user_arr);

                return count($buy_users_ids);
            })
            ->editColumn("status", function ($get_data) {

                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="course"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="course"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("assign_course", function ($get_data) {
                $cr_form = '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_tutorial_course_wise') . '/' . $get_data->id . '" >T</a>';

                $cr_form .= '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_question_course_wise') . '/' . $get_data->id . '" >Q</a>';
                // Route::get('assign_question/{record_id?}', 'App\Http\Controllers\PackageController@assing_que_index_course_wise'); 
                // Route::get('assign_tutorial/{record_id?}', 'App\Http\Controllers\PackageController@assing_tutorial_index_course_wise'); 
                return $cr_form;
            })
            ->editColumn("assign", function ($get_data) {
                $cr_form = '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_tutorial') . '/' . $get_data->id . '/test" >T</a>';

                $cr_form .= '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_question') . '/' . $get_data->id . '/test" >Q</a>';

                return $cr_form;
            })

            ->editColumn("total_hours", function ($get_data) {
                return $get_data->total_hours ?? 'N/A';
            })
            ->editColumn("sort", function ($get_data) {
                return $get_data->sort ?? 'N/A';
            })
            ->editColumn("is_modal", function ($get_data) {
                return !empty($get_data->is_modal) ? 'Yes' : 'No';
            })

            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('course.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                /*$cr_form .= '<a class="btn btn-info btn-rounded btn-condensed btn-sm s_btn1 " href="'.route('course.show',$get_data->id).'" ><i class="fa fa-eye"></i></a>';*/

                /*$cr_form .= '<a href="#" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" data-id="'.$get_data->id.'" data-bs-toggle="modal" data-bs-target="#addBannerForm" title="Banner" ><i class="fa fa-flag"></i></a> ';*/

                $cr_form .= '<a class="btn btn-info btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('add_banner_popup') . '/' . $get_data->id . '" ><i class="fa fa-flag"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 view_in_modal"
                  data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#modal_view_dt" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-success btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('copy_course') . '/' . $get_data->id . '" title="copy course"
                   ><i class="fa fa-copy"></i></a>';

                $cr_form .= '<a class="btn bg-success btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('instruction') . '/' . $get_data->id . '" title="Instruction"
                   ><i class="fa fa-file"></i></a>';

                /*$cr_form .= '<a class="btn btn-default btn-rounded btn-condensed btn-sm" href="'.route('interest.edit',$get_data->id).'"><i class="fa fa-pencil"></i></a> ';*/

                $cr_form .= '<a href="#" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#addForm" ><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';

                $cr_form .= '<button type="button" data-id="' . $get_data->id . '" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';

                $cr_form .= '</form>';

                return $cr_form;
            })
            ->rawColumns(['assign', 'icon_image', 'status', 'action', 'assign_course'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function get_data(Request $request)
    {
        if ($request->record_id) {
            $get_data = Course::where('id', $request->record_id)->get()->toArray();
            $categoryIds = CourseCategory::where('course_id', $request->record_id)->pluck('category_id')->toArray();
            $category = Category::whereIn('id', $categoryIds)->get(['id', 'category_name', 'status']);

            return response()->json(['status' => 1, 'message' => 'Record Found.', 'result' => $get_data, 'category' => $category]);
        } else {
            return response()->json(['status' => 0, 'message' => 'No Record Found.', 'result' => array()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_name' => 'required',
            'total_hours' => 'required',
            'categories'  => 'required'

        ]);
        if ($request->record_id) {
            $res_data = Course::find($request->record_id);

            $res_data->course_name = $request->input('course_name');
            $res_data->status = $request->status ?? 1;
            $res_data->sort   = $request->sort;
            $res_data->course_type_id   = $request->course_type_id;
            $res_data->total_hours = $request->total_hours;
            // $res_data->categories = !empty($request->categories) ? implode(',', $request->categories) : $res_data->categories;
            $res_data->is_modal = $request->is_modal;
            $res_data->is_question = $request->is_question ?? 0;
            $res_data->is_tutorial = $request->is_tutorial ?? 0;
            $res_data->is_test = $request->is_test ?? 0;
            if ($request->hasFile('course_image')) {
                $course_image_url = $request->course_image->store('course_image', 's3');
                $res_data->course_image = $course_image_url;
            }

            if ($request->hasFile('video_image')) {
                $video_image_url = $request->video_image->store('video_image', 's3');
                $res_data->video_image = $video_image_url;
            }
            $res_data->save();

            $res_data->categories()->sync($request->categories);

            return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
        } else {
            $res_data = Course::where(['course_name' => $request->course_name])->first();
            if (isset($res_data->id)) {
                return response()->json(['status' => 2, 'message' => 'Record Already Exist.']);
            } else {
                $input = $request->all();
                $video_image = '';
                $course_image = '';

                if ($request->hasFile('course_image')) {
                    $interest_image_url = $request->course_image->store('course_image');
                    $course_image = $interest_image_url;
                }
                if ($request->hasFile('video_image')) {
                    $video_image_url = $request->video_image->store('video_image');
                    $video_image = $video_image_url;
                }
                $courseTypeId = $request->course_type_id;
                $row = new Course();
                $row->course_name = $request->input('course_name');
                $row->status = $request->status ?? 1;
                $row->sort   = $request->sort;
                $row->course_type_id   =   $courseTypeId;
                $row->total_hours = $request->total_hours;
                // $row->categories = !empty($request->categories) ? implode(',', $request->categories) : '';
                $row->is_modal = $request->is_modal ?? 0;
                $row->is_question = $request->is_question ?? 0;
                $row->is_tutorial = $request->is_tutorial ?? 0;
                $row->is_test = $request->is_test ?? 0;
                $row->video_image = $video_image;
                $row->course_image = $course_image;
                $row->save();

                $row->categories()->sync($request->categories);
            }
            return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);
        }
        return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);
    }

    public function add_banner_popup($record_id = "")
    {
        $getData  = Course::where('id', $record_id)->first();
        return view('admin.course.add_banner_popup', [
            'record_id' => $record_id,
            'getData' => $getData
        ]);
    }

    public function banner_store(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
        ]);

        if ($request->record_id) {
            $res_data = Course::find($request->record_id);

            BlockPopup::where(['course_id' => $request->record_id, 'type' => 1])->delete();
            BlockPopup::where(['course_id' => $request->record_id, 'type' => 2])->delete();

            if ($request->banner_content)
                $res_data->banner_content = $request->banner_content;

            if ($request->banner_link)
                $res_data->banner_link = $request->banner_link;

            if ($request->popup_link)
                $res_data->popup_link = $request->popup_link;

            if ($request->popup_content)
                $res_data->popup_content = $request->popup_content;

            if ($request->hasFile('popup_course_image')) {
                $popup_course_i = $request->popup_course_image->store('popup_course_image');
                $res_data->popup_course_image = $popup_course_i;
            }
            $res_data->save();

            return redirect()->route('course.index')->with('success', 'Banner/Popup added successfully');
        }
        return redirect()->route('course.index')->with('error', 'Submition failed');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('course.index')->with('success', 'Course deleted successfully');
    }

    public function create()
    {
    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
    }
    public function copy_course(Request $request)
    {
        $id = $request->id;
        $course = Course::with(['tutorials', 'questionAnswers', 'assignQuestions', 'packages'])->findOrFail($id);

        // Create a new course by duplicating attributes
        $newCourse = $course->replicate();
        $newCourse->course_name .= "-Copy";
        $newCourse->save();

        // Duplicate tutorials
        foreach ($course->tutorials as $tutorial) {
            $newTutorial = $tutorial->replicate();
            $newTutorial->chapter_name .= "-Copy";
            $newCourse->tutorials()->save($newTutorial);
        }

        // Duplicate question answers
        foreach ($course->questionAnswers as $questionAnswer) {
            $newQuestionAnswer = $questionAnswer->replicate();
            // Adjust relationships or other fields as needed
            $newCourse->questionAnswers()->save($newQuestionAnswer);
        }

        // Duplicate assign questions
        foreach ($course->assignQuestions as $assignQuestion) {
            $newAssignQuestion = $assignQuestion->replicate();
            // Adjust relationships or other fields as needed
            $newCourse->assignQuestions()->save($newAssignQuestion);
        }

        // Duplicate packages
        foreach ($course->packages as $package) {
            $newPackage = $package->replicate();
            // Adjust relationships or other fields as needed
            $newPackage->perticular_record_id = $newCourse->id;
            $newPackage->save();
        }

        return redirect()->route('course.index')->with('success', 'Duplicate course created successfully');
    }


    public function instruction(Request $request)
    {
        $id = $request->courseId;
        $course = Course::find($id);

        if ($request->isMethod('post')) {
            $allInput = $request->all();


            $courseType =  Course::find($request->courseId);
            $courseType->instruction = $request->description;

            $courseType->save();

            return redirect("/instruction/" . $id)->with('success', 'Course Type Updated Successfully');
        }

        return view('admin.course.instruction', [
            'course' => $course
        ]);
    }
}
