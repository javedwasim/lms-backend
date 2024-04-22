<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Course;
use yajra\Datatables\Datatables;
use App\Models\QuestionAnswerList;
use App\Models\QuestionAnswer;
use App\Models\AssignQuestion;
use App\Models\Tutorial;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Mocktest;
use App\Models\MocktestCategory;
use App\Models\AdminTag;
use Illuminate\Support\Str;
use App\Models\AssingQuestionMocktest;
use App\Models\AssignTutorial;
use App\Models\TempMocktestSrQuestion;
use Illuminate\Support\Facades\Storage;

class MocktestController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::where("status", 1)->get();
        return view('admin.mocktest.index', [
            'courses' => $courses
        ]);
    }
    public function call_data(Request $request)
    {
        $course_id = $request->course_id;
        $get_data = Mocktest::query();
        if (!empty($course_id)) {
            $get_data = $get_data->where("course_id", $course_id);
        }
        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();



        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_name", function ($get_data) {
                $truncated = '';
                if (@$get_data->course->course_name && !empty(trim($get_data->course->course_name))) {
                    $truncated = Str::limit(strip_tags($get_data->course->course_name), 20, '...');
                }

                return ($truncated) ?? "N/A";
            })
            ->editColumn("mocktest_name", function ($get_data) {
                $truncated = '';
                if (!empty(trim($get_data->name))) {
                    $truncated = Str::limit(strip_tags($get_data->name), 20, '...');
                }

                return ($truncated) ?? "N/A";
            })

            ->editColumn("image", function ($get_data) {
                if ($get_data->image) {
                    return '<a href="' .  $get_data->image . '" target="_blank" >
                                <img src="' . $get_data->image . '" style="width: 47px; margin-left:3px;" />
                            </a>';
                } else {
                    return "N/A";
                }
            })
            ->editColumn("total_hours", function ($get_data) {
                return $get_data->totaltime;
            })

            ->editColumn("assign_question", function ($get_data) {


                $cr_form = '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_question_mocktest_wise') . '/' . $get_data->id . '" >Q</a>';
                return $cr_form;
            })




            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '';

                $cr_form .= '<a href="' . url('/mocktest/edit/' . $get_data->id . '') . '" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" ><i class="fa fa-pencil"></i></a> ';
                $cr_form .= '<a href="' . url('/mocktest/delete/' . $get_data->id . '') . '" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm form_data_act" ><i class="fa fa-trash"></i></a> ';


                return $cr_form;
            })
            ->rawColumns(['assign', 'image', 'status', 'action', 'assign_question'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }
    public function add(Request $request)
    {
        $categories = Category::all();
        $courses = Course::where("status", 1)->get();
        if ($request->isMethod('post')) {

            $image = '';
            if (@$request->image) {
                $image =   $request->image->store('tutor');
                $image = url('/uploads/') . "/" . $image;
            }
            $mocktest = new Mocktest();
            $mocktest->name = $request->name;
            $mocktest->image = $image;
            $mocktest->sort = $request->sort;
            $mocktest->course_id = $request->course_id;
            $mocktest->save();

            $request['category_id'] = array_filter($request->category_id);

            if (!empty($request->category_id)) {
                $addonInsert = array();
                $totalTime = "00:00:00";
                foreach ($request->category_id as $key => $val) {

                    $addonInsert[$key]['mocktest_id'] = $mocktest->id;
                    $addonInsert[$key]['time'] = $request->time[$key];
                    $addonInsert[$key]['instruction'] = $request->instrucation[$key];
                    $addonInsert[$key]['category_id'] = $val;
                    $totalTime = $this->sum_the_time($totalTime, $request->time[$key]);
                }
                MocktestCategory::insert($addonInsert);
                $mocktest->totaltime = $totalTime;
                $mocktest->save();
            }
            return redirect()->back()->with('success', 'Mocktest Created Successfully');
        }
        return view('admin.mocktest.add', compact("categories", 'courses'));
    }
    public function sum_the_time($time1, $time2)
    {
        $times = array($time1, $time2);
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
    public function edit(Request $request)
    {
        $id = $request->id;
        $categories = Category::all();
        $courses = Course::where("status", 1)->get();
        if ($request->isMethod('post')) {
            $image = '';

            $mocktest =  Mocktest::find($id);
            $mocktest->name = $request->name;
            if (@$request->image) {
                $image =   $request->image->store('tutor', 's3');
                $image = Storage::disk('s3')->url($image);
                $mocktest->image = $image;

            }

            $mocktest->sort = $request->sort;
            $mocktest->course_id = $request->course_id;
            $mocktest->save();

            $request['category_id'] = array_filter($request->category_id);

            if (!empty($request->category_id)) {

                $addonInsert = array();
                $totalTime = "00:00:00";

                MocktestCategory::where("mocktest_id", $id)->delete();
                foreach ($request->category_id as $key => $val) {

                    $addonInsert[$key]['mocktest_id'] = $mocktest->id;
                    $addonInsert[$key]['time'] = $request->time[$key];
                    $addonInsert[$key]['instruction'] = $request->instrucation[$key];
                    $addonInsert[$key]['category_id'] = $val;
                    $totalTime = $this->sum_the_time($totalTime, $request->time[$key]);
                }

                MocktestCategory::insert($addonInsert);
                $mocktest->totaltime = $totalTime;
                $mocktest->save();
            }
            return redirect()->back()->with('success', 'Mocktest Updated Successfully');
        }
        $mocktest =  Mocktest::find($id);
        $mocktestcategory =  MocktestCategory::where("mocktest_id", $id)->get();
        return view('admin.mocktest.edit', compact("categories", 'mocktest', 'mocktestcategory', 'courses'));
    }
    public function delete(Request $request)
    {
        $id = $request->id;

        $mocktest =  Mocktest::where("id", $id)->delete();
        $mocktestcategory =  MocktestCategory::where("mocktest_id", $id)->delete();
        return redirect("mocktest/index")->with('success', 'Mocktest Deleted Successfully');
    }
    public function assign_question_mocktest_wise(Request $request)
    {
        $id = $request->id;
        $type = 'question';
        $getCategory = Category::get(['id', 'category_name']);
        $adminTag = AdminTag::get(['id', 'name']);
        // return view('admin.question.assing_que_index_course_wise', compact('courseid', 'type'));
        return view('admin.mocktest.assign_question_mocktest_wise', compact('id', 'getCategory', "adminTag"));
    }
    public function call_question_list_data_mocktest_wise(Request $request)
    {
        $mocktestId = $request->mocktestId;
        $record_id = $mocktestId;
        $start = $request->start;
        $tag = $request->tag;
        $length = $request->length;
        $page_type = $request->page_type;
        $categoryId = MocktestCategory::where("mocktest_id", $mocktestId)->pluck("category_id");

        $get_dataQue = QuestionAnswerList::query();

        if ($request->category) {
            $get_dataQue = $get_dataQue->whereRaw('FIND_IN_SET("' . $request->category . '",category_id)');
        }
        if ($request->subcategory) {
            $get_dataQue = $get_dataQue->whereRaw('FIND_IN_SET("' . $request->subcategory . '",sub_category_ids)');
        }
        if ($request->tag) {
            $get_dataQue = $get_dataQue->where("question_tags", $request->tag);
        }
        // if($page_type=="test"){
        //      $get_dataQue->whereRaw('FIND_IN_SET("'.$request->record_id.'",course_id)');
        // }else if($page_type=="package"){
        //     $getPack = Package::where('id',$record_id)->first(['perticular_record_id']);
        //     $course_id = $getPack->perticular_record_id ?? '';
        //     $get_dataQue->whereRaw('FIND_IN_SET("'.$course_id.'",course_id)');
        // }
        // $newQuery=$get_dataQue;
        // $count_total=555;
        $totalData = $get_dataQue->count();
        $totalFiltered = $totalData;

        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_dataQue->orderBy('id', 'desc')->offset($start)->limit($limit)->get();
        // $count_total= $newQuery->count();
        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("assign_checkbx", function ($get_data) use ($record_id, $page_type, $mocktestId) {

                $row = AssingQuestionMocktest::where("mocktest_id", $mocktestId)->where("question_id", $get_data->id)->first();


                $HiddenProducts = explode(',', $get_data->course_id);

                $is_checked = "";

                if (!empty($row)) {
                    $is_checked = "checked";
                }

                // if(isset($page_type)){ 
                //     if($page_type=="package"){ 
                //         $getPack = Package::where('id',$record_id)->first(['assign_question_id']);
                //         $assignCheckArr = explode(',',$getPack->assign_question_id); 
                //         $is_checked = (in_array($get_data->id,$assignCheckArr)) ? 'checked' : ''; 
                //     }else if($page_type=="test"){

                //         $check_test_mode = AssignQuestion::where(['course_id'=>$record_id,'question_id'=>$get_data->id])->first(['id']);
                //         $is_checked = (isset($check_test_mode->id)) ? 'checked' : '';

                //     }
                // }
                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control" ' . $is_checked . ' /> ';
                if ($is_checked == "checked") {
                    $string .= ' <a href="javascript:" class="btn btn-danger"  style="padding: 3px 5px 2px 5px;
                    margin-top: 10px;" onclick="unassignquestion(' . $get_data->id . ',' . $mocktestId . ',\'mocktest\')">X</a>';
                }
                return $string;
            })
            ->editColumn("category_name", function ($get_data) {
                $getCat = Category::where('id', $get_data->category_id)->first(['id', 'category_name']);
                return (@$get_data->category_id) ? @$getCat->category_name : "";
            })
            ->editColumn("subcategory_name", function ($get_data) {
                $allSubCategory = explode(",", $get_data->sub_category_ids);
                $getCat = SubCategory::whereIn('id', $allSubCategory)->pluck('sub_category_name');
                return count(@$getCat) > 0 ? @implode(",", $getCat->toArray()) : "N/A";
            })
            ->editColumn("tag", function ($get_data) {
                return ($get_data->question_tags) ?? "";
            })
            ->editColumn("question_type_name", function ($get_data) {
                return ($get_data->question_type_name) ?? "";
            })
            ->editColumn("question_name", function ($get_data) {
                return ($get_data->question_name) ?? "";
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('question.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';

                return $cr_form;
            })->rawColumns(['assign_checkbx', 'question_name', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }
    public function question_common_assign_submit_mocktest_wise(Request $request)
    {

        try {
            /* $lastSrNo= TempMocktestSrQuestion::where("mocktest_id",33)->where("user_id",203)->where("category_id",39)->orderBy('sr_no',"DESC")->first();
            print_r($lastSrNo); exit; */
            $mocktestId = $request->mocktestId;
            $postData  = $request->assign_que_id;

            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Question');
            }


            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Question');
            }

            foreach ($postData as $key => $ids) {
                $row = AssingQuestionMocktest::where("mocktest_id", $mocktestId)->where("question_id", $ids)->first();
                if (empty($row)) {
                    $get_dataQue = QuestionAnswerList::where('id', $ids)->first();
                    $mockAssig = new AssingQuestionMocktest();
                    $mockAssig->mocktest_id = $mocktestId;
                    $mockAssig->category_id = $get_dataQue->category_id;
                    $mockAssig->question_id = $ids;
                    $mockAssig->save();
                    // check any running mocktest
                    $check =  TempMocktestSrQuestion::where("mocktest_id", $mocktestId)->groupBy("user_id")->where("category_id", $get_dataQue->category_id)->get();
                    foreach ($check as $val) {
                        $lastSrNo = TempMocktestSrQuestion::where("mocktest_id", $mocktestId)->where("user_id", $val->user_id)->where("category_id", $get_dataQue->category_id)->orderBy('sr_no', "DESC")->first();
                        $newSrNo = $lastSrNo->sr_no + 1;
                        $insertData = array('sr_no' => $newSrNo, 'question_id' => $ids, 'user_id' => $val->user_id, 'is_practice' => $val->is_practice, "mocktest_id" => $val->mocktest_id, "category_id" => $val->category_id);
                        TempMocktestSrQuestion::insert($insertData);
                    }
                }
            }

            ///  return back()->with('success','Question Assigned to course');
            return redirect('/mocktest/index')->with('success', 'Question Assigned Successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Something Went Wrong!');
        }
    }
    public function getcategorywisecourse(Request $request)
    {
        $category_id = $request->categoryid;
        $courses = Course::whereHas('categories', function ($query) use ($category_id) {
            $query->where('categories.id', $category_id);
        })->where('status', 1)->get();
        
        $options = '<option value="">Select</option>';
        foreach ($courses as $course) {
            $options .= "<option value='{$course->id}'>{$course->course_name}</option>";
        }
        echo $options;
    }
    public function unassignquestion(Request $request)
    {
        $questionId = $request->questionId;
        $primaryId = $request->primaryId;
        $type = $request->type;
        if ($type == "mocktest") {

            AssingQuestionMocktest::where("question_id", $questionId)->where("mocktest_id", $primaryId)->delete();
            TempMocktestSrQuestion::where("question_id", $questionId)->where("mocktest_id", $primaryId)->delete();
            // reset order 
            $getRemain =  TempMocktestSrQuestion::where("question_id", $questionId)->where("mocktest_id", $primaryId)->orderBy("sr_no", "asc")->get();
            $i = 1;
            foreach ($getRemain as $val) {
                TempMocktestSrQuestion::where("id", $val->id)->update(['sr_no' => $i]);
                $i++;
            }
        }
        if ($type == "course") {
            $row = QuestionAnswer::find($questionId);
            $HiddenProducts = $row->courses()->pluck('id')->toArray();

            if ((array_search($primaryId, $HiddenProducts)) !== false) {
                $row->courses()->detach($primaryId);
            }
        }
        if ($type == "coursetest") {
            AssignQuestion::where("question_id", $questionId)->where("course_id", $primaryId)->delete();
        }
        if ($type == "tutorail") {
            $row = \App\Models\Tutorial::find($questionId);
            $HiddenProducts = $row->courses()->pluck('id')->toArray();

            if ((array_search($primaryId, $HiddenProducts)) !== false) {
                $row->courses()->detach($primaryId);
            }
        }
        if ($type == "tutorailtest") {
            AssignTutorial::where("tutorial_id", $questionId)->where("course_id", $primaryId)->delete();
        }
        if ($type == "packagequestion") {
            $row = Package::find($primaryId);
            $HiddenProducts = $row->questions()->pluck('id')->toArray();

            if ((array_search($questionId, $HiddenProducts)) !== false) {
                $row->questions()->detach($questionId);
            }
        }
        if ($type == "packagetutorial") {
            $row = Package::find($primaryId);
            $HiddenProducts = $row->tutorials()->pluck('id')->toArray();

            if ((array_search($questionId, $HiddenProducts)) !== false) {
                $row->tutorials()->detach($questionId);
            }
        }
    }

    public function unassignquestionAll(Request $request)
    {

        $primaryId = $request->primaryId;
        $type = $request->type;
        if ($type == "mocktest") {
            AssingQuestionMocktest::where("mocktest_id", $primaryId)->delete();
            TempMocktestSrQuestion::where("mocktest_id", $primaryId)->delete();
        }
        if ($type == "course") {
            $row = Course::find($primaryId);
            $row->questions()->detach();
        }
        if ($type == "coursetest") {
            AssignQuestion::where("course_id", $primaryId)->delete();
        }
        if ($type == "tutorail") {
            $row = Tutorial::find($primaryId);
            $row->courses()->detach();
        }
        if ($type == "tutorailtest") {
            AssignTutorial::where("course_id", $primaryId)->delete();
        }
        if ($type == "packagequestion") {

            $package = Package::find($primaryId);

            $package->assign_question_id  = '';

            $package->save();
        }
        if ($type == "packagetutorial") {
            $package = Package::find($primaryId);

            $package->tutorials()->detach();
        }
        return redirect()->back()->with('success', 'Unassign Successfully');
    }
}
