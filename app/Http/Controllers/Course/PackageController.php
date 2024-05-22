<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\PackageQuestion;
use App\Models\PackageTutorial;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Course;
use App\Models\SubCategory;
use yajra\Datatables\Datatables;
use App\Models\QuestionAnswerList;
use App\Models\Tutorial;
use App\Models\Category;
use App\Models\Seminar;
use App\Models\FlashCard;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use App\Models\AssignQuestion;
use App\Models\AdminTag;
use App\Models\AssignTutorial;
use App\Models\CourseType;
use App\Models\PackageMultiple;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $course = Course::all();
        $CourseType = CourseType::all();

        return view('admin.package.index', [
            "type" => $type,
            'course' => $course,
            'CourseType' => $CourseType
        ]);
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        Package::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Package status updated.']);
    }

    public function call_data(Request $request)
    {
        $type = $request->type == "book" ? 4 : ($request->type == "seminar" ? 2 : ($request->type == "flashcard" ? 3 : 1));

        $get_data = Package::where("package_for", $type);
        if ($type == 1) {
            $get_data = $get_data->whereHas('course');
        }
        if ($request->course) {
            $get_data = $get_data->where("perticular_record_id", $request->course)->where("package_for", 1);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_id", function ($get_data) {
                return $get_data->perticular_record_id ?? 'N/A';
            })
            ->editColumn("package_id", function ($get_data) {
                return $get_data->id ?? 'N/A';
            })
            ->editColumn("package_title", function ($get_data) {
                return $get_data->package_title ?? 'N/A';
            })
            ->editColumn("course_name", function ($get_data) {
                $course_id = ($get_data->package_for == '1') ? $get_data->perticular_record_id : '';
                $seminar_id = ($get_data->package_for == '2') ? $get_data->perticular_record_id : '';
                $flashcard_id = ($get_data->package_for == '3') ? $get_data->perticular_record_id : '';
                $book_id = ($get_data->package_for == '4') ? $get_data->perticular_record_id : '';
                if (!empty($course_id)) {
                    $getCat = Course::where('id', $course_id)->first(['id', 'course_name']);
                    return (@$get_data->id) ? @$getCat->course_name : "N/A";
                }
                if (!empty($book_id)) {
                    $getCat = Book::where('id', $book_id)->first(['id', 'title']);
                    return (@$get_data->id) ? @$getCat->title : "N/A";
                }
                if (!empty($flashcard_id)) {
                    $getCat = FlashCard::where('id', $flashcard_id)->first(['id', 'title']);
                    return (@$get_data->id) ? @$getCat->title : "N/A";
                }
                if (!empty($seminar_id)) {
                    $getCat = Seminar::where('id', $seminar_id)->first(['id', 'title']);
                    return (@$get_data->id) ? @$getCat->title : "N/A";
                }
            })
            ->editColumn("assign", function ($get_data) {
                $cr_form = '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_tutorial') . '/' . $get_data->id . '/package" >T</a>';

                $cr_form .= '<a class="btn btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('assign_question') . '/' . $get_data->id . '/package" >Q</a>';

                return $cr_form;
            })
            ->editColumn("status", function ($get_data) {
                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="package"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="package"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('package.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';


                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('package.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('package.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm" href="' . route('package.edit', $get_data->id) . '"><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';
                $cr_form .= '<button type="button" data-id="' . $get_data->id . '" class="btn btn-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';
                $cr_form .= '</form>';

                return $cr_form;
            })
            ->rawColumns(['course_name', 'assign', 'status', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function get_data(Request $request)
    {
        if ($request->record_id) {
            $get_data = Package::where('id', $request->record_id)->get()->toArray();
            return response()->json(['status' => 1, 'message' => 'Record Found.', 'result' => $get_data]);
        } else {
            return response()->json(['status' => 0, 'message' => 'No Record Found.', 'result' => array()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'package_title' => 'required',
            'course' => 'required',
            'price' => 'required',
            'package_description' => 'required',
        ]);

        $res_data = [];
        $package_for = 1;
        if ($request->package_for == "seminar") {
            $package_for = 2;
        }
        if ($request->package_for == "flashcard") {
            $package_for = 3;
        }
        if ($request->package_for == "book") {
            $package_for = 4;
        }

        $res_data['package_for'] = $package_for;
        $res_data['course_type_id'] = $request->course_type_id;
        $res_data['freecourse'] = $request->freecourse ? implode(",", $request->freecourse) : '';
        $res_data['perticular_record_id'] = $request->course;
        $res_data['expire_date'] = $request->expire_date;
        $res_data['package_title'] = $request->package_title;
        $res_data['package_for_month'] = $request->package_for_month;
        $res_data['price'] = $request->price;
        $res_data['description'] = $request->package_description;
        $res_data['packagetype'] = $request->packagetype;
        $res_data['status'] = $request->status;
        $multipledata = $this->updateAndSaveInputs();

        if (empty($multipledata)) {
            return back()->with('error', 'Package features is required');
        }
        if ($request->record_id) {
            $checkPack = Package::find($request->record_id);
            $type = $checkPack->package_for == "1" ? "course" : ($checkPack->package_for == "2" ? "seminar" : ($checkPack->package_for == "3" ? "flashcard" : 'book'));
            $res_data['package_for'] = $checkPack->package_for;
            Package::where('id', $request->record_id)->update($res_data);
            $this->storeMultiple($multipledata, $request->record_id);
            return redirect('/package?type=' . $type)->with('success', 'Package updated successfully');
        } else {
            $check_data = Package::where(['package_for' => 1, 'perticular_record_id' => $request->course, 'package_title' => $request->package_title])->first();

            if (isset($check_data->id)) {
                return back()->with('error', 'Package already exists');
            } else {

                $crr = Package::create($res_data);
                $this->storeMultiple($multipledata, $crr->id);
            }
            return redirect('/package?type=' . $request->package_for)->with('success', 'Package added successfully');
        }
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->back()->with('success', 'Package deleted successfully');
    }

    public function create()
    {
        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc')->get();
        $CourseType = CourseType::all();
        $getSeminar = Seminar::where('status', 'active')->orderBy('title', 'asc')->get();
        $getFlashCard = FlashCard::where('status', 'active')->orderBy('title', 'asc')->get();
        $getBook = Book::where('status', 'active')->orderBy('title', 'asc')->get();

        return view('admin.package.create', [
            'getCourse' => $getCourse,
            'getSeminar' => $getSeminar,
            'getFlashCard' => $getFlashCard,
            'getBook' => $getBook,
            'CourseType' => $CourseType
        ]);
    }

    public function show($id)
    {
        $getData = Package::find($id);

        if ($getData->package_for == 1) {
            $getCourse = Course::where('id', $getData->perticular_record_id)->first(['id', 'course_name']);
        }
        if ($getData->package_for == 2) {
            $getCourse = Seminar::where('id', $getData->perticular_record_id)->first(['id', 'title']);
        }
        if ($getData->package_for == 3) {
            $getCourse = FlashCard::where('id', $getData->perticular_record_id)->first(['id', 'title']);
        }
        if ($getData->package_for == 4) {
            $getCourse = Book::where('id', $getData->perticular_record_id)->first(['id', 'title']);
        }

        $question_idArr = [];
        $tutorial_idArr = [];
        if (isset($getData->assign_question_id)) {
            $question_idArr = $getData->questions()->pluck('id')->toArray();
        }

        if (isset($getData->assign_tutorial_id)) {
            $tutorial_idArr = $getData->tutorials()->pluck('id')->toArray();
        }

        $getQuestion = QuestionAnswerList::orderBy('id', 'desc')->whereIn('id', $question_idArr)->get();

        $getTutorial = Tutorial::orderBy('id', 'desc')->whereIn('id', $tutorial_idArr)->get();
        $multipledata = PackageMultiple::where('multi_pack_parent', $id)->get();
        $page_title = 'package';

        return view('admin.package.show', [
            'getData' => $getData,
            'page_title' => $page_title,
            'getCourse' => $getCourse,
            'getQuestion' => $getQuestion,
            'getTutorial' => $getTutorial,
            'multipledata' => $multipledata
        ]);
    }

    public function edit($id)
    {
        $getData = Package::find($id);

        $getCourse = Course::where('status', '1')->orderBy('course_name', 'asc');

        if ($getData->course_type_id > 0) {
            $getCourse = $getCourse->where("course_type_id", $getData->course_type_id);
        }
        $getCourse = $getCourse->get();

        $allCourse = Course::where('status', '1')->orderBy('course_name', 'asc')->get();


        $CourseType = CourseType::all();

        $getSeminar = Seminar::where('status', 'active')->orderBy('title', 'asc')->get();
        $getFlashCard = FlashCard::where('status', 'active')->orderBy('title', 'asc')->get();
        $getBook = Book::where('status', 'active')->orderBy('title', 'asc')->get();

        $question_idArr = [];
        $tutorial_idArr = [];

        if (isset($getData->assign_question_id)) {
            $question_idArr = $getData->questions()->pluck('id')->toArray();
        }

        if (isset($getData->assign_tutorial_id)) {
            $tutorial_idArr = $getData->tutorials()->pluck('id')->toArray();
        }

        $getQuestion = QuestionAnswerList::orderBy('id', 'desc')->where('course_id', $getData->perticular_record_id)->get();

        $getTutorial = Tutorial::orderBy('id', 'desc')->where('course_id', $getData->perticular_record_id)->get();
        $multipledata = PackageMultiple::where('multi_pack_parent', $id)->get();
        $page_title = 'package';

        return view('admin.package.edit', [
            'page_title' => $page_title,
            'getData' => $getData,
            'getCourse' => $getCourse,
            'getQuestion' => $getQuestion,
            'getTutorial' => $getTutorial,
            'question_idArr' => $question_idArr,
            'tutorial_idArr' => $tutorial_idArr,
            'multipledata' => $multipledata,
            'getSeminar' => $getSeminar,
            'getFlashCard' => $getFlashCard,
            'getBook' => $getBook,
            'CourseType' => $CourseType,
            'allCourse' => $allCourse
        ]);
    }

    public function update(Request $request, $id)
    {
    }
    public function updateAndSaveInputs()
    {
        $multi_pack_value = !empty($_POST['multi_pack_value']) ? $_POST['multi_pack_value'] : '';
        $multi_pack_status = !empty($_POST['multi_pack_status']) ? $_POST['multi_pack_status'] : '';
        $data = [];
        if (!empty($multi_pack_value)) {
            foreach ($multi_pack_value as $key => $val) {
                if (!empty($val) && !empty($multi_pack_status[$key])) {
                    $row = [];
                    $row['multi_pack_value'] = $val;
                    $row['multi_pack_status'] = $multi_pack_status[$key];
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    public function storeMultiple($data, $slug)
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $key => $val) {
            $data[$key]['multi_pack_parent'] = $slug;
        }
        if (!empty($data)) {
            PackageMultiple::where('multi_pack_parent', $slug)->delete();
            PackageMultiple::insert($data);
        }
        return true;
    }

    public function assing_question_index_course_wise(Request $request, $courseid)
    {
        $type = 'question';
        $getCategory = Category::get(['id', 'category_name']);
        $adminTag = AdminTag::get(['id', 'name']);

        return view('admin.question.assing_que_index_course_wise', [
            'courseid' => $courseid,
            'type' => $type,
            'getCategory' => $getCategory,
            'adminTag' => $adminTag
        ]);
    }


    public function call_question_list_data_course_wise(Request $request)
    {
        $course_id = $request->course_id;
        $record_id = $course_id;
        $page_type = $request->page_type;
        $course = Course::with('categories')->find($course_id);

        $categories = $course->categories()->pluck('id')->toArray();

        $get_dataQue = QuestionAnswerList::whereIn('category_id', $categories)->orderBy('id', 'desc');

        if ($request->type) {
            $get_dataQue = $get_dataQue->where("question_type", $request->type);
        }
        if ($request->category) {
            $get_dataQue = $get_dataQue->where("category_id", $request->category);
        }
        if ($request->subcategory) {
            // $get_dataQue = $get_dataQue->whereRaw('FIND_IN_SET("' . $request->subcategory . '",sub_category_ids)');
            $get_dataQue = $get_dataQue->subcategories()->where('sub_category_id', $request->subcategory);
        }
        if ($request->tag) {
            $get_dataQue = $get_dataQue->where("question_tags", $request->tag);
        }

        $totalData = $get_dataQue->count();
        $totalFiltered = $totalData;

        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_dataQue->with('courses')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->setOffset($start)
            ->addIndexColumn()
            ->editColumn("assign_checkbx", function ($get_data) use ($record_id, $page_type) {

                // $HiddenProducts = explode(',', $get_data->course_id);
                $HiddenProducts = $get_data->courses()->pluck('id')->toArray();

                $is_checked = "";

                if (in_array($record_id, $HiddenProducts)) {
                    $is_checked = "checked";
                }

                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control" ' . $is_checked . ' /> ';
                if ($is_checked == "checked") {
                    $string .= ' <a href="javascript:" class="btn btn-danger"  style="padding: 3px 5px 2px 5px;
                    margin-top: 10px;" onclick="unassignquestion(' . $get_data->id . ',' . $record_id . ',\'course\')">X</a>';
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

    public function call_tutorial_list_data_course_wise(Request $request)
    {
        $record_id = $request->course_id;
        $page_type = $request->page_type;
        $category = $request->category;

        $course = Course::find($record_id);

        $categories = $course->categories()->pluck('id')->toArray();
        $get_dataQue = Tutorial::whereIn('category_id', $categories)->orderBy('id', 'desc');
        if ($category) {
            $get_dataQue = $get_dataQue->where("category_id", $category);
        }
        $totalData = $get_dataQue->count();
        $totalFiltered = $totalData;

        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }

        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_dataQue->with('courses')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("assign_checkbx", function ($get_data) use ($record_id, $page_type) {

                $HiddenProducts = $get_data->courses()->pluck('id')->toArray();

                $is_checked = "";

                if (in_array($record_id, $HiddenProducts)) {
                    $is_checked = "checked";
                }

                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control" ' . $is_checked . ' /> ';
                if ($is_checked == "checked") {
                    $string .= ' <a href="javascript:" class="btn btn-danger"  style="padding: 3px 5px 2px 5px;
                    margin-top: 10px;" onclick="unassignquestion(' . $get_data->id . ',' . $record_id . ',\'tutorail\')">X</a>';
                }
                return $string;
            })
            ->editColumn("category_name", function ($get_data) {
                return (@$get_data->category_id) ? @$get_data->category_detail->category_name : "";
            })
            ->editColumn("chapter_name", function ($get_data) {
                return ($get_data->chapter_name) ?? "";
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {
                $cr_form = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('tutorial.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';
                return $cr_form;
            })->rawColumns(['assign_checkbx', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function question_common_assign_submit_course_wise(Request $request)
    {
        try {
            $courseId = $request->course_id;
            $postData  = $request->assign_que_id;
            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Question');
            }

            $query = \App\Models\QuestionAnswer::whereIn('id', $postData)->pluck('id')->toArray();;
            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Question');
            }

            foreach ($postData as $key => $ids) {
                $questionAnswer = \App\Models\QuestionAnswer::find($ids);
                if ($questionAnswer) {
                    $questionAnswer->courses()->sync($courseId);
                }
            }

            return redirect('/course')->with('success', 'Question Assigned to course');
        } catch (\Exception $e) {
            return back()->with('error', 'Something Went Wrong!');
        }
    }

    public function assing_tutorial_index_course_wise(Request $request, $courseid)
    {
        $type = 'tutorial';
        $allCourse = Course::all();
        $getCourse = Course::where('id', $courseid)->with('categories')->first();

        $getCategory = $getCourse->categories()->get();

        return view('admin.tutorial.assing_tuts_index_course_wise', [
            'courseid' => $courseid,
            'type' => $type,
            'allCourse' => $allCourse,
            'getCategory' => $getCategory
        ]);
    }

    public function tutorial_common_assign_submit_course_wise(Request $request)
    {
        try {
            $courseId = $request->course_id;
            $postData  = $request->assign_que_id;
            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Tutorial');
            }

            $query = \App\Models\Tutorial::whereIn('id', $postData)->pluck('id')->toArray();;
            if (empty($postData)) {
                return back()->with('error', 'Select Minimum One Tutorial');
            }

            foreach ($postData as $key => $ids) {
                $tutorial = Tutorial::find($ids);

                if ($tutorial) {
                    $tutorial->courses()->sync($courseId);
                }
            }

            return redirect('/course')->with('success', 'Tutorial Assigned to course');
        } catch (\Exception $e) {
            return back()->with('error', 'Something Went Wrong!');
        }
    }
    public function updateautoassign(Request $request)
    {
        $packageId = $request->packageId;
        $status = $request->status == "true" ? 1 : 0;
        echo $status;
        $package = Package::find($packageId);
        $package->is_auto_assign = $status;
        $package->save();
    }
    public function updateautoassigntutorial(Request $request)
    {
        $packageId = $request->packageId;
        $status = $request->status == "true" ? 1 : 0;
        echo $status;
        $package = Package::find($packageId);
        $package->is_auto_assign_tutorial = $status;
        $package->save();
    }

    public function assing_que_index(Request $request, $record_id = "", $page_type = "")
    {
        $getCategory = Category::get(['id', 'category_name']);
        Log::info('getCat' . $getCategory);
        $adminTag = AdminTag::get(['id', 'name']);
        $isChecked = '';
        if ($page_type == "package") {
            $packageDetail = Package::find($record_id);
            if ($packageDetail->is_auto_assign == 1) {
                $isChecked = "checked";
            }
        }

        return view('admin.question.assing_que_index', [
            'record_id' => $record_id,
            'page_type' => $page_type,
            'getCategory' => $getCategory,
            'adminTag' => $adminTag,
            'isChecked' => $isChecked
        ]);
    }
    public function assing_tutorial_index(Request $request, $record_id = "", $page_type = "")
    {
        if ($page_type == "test") {
            $getCourse = Course::where('id', $record_id)->with('categories')->first();
        } else {
            $getPack = Package::where('id', $record_id)->first(['perticular_record_id']);
            $course_id = $getPack->perticular_record_id ?? '';
            $getCourse = Course::where('id', $course_id)->with('categories')->first();
        }

        $isChecked = '';
        if ($page_type == "package") {
            $packageDetail = Package::find($record_id);
            if ($packageDetail->is_auto_assign_tutorial == 1) {
                $isChecked = "checked";
            }
        }
        return view('admin.tutorial.assing_tutorial_index', [
            'record_id' => $record_id,
            'page_type' => $page_type,
            'getCategory' => $getCourse->categories()->get(),
            'isChecked' => $isChecked
        ]);
    }
    public function question_common_assign_submit(Request $request)
    {
        $request->validate([
            'page_type' => 'required',
            'record_id' => 'required|integer',
            'assign_que_id' => 'required|array',
        ], [
            'assign_que_id.required' => 'Question Id is required',
            'assign_que_id.array' => 'Question Id should be array',
        ]);
        $assign_type = $request->assign_type;
        $page_type = $request->page_type;
        $assing_queArr = $request->assign_que_id;
        $assing_queArr = array_keys($assing_queArr);;

        if ($assign_type == "question") {
            if ($page_type == "package") {
                //$package = Package::findOrFail($request->record_id);
                //$package->questions()->sync($assing_queArr);
                //Log::info($request->all());
                $newPackageQuestions = [];
                if(!empty($assing_queArr)){
                    if($request->category_id != null){
                        PackageQuestion::where('package_id', $request->record_id)
                            ->where('category_id', $request->category_id)
                            ->whereIn('question_id', $assing_queArr)
                            ->delete();
                        // Insert new records
                        foreach ($assing_queArr as $question) {
                            $questionExist = PackageQuestion::where('package_id', $request->record_id)->where('question_id', $question)->first();
                            if(!isset($questionExist)) {
                                $newPackageQuestions[] = [
                                    //'category_id' => $request->category_id,
                                    'package_id' => $request->record_id,
                                    'question_id' => $question
                                ];
                            }
                        }
                    }else{
                        foreach ($assing_queArr as $question) {
                            $questionExist = PackageQuestion::where('package_id', $request->record_id)->where('question_id', $question)->first();
                            if(!isset($questionExist)){
                                $newPackageQuestions[] = [
                                    'package_id' => $request->record_id,
                                    'question_id' => $question
                                ];
                            }
                        }
                    }
                }
                if(!empty($newPackageQuestions)){
                    // Bulk insert the new records
                    PackageQuestion::insert($newPackageQuestions);
                }
                return redirect()->back()->with('success', 'Question assigned successfully');
            } else if ($page_type == "test") {
                foreach ($assing_queArr as $keyId => $kVal) {
                    $check = AssignQuestion::where('course_id', $request->record_id)->where("question_id", $kVal)->first();
                    if (empty($check)) {
                        $res_data = [
                            'course_id' => $request->record_id,
                            'question_id' => $kVal,
                        ];
                        AssignQuestion::create($res_data);
                    }
                }

                return redirect()->route('course.index')->with('success', 'Question added to test');
            }
        }
        if ($assign_type == "tutorial") {
            if ($page_type == "package") {
//                Package::where('id', $request->record_id)->first()->tutorials()->sync($assing_queArr);
                $package = Package::find($request->record_id);
                $saveAssignIds = explode(',', $package->assign_tutorial_id);
                $differenceArray = array_diff($assing_queArr, $saveAssignIds);
                Log::info('$assing_queArr' . json_encode($assing_queArr));
                Log::info('$saveAssignIds' . json_encode($saveAssignIds));
                if(!empty($differenceArray)) {
                    $assingQueArrString = implode(',', $differenceArray);
                    $assingQueArrString .= $package->assign_tutorial_id;
                    Log::info('$assingQueArrString' . json_encode($assingQueArrString));
                    $package->assign_tutorial_id = $assingQueArrString;
                    $package->save();
                }

                $existingTutorials = PackageTutorial::where('package_id', $request->record_id)
                                        ->pluck('tutorial_id')
                                        ->toArray();
                $differenceArray = array_diff($assing_queArr, $existingTutorials);
                if(!empty($differenceArray)){
                    foreach ($differenceArray as $tutorial_id) {
                        PackageTutorial::create([
                            'package_id' => $request->record_id,
                            'tutorial_id' => $tutorial_id
                        ]);
                    }
                }
                return redirect()->back()->with('success', 'Tutorial assigned successfully');
            } else if ($page_type == "test") {

                foreach ($assing_queArr as $keyId => $kVal) {
                    $res_data = [
                        'course_id' => $request->record_id,
                        'tutorial_id' => $kVal,
                    ];
                    AssignTutorial::create($res_data);
                }
                return redirect()->route('course.index')->with('success', 'Tutorial added to test mode');
            }
        }
    }

    public function call_question_list_data(Request $request)
    {
        $record_id = $request->record_id;
        $page_type = $request->page_type;

        $get_dataQue = QuestionAnswerList::orderBy('id', 'desc');
        if ($page_type == "test") {
            // $get_dataQue->with('courses')->whereHas('courses', function ($query) use ($record_id) {
            //     $query->where('course_id', $record_id);
            // });

        } else if ($page_type == "package") {
            // $getPack = Package::where('id', $record_id)->first(['perticular_record_id']);
            // $course_id = $getPack->perticular_record_id ?? '';
            // $get_dataQue->whereHas('courses', function ($query) use ($course_id) {
            //     $query->where('course_id', $course_id);
            // });
        }
        if ($request->type) {
            $get_dataQue = $get_dataQue->where("question_type", $request->type);
        }
        if ($request->category) {
            $get_dataQue = $get_dataQue->where("category_id", $request->category);
        }
        if ($request->subcategory) {
            $get_dataQue = $get_dataQue->subcategories()->where('sub_category_id', $request->subcategory);
        }
        if ($request->tag) {
            $get_dataQue = $get_dataQue->where("question_tags", $request->tag);
        }

        $totalData = $get_dataQue->count();
        $totalFiltered = $totalData;

        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_dataQue->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("assign_checkbx", function ($get_data) use ($record_id, $page_type) {

                $is_checked = "";
                $type = 'coursetest';
                if (isset($page_type)) {
                    if ($page_type == "package") {
                        $getPack = Package::where('id', $record_id)->with(['questions' => function($query) use($get_data) {
                            return $query->where('question_id', $get_data->id);
                        }])->first();
                        $is_checked = (isset($getPack->questions[0]->id)) ? 'checked' : '';
                        $type = 'packagequestion';
                    } else if ($page_type == "test") {

                        $check_test_mode = AssignQuestion::where(['course_id' => $record_id, 'question_id' => $get_data->id])->first(['id']);
                        $is_checked = (isset($check_test_mode->id)) ? 'checked' : '';
                    }
                }
                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control" ' . $is_checked . ' /> ';
                if ($is_checked == "checked") {
                    $string .= ' <a href="javascript:" class="btn btn-danger"  style="padding: 3px 5px 2px 5px;
                    margin-top: 10px;" onclick="unassignquestion(' . $get_data->id . ',' . $record_id . ',\'' . $type . '\')">X</a>';
                }
                return $string;
            })
            ->editColumn("question_id", function ($get_data) {
                return (@$get_data->id) ? @$get_data->id : "N/A";
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

    public function call_tutorial_list_data(Request $request)
    {
        $record_id = $request->record_id;
        $page_type = $request->page_type;
        $category = $request->category;

        $get_dataQue = Tutorial::orderBy('id', 'desc');
        if ($page_type == "test") {
            $get_dataQue->with(['courses' => function($query) use($request) {
                return $query->where('course_id', $request->record_id);
            }]);
        } else if ($page_type == "package") {
            $getPack = Package::where('id', $record_id)->first(['perticular_record_id']);
            $course_id = $getPack->perticular_record_id ?? '';
            $get_dataQue->with(['courses' => function($query) use($course_id) {
                return $query->where('course_id', $course_id);
            }]);
        }
        if ($category) {
            $get_dataQue = $get_dataQue->where("category_id", $category);
        }

        $totalData = $get_dataQue->count();
        $totalFiltered = $totalData;

        if ($request->get('length') >= 0) {
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        } else {
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_dataQue->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("assign_checkbx", function ($get_data) use ($record_id, $page_type) {

                $is_checked = "";
                $type = 'tutorailtest';
                if (isset($page_type)) {
                    if ($page_type == "package") {
                        $getPack = Package::where('id', $record_id)->with(['tutorials' => function($query) use($get_data) {
                            return $query->where('tutorial_id', $get_data->id);
                        }])->first();
                        $is_checked = (isset($getPack->tutorials[0]->id)) ? 'checked' : '';
                        $type = "packagetutorial";
                    } else if ($page_type == "test") {
                        $check_test_mode = AssignTutorial::where(['course_id' => $record_id, 'tutorial_id' => $get_data->id])->first(['id']);
                        $is_checked = (isset($check_test_mode->id)) ? 'checked' : '';
                    }
                }
                $string = '<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control" ' . $is_checked . ' /> ';
                if ($is_checked == "checked") {
                    $string .= ' <a href="javascript:" class="btn btn-danger"  style="padding: 3px 5px 2px 5px;
                    margin-top: 10px;" onclick="unassignquestion(' . $get_data->id . ',' . $record_id . ',\'' . $type . '\')">X</a>';
                }
                return $string;
            })
            ->editColumn("category_name", function ($get_data) {
                return (@$get_data->category_id) ? @$get_data->category_detail->category_name : "";
            })
            ->editColumn("chapter_name", function ($get_data) {
                return ($get_data->chapter_name) ?? "";
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {
                $cr_form = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . route('tutorial.show', $get_data->id) . '" ><i class="fa fa-eye"></i></a>';
                return $cr_form;
            })->rawColumns(['assign_checkbx', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }
}
