<?php
    
namespace App\Http\Controllers;
    
use yajra\Datatables\Datatables;
use App\Models\Reportissue;
class ReportissueController extends Controller
{  
    public function index()
    { 
        return view('admin.report_issue.index',[]);    
    }

    public function call_data(){
        $get_data = Reportissue::select('users.name as username','question_answer_tbl.question_name as questions','report_issue.options as options','report_issue.email as emailid','report_issue.description as desc','report_issue.created_at as datetime')->join('users','users.id','=','report_issue.user_id')
        ->Leftjoin('question_answer_tbl','question_answer_tbl.id','=','report_issue.question_id')
        ->orderBy('report_issue.created_at','desc')->get();


        return Datatables::of($get_data)
            ->addIndexColumn()
            ->editColumn("username",function($get_data){
                return $get_data->username;
            }) 
            ->editColumn("emailid",function($get_data){
                return $get_data->emailid;
            }) 
            ->editColumn("questions",function($get_data){
                return $get_data->questions;
            }) 
            ->editColumn("options",function($get_data){
                return $get_data->options;
            }) 
            ->editColumn("desc",function($get_data){
                return $get_data->desc;
            }) 
            ->editColumn("datetime",function($get_data){
                return date("Y-m-d", strtotime($get_data->datetime));
            })->make(true);
    }
}