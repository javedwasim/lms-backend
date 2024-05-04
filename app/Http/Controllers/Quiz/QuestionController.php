<?php

namespace App\Http\Controllers\Quiz;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AdminTag;
use yajra\Datatables\Datatables;
use App\Models\QuestionAnswer;
use App\Models\CourseType;
use App\Models\Course;
use App\Models\Package;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Tutorial;
use App\Models\Paragraph;
use App\Models\QueOption;
use App\Models\QueOptionAnswerType;
use App\Models\QuestionAnswerList;
use App\Models\Rating;
use App\Models\LikeUnlike;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Request $request, $course_id = "")
    {
        $getCourse = Course::where('id', $course_id)->first();
        $getCategory = Category::get(['id', 'category_name']);
        $adminTag = AdminTag::get(['id', 'name']);

        return view('admin.question.index', [
            'getCourse' => $getCourse,
            'course_id' => $course_id,
            'getCategory' => $getCategory,
            'adminTag' => $adminTag
        ]);
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        QuestionAnswer::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Question status updated.']);
    }

    public function call_data(Request $request)
    {
        $get_data = QuestionAnswerList::orderBy('id', 'desc')->where('status', '1');
        if ($request->question_tags) {
            $ques_tags = $request->question_tags;

            $tag_arr = explode(',', $ques_tags);
            foreach ($tag_arr as $tag_val) {
                $get_data = $get_data->whereRaw('FIND_IN_SET("' . $tag_val . '",question_tags)');
            }
        }
        if ($request->type) {
            $get_data = $get_data->where("question_type", $request->type);
        }
        if ($request->category) {
            $get_data = $get_data->where("category_id", $request->category);
        }
        if ($request->subcategory) {
            $get_data = $get_data->whereHas('subcategories', function ($query) use ($request) {
                $query->where('subcategory_id', $request->subcategory);
            });
        }
        if ($request->tag) {
            $get_data = $get_data->where("question_tags", $request->tag);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;
        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->offset($start)->limit($limit)->get();

        $appDatatable = Datatables::of($get_data)->setOffset($start)->addIndexColumn();
        $raw_column = ['question_name', 'status', 'action', 'rating', 'comment', 'feedback', 'assign_checkbx'];

        return $appDatatable
            ->editColumn("assign_checkbx", function ($get_data) {


                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control"  /> ';

                return $string;
            })
            ->editColumn("question_id", function ($get_data) {

                return (@$get_data->id) ? "Q ID: " . @$get_data->id : "N/A";
            })
            ->editColumn("category_name", function ($get_data) {
                $getCat = Category::where('id', $get_data->category_id)->first(['id', 'category_name']);
                return (@$get_data->category_id) ? @$getCat->category_name : "N/A";
            })
            ->editColumn("subcategory_name", function ($get_data) {
                $allSubCategory = explode(",", $get_data->sub_category_ids);
                $getCat = SubCategory::whereIn('id', $allSubCategory)->pluck('sub_category_name');
                return count(@$getCat) > 0 ? @implode(",", $getCat->toArray()) : "N/A";
            })
            ->editColumn("question_type_name", function ($get_data) {
                $truncated = '';
                if (!empty(trim($get_data->question_type_name))) {
                    $truncated = Str::limit(strip_tags($get_data->question_type_name), 20, '...');
                }

                return !empty($truncated) ? $truncated : "N/A";
            })
            ->editColumn("question_name", function ($get_data) {
                $truncated = '';
                if (!empty(trim($get_data->question_name))) {
                    $truncated = Str::limit(strip_tags($get_data->question_name), 30, '...');
                }

                return !empty($truncated) ? $truncated : "N/A";
            })
            ->editColumn("question_tags", function ($get_data) {
                return !empty($get_data->question_tags) ? $get_data->question_tags : "N/A";
            })

            ->editColumn("rating", function ($get_data) {

                $getCat = Rating::where('question_id', $get_data->id)->avg("rating");
                $getCat = $getCat ? round($getCat) : 0;
                $commentLink = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('rating_list') . '/' . $get_data->id . '" >' . $getCat . '</a>';

                return $commentLink;
            })

            ->editColumn("comment", function ($get_data) {
                $getCat = Comment::where('question_id', $get_data->id)->count();

                $commentLink = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('comment_list') . '/' . $get_data->id . '" >' . $getCat . '</a>';
                return $commentLink;
            })
            ->editColumn("feedback", function ($get_data) {
                $like = LikeUnlike::where('question_id', $get_data->id)->where("like_unlike_status", 1)->count();
                $unlike = LikeUnlike::where('question_id', $get_data->id)->where("like_unlike_status", 2)->count();

                $commentLink = '<span style="padding:0px 5px 0px 5px"><i class="fa fa-solid fa-thumbs-up" style="color:green"></i>   ' . $like . '</span><span style="padding:0px 5px 0px 5px"><i class="fa fa-solid fa-thumbs-down" style="color:red"></i> ' . $unlike . '</span>';
                return $commentLink;
            })
            ->editColumn("status", function ($get_data) {

                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="question"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="question"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('question.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('question.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm" href="' . route('question.edit', $get_data->id) . '"><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE">';
                $cr_form .= '<button type="button" data-id="' . $get_data->id . '" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';

                $cr_form .= '</form>';

                return $cr_form;
            })->rawColumns($raw_column)->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function question_drag_drop()
    {
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc')->get();
        $getCategory = Category::where('status', '1')->orderBy('category_name', 'asc')->get();

        return view('admin.question.create_drag_drop', compact('getCourse', 'getCategory'));
    }

    public function create()
    {
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc')->get();
        $CourseType = CourseType::all();

        $getCategory = Category::where('status', '1')->orderBy('category_name', 'asc')->get();
        $getSubCategory = SubCategory::where('status', '1')->orderBy('sub_category_name', 'asc')->get();
        $getParagraph = Paragraph::orderBy('paragraph', 'asc')->get();
        $adminTag = AdminTag::all();
        $courses = Course::where("status", 1)->get();
        return view('admin.question.create', compact('courses','getCourse', 'getCategory', 'getSubCategory', 'getParagraph', 'adminTag', 'CourseType'));
    }

    public function store(Request $request)
    {
        if (isset($request->question_type)) {
            if ($request->question_type == '1') {
                $this->validate($request, [
                    // 'course' => 'required|array',
                    'category' => 'required',
                    'paragraph' => 'required',
                    // 'question' => 'required',

                    'sub_category' => 'required',
                    'course_id' => 'required',
                    //  'option_a' => 'required',
                    //  'option_b' => 'required',
                    // 'option_c' => 'required',
                ]);
            } else {
                $this->validate($request, [
                    //  'course' => 'required',
                    'category' => 'required',
                    'course_id' => 'required',
                ]);
            }
        } else {
            $this->validate($request, [
                'question_type' => 'required',
                'course_id' => 'required',
            ]);
        }
        $input = $request->all();
        $res_data = [];
        $course = (isset($request->course)) ? implode(',', $request->course) : '';
        // $res_data['course_id'] = $course;
        $res_data['course_type_id'] = $request->course_type_id;
        $res_data['category_id'] = $request->category;
        $res_data['sub_category_ids'] = $request->sub_category ?? '';
        $res_data['paragraph'] = $request->paragraph ?? '';
        $res_data['tutorial_id'] = $request->tutorial ?? 0;
        $res_data['question_type'] = $request->question_type;
        $res_data['question_name'] = $request->question;
        $res_data['question_tags'] = $request->question_tags ?? '';
        $res_data['explanation'] = $request->explanation ?? '';
        $res_data['course_id'] = $request->course_id ?? '';

        $di = !empty($request->record_id) ? $request->record_id : 0;
        $findAlready = QuestionAnswer::find($di);
        $question_img = !empty($findAlready->question_img) ? $findAlready->question_img : '';
        $option_a_img = !empty($findAlready->option_a_img) ? $findAlready->option_a_img : '';
        $option_b_img = !empty($findAlready->option_b_img) ? $findAlready->option_b_img : '';
        $option_c_img = !empty($findAlready->option_c_img) ? $findAlready->option_c_img : '';
        $option_d_img = !empty($findAlready->option_d_img) ? $findAlready->option_d_img : '';
        $option_e_img = !empty($findAlready->option_e_img) ? $findAlready->option_e_img : '';
        $option_f_img = !empty($findAlready->option_f_img) ? $findAlready->option_f_img : '';
        $option_g_img = !empty($findAlready->option_g_img) ? $findAlready->option_g_img : '';
        $option_h_img = !empty($findAlready->option_h_img) ? $findAlready->option_h_img : '';
        $option_i_img = !empty($findAlready->option_i_img) ? $findAlready->option_i_img : '';
        $option_j_img = !empty($findAlready->option_j_img) ? $findAlready->option_j_img : '';
        $paragraph_img = !empty($findAlready->paragraph_img) ? $findAlready->paragraph_img : '';

        if ($request->hasFile('explanation_video')) {
            $media_img_path = $request->explanation_video->store('media', 's3');
            $res_data['explanation_video'] = Storage::disk('s3')->url($media_img_path);
        }

        if ($request->hasFile('question_img')) {
            $media_img_path = $request->question_img->store('question_images', 's3');
            $res_data['question_img'] = Storage::disk('s3')->url($media_img_path) ?? $question_img;
        }

        if ($request->hasFile('option_a_img')) {
            $media_img_path = $request->option_a_img->store('question_images', 's3');
            $res_data['option_a_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_a_img;
        }

        if ($request->hasFile('option_b_img')) {
            $media_img_path = $request->option_b_img->store('question_images', 's3');
            $res_data['option_b_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_b_img;
        }

        if ($request->hasFile('option_c_img')) {
            $media_img_path = $request->option_c_img->store('question_images', 's3');
            $res_data['option_c_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_c_img;
        }
        if ($request->hasFile('option_d_img')) {
            $media_img_path = $request->option_d_img->store('question_images', 's3');
            $res_data['option_d_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_d_img;
        }
        if ($request->hasFile('option_e_img')) {
            $media_img_path = $request->option_e_img->store('question_images', 's3');
            $res_data['option_e_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_e_img;
        }
        if ($request->hasFile('option_f_img')) {
            $media_img_path = $request->option_f_img->store('question_images', 's3');
            $res_data['option_f_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_f_img;
        }
        if ($request->hasFile('option_g_img')) {
            $media_img_path = $request->option_g_img->store('question_images', 's3');
            $res_data['option_g_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_g_img;
        }
        if ($request->hasFile('option_h_img')) {
            $media_img_path = $request->option_h_img->store('question_images', 's3');
            $res_data['option_h_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_h_img;
        }
        if ($request->hasFile('option_i_img')) {
            $media_img_path = $request->option_i_img->store('question_images', 's3');
            $res_data['option_i_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_i_img;
        }
        if ($request->hasFile('option_j_img')) {
            $media_img_path = $request->option_j_img->store('question_images', 's3');
            $res_data['option_j_img'] =  Storage::disk('s3')->url($media_img_path) ?? $option_j_img;
        }

        if ($request->hasFile('paragraph_img')) {
            $media_img_path = $request->paragraph_img->store('question_images', 's3');
            $res_data['paragraph_img'] =  Storage::disk('s3')->url($media_img_path) ?? $paragraph_img;
        }
        // question_img
        //option_a_img
        //  option_b_img
        // option_c_img
        // option_d_img
        // option_e_img
        // option_f_img
        if ($request->question_type == '1' || $request->question_type == '5') {
            $res_data['option_a'] = $request->option_a ?? '';
            $res_data['option_b'] = $request->option_b ?? '';
            $res_data['option_c'] = $request->option_c ?? '';
            $res_data['option_d'] = $request->option_d ?? '';
            $res_data['option_e'] = $request->option_e ?? '';
            $res_data['option_f'] = $request->option_f ?? '';
            $res_data['option_g'] = $request->option_g ?? '';
            $res_data['option_h'] = $request->option_h ?? '';
            $res_data['option_i'] = $request->option_i ?? '';
            $res_data['option_j'] = $request->option_j ?? '';
            $res_data['correct_answer'] = $request->correct_answer ?? '';
        }

        $res_data['status'] = $request->status ?? '1';

        // dd($request->option_attr);

        if ($request->record_id) {
            $crr = QuestionAnswer::where('id', $request->record_id)->update($res_data);
            // $crr->courses()->sync($request->course);
            $question_id = $request->record_id;
        } else {
            $crr = QuestionAnswer::create($res_data);
            // $crr->courses()->sync($request->course);
            $question_id = $crr->id;
            $this->autoassignQuestion($question_id, $request->category);
        }

        if ($request->question_type == '2' || $request->question_type == '3' || $request->question_type == '4') {
            if (is_array($request->option_attr)) {
                if (count($request->option_attr) > 0) {

                    foreach ($request->option_attr as $option_attr_name) {

                        $option_data2 = [
                            "question_id" => $question_id,
                            "answer_type_name" => $option_attr_name,
                        ];

                        $checkQueOpt = QueOptionAnswerType::where(["question_id" => $question_id, "answer_type_name" => $option_attr_name])->first();
                        if (isset($checkQueOpt->id)) {
                            QueOptionAnswerType::where('id', $checkQueOpt->id)->update($option_data2);
                        } else {
                            QueOptionAnswerType::create($option_data2);
                        }
                    }
                }
            }
        }

        if ($request->question_type == '2' || $request->question_type == '3'  || $request->question_type == '4') {
            if (count($request->option) > 0) {
                $option_ans = $request->option_answer;
                foreach ($request->option as $op_key => $options) {
                    $options_dt = $options;
                    $option_val = $option_ans[$op_key];

                    $getOptionValue = QueOptionAnswerType::where(["question_id" => $question_id, "answer_type_name" => $option_val])->first(['id']);
                    $option_data = [
                        "question_id" => $question_id,
                        "option_name" => $options_dt,
                        "option_value_id" => $getOptionValue->id ?? "",
                        "correct_option_answer" => $option_val,
                    ];
                    $checkQue = QueOption::where(["question_id" => $question_id, "option_name" => $options_dt])->first();
                    if (isset($checkQue->id)) {
                        QueOption::where('id', $checkQue->id)->update($option_data);
                    } else {
                        QueOption::create($option_data);
                    }
                }
            }
        }

        if ($request->record_id) {
            return redirect()->back()->with('success', 'Question updated successfully');
        } else {
            return redirect()->back()->with('success', 'Question created successfully');
        }
    }
    public function autoassignQuestion($questionId, $categoryId)
    {
        $getCategoryWiseCourse = Course::whereHas('categories', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })->pluck('id');

        // Get packages for the specific category, which are auto-assigned tutorials
        $packageDetail = Package::whereIn('perticular_record_id', $getCategoryWiseCourse)
            ->where('package_for', 1)
            ->where('is_auto_assign', 1)
            ->get();

        foreach ($packageDetail as $package) {
            // Update the assigned question IDs for the package
            $package->questions()->syncWithoutDetaching([$questionId]);
        }

        // Update the associated QuestionAnswer record
        $question = QuestionAnswer::find($questionId);
        $question->courses()->syncWithoutDetaching($getCategoryWiseCourse);
    }
    public function show($id)
    {
        $getData = QuestionAnswer::find($id);

        $queOption = QueOption::where('question_id', $id)->get();
        $option_answer_type = QueOptionAnswerType::where('question_id', $id)->get();

        $selectedCourseArr = (isset($getData->course_id)) ? explode(',', $getData->course_id) : [];

        $my_course_list = Course::whereIn('id', $selectedCourseArr)->pluck('course_name')->join(',');

        $page_title = 'question';
        return view('admin.question.show', compact('getData', 'page_title', 'queOption', 'option_answer_type', 'my_course_list'));
    }

    public function question_comments_list($id)
    {
        $getData = QuestionAnswer::find($id);
        $comments = \App\Models\Comment::where('question_id', $id)->get();
        $page_title = 'question';
        return view('admin.question.comments', compact('comments', 'page_title', 'getData'));
    }

    public function question_comments_reply(Request $request, $id)
    {
        $message = $request->message ?? '';
        $find = \App\Models\Comment::find($id);
        if (empty($find)) {
            return $this->json_view(false, [], 'Invalid Comment');
        }
        $find->admin_reply = $message;
        $find->save();
        return $this->json_view(true, [], 'Reply Successfully');
    }

    public function edit($id)
    {
        $getData = QuestionAnswer::find($id);

        $selectedCourseArr = (isset($getData->course_id)) ? explode(',', $getData->course_id) : [];
        $CourseType = CourseType::all();
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc');

        if ($getData->course_type_id > 0) {
            $getCourse = $getCourse->where("course_type_id", $getData->course_type_id);
        }
        $getCourse = $getCourse->get();

        $getCategory = Category::where(['status' => '1'])->orderBy('category_name', 'asc')->get();

        $getSubCategory = SubCategory::where(['category_id' => $getData->category_id, 'status' => '1'])->orderBy('sub_category_name', 'asc')->get();

        $getTutorial = Tutorial::where(['status' => '1'])->orderBy('chapter_name', 'asc')->get();

        $crr = QueOption::where('question_id', $id);
        $adminTag = AdminTag::all();
        // echo $crr->toSql(); die;
        $queOption = $crr->get();

        $option_answer_type = QueOptionAnswerType::where('question_id', $id)->get();

        $page_title = 'question';
        return view('admin.question.edit', compact('page_title', 'getData', 'getCourse', 'getCategory', 'getSubCategory', 'getTutorial', 'queOption', 'option_answer_type', 'selectedCourseArr', 'adminTag', 'CourseType'));
    }

    public function destroy($id)
    {
        QuestionAnswer::find($id)->delete();
        return redirect()->route('question.index')
            ->with('success', 'Question deleted successfully');
    }

    public function get_subcategory(Request $request)
    {
        // $request->validate([
        //     'category_id' => 'required|integer',
        // ]);

        $req_data = [];
        $res_data = SubCategory::orderBy('id', 'desc')->where('category_id', $request->category_id)->where('status', 1)->get()->toArray();

        if (!empty($res_data)) {
            $req_data = $res_data;
            $req_message = "Record Found";

            // return $this->json_view(true, $req_data, $req_message);
            return response()->json([
                "date" => $req_data,
                "message" => $req_message,
                "status" => true
            ]);
        } else {
            $req_message = "No Record Found";
            return response()->json([
                "date" => [],
                "message" => $req_message,
                "status" => false
            ]);
        }
    }

    public function get_ajax_course_question(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
        ]);

        $req_data = []; // (object)[];
        $res_question_data = QuestionAnswerList::orderBy('id', 'desc')->where(['course_id' => $request->course_id, 'status' => '1'])->get()->toArray();

        $res_tutorial_data = Tutorial::orderBy('id', 'desc')->where(['course_id' => $request->course_id, 'status' => 1])->get()->toArray();

        if (!empty($res_question_data)) {
            $req_data['question_list'] = $res_question_data;
            $req_data['tutorial_list'] = $res_tutorial_data;
            $req_message = "Record Found";

            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            $req_data['question_list'] = [];
            $req_data['tutorial_list'] = [];
            return $this->json_view(false, $req_data, $req_message);
        }
    }


    public function get_tutorial(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
            'category_id' => 'required|integer',
        ]);

        $req_data = []; // (object)[];
        $res_data = Tutorial::orderBy('id', 'desc')->where(['course_id' => $request->course_id, 'category_id' => $request->category_id])->where('status', 1)->get()->toArray();

        if (!empty($res_data)) {
            $req_data = $res_data;
            $req_message = "Record Found";

            return $this->json_view(true, $req_data, $req_message);
        } else {
            $req_message = "No Record Found";
            return $this->json_view(false, $req_data, $req_message);
        }
    }


    public function json_view($req_status = false, $req_data = "", $req_message = "")
    {
        $this['status'] = $req_status;
        $this['code'] = ($req_status == false) ? "404" : "101";
        $this['data'] = $req_data;
        $this['message'] = $req_message;

        return  response()->json($this);
    }

    public function comment_list(Request $request)
    {
        $questionId = $request->questionId;

        $commentlist = Comment::where('question_id', $questionId)->where("parent_id", 0)->get();
        return view('admin.question.comment_list', compact('commentlist'));
    }

    public function rating_list(Request $request)
    {
        $questionId = $request->questionId;
        $ratinglist = Rating::where('question_id', $questionId)->get();
        return view('admin.question.rating_list', compact('ratinglist'));
    }

    public function replyComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = Comment::where("id", $commentId)->first();
        $admincomment = Comment::where("parent_id", $commentId)->first();

        if ($request->isMethod('post')) {
            $commentNew = $request->comment;
            $status = $request->status;
            if (!empty($admincomment)) {
                $insert = Comment::find($admincomment->id);
            } else {
                $insert = new Comment();
            }

            $insert->user_id = auth()->user()->id;
            $insert->user_name = auth()->user()->name;
            $insert->question_id = $comment->question_id;
            $insert->comment = $commentNew;
            $insert->admin_reply = 1;
            $insert->is_name_display = 1;
            $insert->parent_id = $comment->id;
            $insert->status = $status;
            $insert->save();

            $comment->status = $status;
            $comment->save();

            return back()->with('success', 'comment added successfully');
        }

        return view('admin.question.replyComment', compact('comment', 'admincomment'));
    }

    public function editComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = Comment::where("id", $commentId)->first();

        if ($request->isMethod('post')) {
            $commentNew = $request->comment;

            $comment->comment = $commentNew;

            $comment->save();


            return back()->with('success', 'Comment updated successfully');
        }

        return view('admin.question.editComment', compact('comment'));
    }

    public function deleteComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = Comment::where("id", $commentId)->delete();


        return back()->with('success', 'Comment deleted successfully');
    }

    public function questiontaglist(Request $request)
    {
        $adminTag = AdminTag::get(['id', 'name']);
        return view('admin.question.questiontaglist', compact('adminTag'));
    }

    public function questiontagadd(Request $request)
    {
        if ($request->isMethod('post')) {
            $AdminTag = new AdminTag();
            $AdminTag->name = $request->name;
            $AdminTag->save();
            return back()->with('success', 'tag added successfully');
        }
        return view('admin.question.questiontagadd');
    }

    public function questiontagedit(Request $request)
    {
        $adminTag = AdminTag::find($request->id);
        if ($request->isMethod('post')) {
            $AdminTag = AdminTag::find($request->id);
            $AdminTag->name = $request->name;
            $AdminTag->save();
            return back()->with('success', 'tag updated successfully');
        }
        return view('admin.question.questiontagedit', compact('adminTag'));
    }

    public function questiontagdelete(Request $request)
    {
        $AdminTag = AdminTag::find($request->id)->delete();
        return back()->with('success', 'tag deleted successfully');
    }

    public function deletequestion(Request $request)
    {
        $qustionIds = $request->assign_que_id;
        $questionIdForDelete = array();
        foreach ($qustionIds as $key => $val) {
            $questionIdForDelete[] = $key;
        }
        QuestionAnswer::whereIn("id", $questionIdForDelete)->delete();
        return back()->with('success', 'question deleted successfully');
    }
}
