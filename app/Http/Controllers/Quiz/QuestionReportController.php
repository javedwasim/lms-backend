<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Reportissue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use yajra\Datatables\Datatables;

class QuestionReportController extends Controller
{
    public function index(Request $request)
    {
        $getCategory=Category::get(['id', 'category_name']);
        $getCourse=Course::get(['id', 'course_name']);

        return view('admin.question-report.index',compact('getCategory','getCourse'));
    }

    public function call_data(Request $request)
    {

        $get_data = Reportissue::with('userData')->whereHas("questionData");
        if($request->course)
        {   $courseId=$request->course;
            $get_data=$get_data->whereHas('questionData', function($q) use($courseId) {
                $q->whereHas('courses', function($q) use($courseId) {
                    $q->where('course_id', $courseId);
                });
            });
        }
        if($request->category)
        {   $category=$request->category;
            $get_data=$get_data->whereHas('questionData', function($q) use($category) {
                $q->where('category_id', $category);
            });
        }
        if($request->label)
        {   $label=$request->label;
            $get_data=$get_data->where('status', $label);
        }
        if($request->fromdate)
        {
            $get_data=$get_data->where('created_at', ">=",$request->fromdate);
        }
        if($request->todate)
        {
            $get_data=$get_data->where('created_at', "<=",$request->todate);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;
        if($request->get('length') >= 0){
            $limit = ($request->get('length')) ? $request->get('length') : 10;
        }else{
            $limit = $totalData;
        }
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data=$get_data->orderBy("updated_at","DESC")->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
        ->editColumn("assign_checkbx",function($get_data) {


            $string='<input type="checkbox" name="assign_que_id[' . $get_data->id . ']" class="assign_que_id" id="agn_que_id_' . $get_data->id . '" value="' . $get_data->id . '" data-id="' . $get_data->id . '" class="form-control"  /> ';

            return $string;
        })
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("questionid", function ($get_data) {
                return "Q ID: ".$get_data->question_id ?? 'N/A';
            })
            ->editColumn("user_name", function ($get_data) {
                return $get_data->userData->name ?? 'N/A';
            })
            ->editColumn("issue", function ($get_data) {
                $truncated = '';
                if (!empty(trim($get_data->description))) {
                    $truncated = Str::limit(strip_tags($get_data->description), 50, '...');
                }
                return [
                    'truncated' => !empty($truncated) ? $truncated : "N/A",
                    'full' => $get_data->description ?? 'N/A'
                ];
            })
            ->editColumn("type", function ($get_data) {
                $data=json_decode($get_data->options,true);

                $string='<ul>';
                foreach ($data as $val) {

                    $string.='<li>'.$val['value'].'</li>';
                }
                $string.='</ul>';

                return $string ?? 'N/A';
            })
            ->editColumn("course", function ($get_data) {

                return @$get_data->questionData->course->course_name;
            })
            ->editColumn("category", function ($get_data) {
                return @$get_data->questionData->category->category_name;
            })
            ->editColumn("date_reported", function ($get_data) {

                return $get_data->created_at;
            })
            ->editColumn("admin_notes", function ($get_data) {
                return $get_data->admin_note;
            })
            ->editColumn("label", function ($get_data) {
                return $get_data->status==0?"Pending":"Resolved";
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('question_report/') . '/reply/' . $get_data->id . '" > Reply</a>';
                $cr_form.= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="' . url('question/') .'/'.  $get_data->question_id . '/edit/" >Edit Question</a>';

                return $cr_form;
            })
            ->rawColumns(['action','type','assign_checkbx'])->with(['recordsTotal'=>$totalData, "recordsFiltered" => $totalFiltered,'start' => $start])->make(true);

    }
    public function show($id)
    {
        $getData = Order::find($id);
        $getDataDetail = OrderDetail::where("order_id", $id)->get();

        $page_title = 'package';
        return view('admin.tranaction.show', compact('getData', 'getDataDetail'));
    }
    public function reply(Request $request)
    {
        $reportId=$request->id;

        $comment = Reportissue::where("id", $reportId)->first();


        if ($request->isMethod('post')) {
            $commentNew = $request->comment;
            $status = $request->status;

            $comment->admin_note = $commentNew;
            $comment->status = $status;

            $comment->save();

            return back()->with('success', 'Updated Successfully');
        }
        return view('admin.question-report.reply', compact('comment'));
    }
    public function deletequestionreport(Request $request)
    {
        $qustionIds=$request->assign_que_id;
        $questionIdForDelete=array();
        foreach($qustionIds as $key=>$val){
            $questionIdForDelete[]=$key;
        }
        Reportissue::whereIn("id",$questionIdForDelete)->delete();
        return back()->with('success', 'Deleted successfully');

    }

}
