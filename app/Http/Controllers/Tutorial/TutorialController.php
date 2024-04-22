<?php

namespace App\Http\Controllers\Tutorial;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\Tutorial;
use App\Models\TutorialOrder;
use App\Models\TutorialFile;
use App\Models\TutorialSubfile;
use App\Models\VideoComment;
use App\Models\Package;
use Illuminate\Http\Request;
use yajra\Datatables\Datatables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class TutorialController extends Controller{
    public function index($course_id = "")
    {
        $getCourse = Course::where('id', $course_id)->first();
        $allCourse = Course::all();
        $getCategory = Category::get(['id', 'category_name']);

        return view('admin.tutorial.index', [
            'getCourse' => $getCourse,
            'course_id' => $course_id,
            'getCategory' => $getCategory,
            'allCourse' => $allCourse,
        ]);
    }
    
    public function getcoursecategory(Request $request)
    {
        $courseId = $request->course_id;
        $getCourse = Course::where('id', $courseId)->with('categories')->first();
        $getCategory = $getCourse->categories()->get(['id', 'category_name']);
        $str = '<option value="">Select</option> ';
        foreach ($getCategory as $val) {
            $str .= "<option value='" . $val->id . "'>" . $val->category_name . "</option>";
        }
        echo $str;

    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        Tutorial::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Tutorial status updated.']);
    }

    public function call_data(Request $request)
    {
        $get_data = Tutorial::query();

        if ($request->category) {
            $get_data = $get_data->where("category_id", $request->category);
        }if ($request->course_id) {
            // $get_data = $get_data->whereRaw('FIND_IN_SET("' . $request->course_id . '",course_id)');
            $get_data = $get_data->whereHas('courses', function ($query) use ($request) {
                $query->where('course_id', $request->course_id);
            });
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;
        if($request->get('length') >= 0){
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        }else{
            $limit = $totalData;
        }   
        // $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)

        ->editColumn("assign_checkbx",function($get_data) {

          
            $string='<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control"  /> ';
         
            return $string;
        })
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("category_name", function ($get_data) {
                return (@$get_data->category_id) ? @$get_data->category_detail->category_name : "";
            })
           /*  ->editColumn("order_tutorial", function ($get_data) {
                return (@$get_data->tutorialorder) ? @$get_data->tutorialorder : "0";
            }) */
            ->editColumn("chapter_name", function ($get_data) {

                $truncated = '';
                if (!empty(trim($get_data->chapter_name))) {
                    $truncated = $get_data->chapter_name;
                    // $truncated = Str::limit(strip_tags($get_data->chapter_name), 20, '...');
                }

                return ($truncated) ?? "";
            })
            ->editColumn("comment", function ($get_data) {
                $getCat = VideoComment::where('tutorial_id', $get_data->id)->count();

                $commentLink = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('tutorial_comment_list') . '/' . $get_data->id . '" >' . $getCat . '</a>';
                return $commentLink;
            })
            ->editColumn("status", function ($get_data) {

                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="tutorial"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="tutorial"  />
                        <span></span>
                    </div>';
                }

            })

            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('tutorial.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('tutorial.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';
                
                $cr_form .= '<a class="btn bg-gradient-success btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('tutorialfile/add/' . $get_data->id) . '" ><i class="fa fa-file"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm" href="' . route('tutorial.edit', $get_data->id) . '"><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';

                $cr_form .= '<button type="button" data-id="' . $get_data->id . '" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';

                $cr_form .= '</form>';

                return $cr_form;
            })->rawColumns(['phone_no', 'status', 'action', 'comment','assign_checkbx'])->with(['recordsTotal'=>$totalData, "recordsFiltered" => $totalFiltered,'start' => $start])->make(true);

    }

    public function create()
    {
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc')->get();
        $CourseType = CourseType::all();
        $getCategory = Category::where('status', '1')->orderBy('category_name', 'asc')->get();

        return view('admin.tutorial.create', [
            'getCourse' => $getCourse,
            'getCategory' => $getCategory,
            'CourseType' => $CourseType,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->record_id) {
            $this->validate($request, [
                'course' => 'required|array',
                'category' => 'required',
                'tutorial_name' => 'required',

            ]);
        } else {
            $this->validate($request, [
                'course' => 'required|array',
                'category' => 'required',
                'tutorial_name' => 'required',

            ]);
        }

        if (empty($request->record_id) && !$request->hasFile('pdf_url') && !$request->hasFile('video_url') && !$request->input('video_url')) {
            return back()->with('error', 'Please Select atleast one video or pdf');
        }

        $res_data = array(
            "category_id" => $request->category ?? '',
            "chapter_name" => $request->tutorial_name,
            "total_video_time" => $request->video_length,
            "video_type" => $request->videoType,
            "custom_code" => $request->custom_code ?? '',
            "trans_script" => $request->trans_script ?? '',
            "course_type_id" => $request->course_type_id ?? '',
            "pdf_heading" => $request->pdf_heading ?? '',
            "video_heading" => $request->video_heading ?? '',
            "video_pdf_order" => $request->video_pdf_order ?? '1',
            "tutorialorder" => $request->tutorialorder ?? '1',
            "status" => $request->status ?? '1',
        );

        // $course = (isset($request->course)) ? implode(',', $request->course) : '';
        // $res_data['course_id'] = $course;

        if ($request->hasFile('video_url')) {

            $video_image_path = $request->video_url->store('tutorial_video', 's3');
            // $res_data['video_url'] = $video_image_path;
            $res_data['video_url'] = Storage::disk('s3')->url($video_image_path);
        } else {
            $res_data['video_url'] = $request->video_url;
        }

        if ($request->hasFile('pdf_url')) {

            $video_image_path1 = $request->pdf_url->store('tutorial_video');
            $res_data['pdf_url'] = url("/public/uploads") . "/" . $video_image_path1;
        }

        if ($request->record_id) {
            $crr = Tutorial::where('id', $request->record_id)->first();
            if ($crr) {
                $crr->update($res_data);
                $crr->courses()->sync($request->course);
            }
            return redirect('/tutorial')->with('success', 'Tutorial updated successfully');
        } else {
            $crr=Tutorial::create($res_data);
            $crr->courses()->sync($request->course);
            $tutorialId = $crr->id;
            $this->autoassignTutorial($tutorialId, $request->category);
            return redirect('/tutorial')->with('success', 'Tutorial added successfully');
        }
    }

    public function show(Tutorial $tutorial)
    {
        $selectedCourseArr = (isset($tutorial->course_id)) ? explode(',', $tutorial->course_id) : [];

        $my_course_list = Course::whereIn('id', $selectedCourseArr)->pluck('course_name')->join(',');

        $page_title = 'tutorial';
        return view('admin.tutorial.show', [
            'page_title' => $page_title,
            'getData' => $tutorial,
            'my_course_list' => $my_course_list,
        ]);
    }

    public function edit(Tutorial $tutorial)
    {
        $selectedCourseArr = (isset($tutorial->course_id)) ? explode(',', $tutorial->course_id) : [];
        $CourseType = CourseType::all();
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc');
        if ($tutorial->course_type_id > 0) {
            $getCourse = $getCourse->where("course_type_id", $tutorial->course_type_id);
        }
        $getCourse = $getCourse->get();
        $getCategory = Category::where('status', '1')->orderBy('category_name', 'asc')->get();

        $page_title = 'tutorial';
        return view('admin.tutorial.edit', [
            'page_title' => $page_title,
            'getData' => $tutorial,
            'getCourse' => $getCourse,
            'getCategory' => $getCategory,
            'selectedCourseArr' => $selectedCourseArr,
            'CourseType' => $CourseType,
        ]);
    }

    public function destroy($id)
    {
        Tutorial::find($id)->delete();
        return redirect()->route('tutorial.index')
            ->with('success', 'Tutorial deleted successfully');
    }
    public function comment_list(Request $request)
    {
        $tutorialId = $request->tutorialId;

        $commentlist = VideoComment::where('tutorial_id', $tutorialId)->where("parent_id", 0)->get();

        return view('admin.tutorial.comment_list', [
            'commentlist' => $commentlist,
        ]);
    }
    public function replyComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = VideoComment::where("id", $commentId)->first();
        $admincomment = VideoComment::where("parent_id", $commentId)->first();

        if ($request->isMethod('post')) {
            $commentNew = $request->comment;
            $status = $request->status;
            if (!empty($admincomment)) {
                $insert = VideoComment::find($admincomment->id);
            } else {
                $insert = new VideoComment();
            }

            $insert->user_id = 

            $insert->tutorial_id = $comment->tutorial_id;
            $insert->comment = $commentNew;
            $insert->admin_reply = 1;

            $insert->parent_id = $comment->id;
            $insert->status = $status;
            $insert->save();

            $comment->status = $status;
            $comment->save();

            return back()->with('success', 'comment added successfully');
        }

        return view('admin.tutorial.replyComment', [
            'comment' => $comment,
            'admincomment' => $admincomment,
        ]);
    }
    public function editComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = VideoComment::where("id", $commentId)->first();

        if ($request->isMethod('post')) {
            $commentNew = $request->comment;

            $comment->comment = $commentNew;

            $comment->save();

            return back()->with('success', 'Comment updated successfully');
        }

        return view('admin.tutorial.editComment', [
            'comment' => $comment,
        ]);
    }
    public function deleteComment(Request $request)
    {
        $commentId = $request->commentId;
        $comment = VideoComment::where("id", $commentId)->delete();

        return back()->with('success', 'Comment deleted successfully');
    }
    public function tutorialfileadd(Request $request)
    {
        $tutorialId = $request->tutorialId;
        if ($request->isMethod('post')) {

            if (empty($request->id)) {
                $tutorialFile = new TutorialFile();
            } else {
                $tutorialFile = TutorialFile::find($request->id);
            }

            $tutorialFile->tutorial_id = $request->tutorialId;
            $tutorialFile->description = $request->description;
            $tutorialFile->status = 1;
            $tutorialFile->save();
            $titles = $request->title;
            $subfileId = $request->subfileId;
            $titles = array_filter($titles);
            if (!empty($subfileId)) {
                $subfileId = array_filter($subfileId);
            }

            foreach ($titles as $key => $val) {
                $type = $request->type[$key];
                $title = $request->title[$key];
                $position = $request->position[$key];
                $is_downloadable = $request->is_downloadable[$key];
                if ($type != "embed") {
                    $video_image_path1 = $request->images[$key]->store('tutorial_video');
                    $imageUrl = url("/public/uploads") . "/" . $video_image_path1;
                } else {
                    $imageUrl = $request->images_text[$key];
                }

                $subfile = new TutorialSubfile();
                $subfile->tutorial_id = $request->tutorialId;
                $subfile->tutorial_file_id = $tutorialFile->id;
                $subfile->title = $title;
                $subfile->imageurl = $imageUrl;
                $subfile->is_downloadable = $is_downloadable;
                $subfile->type = $type;
                $subfile->position = $position;
                $subfile->save();
            }
            if (!empty($subfileId)) {
                foreach ($subfileId as $key => $val) {

                    $title = $request->titleold[$key];
                    $type = $request->typeold[$key];
                    $position = $request->positionold[$key];
                    $is_downloadableold = $request->is_downloadableold[$key];
                    $subfile = TutorialSubfile::find($val);
                    $subfile->tutorial_id = $request->tutorialId;
                    $subfile->tutorial_file_id = $tutorialFile->id;
                    $subfile->title = $title;
                    $subfile->is_downloadable = $is_downloadableold;

                    if ($type != "embed") {
                        if (!empty($request->imagesold[$key])) {
                            $video_image_path1 = $request->imagesold[$key]->store('tutorial_video');
                            $imageUrl = url("/public/uploads") . "/" . $video_image_path1;
                            $subfile->imageurl = $imageUrl;
                        }

                    } else {
                        $imageUrl = $request->images_textold[$key];
                        $subfile->imageurl = $imageUrl;
                    }

                    $subfile->type = $type;
                    $subfile->position = $position;
                    $subfile->save();
                }
            }

            return back()->with('success', 'File Uploaded Successfully');
        }
        $tutorialFile = TutorialFile::where("tutorial_id", $tutorialId)->first();

        return view('admin.tutorial.filladd', [
            'tutorialFile' => $tutorialFile,
        ]);
    }

    public function deleteFileFromTutorial(Request $request)
    {
        $id = $request->id;
        TutorialSubfile::where("id", $id)->delete();
    }
    
    public function settutorialorder(Request $request)
    {
        // $courseAssignTutorial= Tutorial::orderBy('tutorialorder', 'asc')->where(['category_id' => $category_id, 'status' => 1])->whereRaw('FIND_IN_SET("' . $course_id . '",course_id)');
        $courseId=@$request->course_id ? $request->course_id : 0;
        $category_id=@$request->category_id ? $request->category_id : 0;
        $tutorialList=array();
        $ordersList=array();
        $isSearch=0;
        if($courseId && $courseId > 0)
        {
            // $tutorialList= Tutorial::orderBy('tutorialorder', 'asc')->where(['status' => 1])->whereRaw('FIND_IN_SET("' . $courseId . '",course_id)')->whereRaw('FIND_IN_SET("' . $category_id . '",category_id)')->get();

            $tutorialList = Tutorial::whereHas('courses', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })->where('category_id', $category_id)->where('status', 1)
            ->orderBy('tutorialorder', 'asc')->get();

            $tutorialOrders=TutorialOrder::where("course_id",$courseId)->get();
            foreach($tutorialOrders as $val)
            {
                $ordersList[$val->tutorial_id]=$val->tutorialorder;
            }
           
            $isSearch=1;
            $getCourse = Course::where('id', $courseId)->first();
            $categoryId = explode(",", $getCourse->categories);
            $category = Category::whereIn("id", $categoryId)->get();
        }
        else{
            $category=Category::where("status",1)->get();
        }
        $courses=Course::where("status",1)->get();
        
        return view('admin.tutorial.assignOrderTutorial', [
            'courses' => $courses,
            'courseId' => $courseId,
            'tutorialList' => $tutorialList,
            'isSearch' => $isSearch,
            'ordersList' => $ordersList,
            'category' => $category,
            'category_id' => $category_id,
        ]);
    }
    
    public function storeTutorialOrder(Request $request)
    {
        $courseId=$request->course_id;
        $category_id=$request->category_id;
        
        $tutorialOrders=$request->tutorialOrders;
        $insertData=array();
        // print_r($request->all()); exit;
        if(!empty($tutorialOrders))
        {
            foreach($tutorialOrders as $key=>$val){
                $insertData[]=array("course_id"=>$courseId,"tutorial_id"=>$key,"tutorialorder"=>$val,'category_id'=>$category_id);
            }
        }
        if(!empty($insertData))
        {
            TutorialOrder::where("course_id",$courseId)->where("category_id",$category_id)->delete();
           
            TutorialOrder::insert($insertData);
        }

    return back()->with('success', 'Order is Updated Successfully');

    }
    public function autoassignTutorial($tutorialId,$categoryId)
    {
        $getCategoryWiseCourse = Course::whereHas('categories', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })->pluck('id');
        
        $packageDetail = Package::whereIn('perticular_record_id', $getCategoryWiseCourse)
            ->where('package_for', 1)
            ->where('is_auto_assign_tutorial', 1)
            ->get();
        
        foreach ($packageDetail as $package) {
            $package->tutorials()->syncWithoutDetaching([$tutorialId]);
        }
        
        $tutorial = Tutorial::find($tutorialId);
        $tutorial->courses()->syncWithoutDetaching($getCategoryWiseCourse);
        
        $tutorial->refresh();
    }

    public function deletetutorial(Request $request)
    {
        $qustionIds=$request->assign_que_id;
        $questionIdForDelete=array();
        foreach($qustionIds as $key=>$val){
            $questionIdForDelete[]=$key;
        }
        Tutorial::whereIn("id",$questionIdForDelete)->delete();
        
        return back()->with('success', 'Tutorial deleted successfully');
       
    }
}