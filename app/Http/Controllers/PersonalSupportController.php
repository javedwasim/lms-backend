<?php
    
namespace App\Http\Controllers;
    
use Illuminate\Http\Request;
use App\Models\Course; 
use App\Models\PersonalSupport; 
use Auth;
use Validator;
use yajra\Datatables\Datatables; 
use Illuminate\Support\Str;
class PersonalSupportController extends Controller
{  
    function __construct()
    {   
    }  

    public function index()
    {   
        $getCourse = Course::where('status','1')->orderBy('course_name','asc')->get();   
        return view('admin.personal_support.index', [
            'getCourse' => $getCourse, 
        ]);
    }
    
    public function status_update(Request $request)
    {   
        $request->validate([
            'record_id' => 'required|integer',  
            'status' => 'required|integer', 
        ]);

        PersonalSupport::where('id',$request->record_id)->update(array('status'=>$request->status));
        
        return response()->json(['status'=>1,'message'=>'Personal Support status updated.']); 
    } 
    
    public function call_data(Request $request)
    { 
        $get_data = PersonalSupport::orderBy('status','desc')->orderBy('id','desc');
        
        $totalData = $get_data->count();
        $totalFiltered = $totalData;
               
        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->offset($start)->limit($limit)->get();
  
        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_name",function($get_data){
                $getCourse = Course::where('id',$get_data->course_id)->first(['course_name']); 
                
                return !empty($getCourse->course_name)?$getCourse->course_name:"N/A";
            })
            ->editColumn("support_title",function($get_data){
                $truncated = '';
                if(!empty(trim($get_data->support_title)))
                {
                    $truncated = Str::limit(strip_tags($get_data->support_title), 20, '...');    
                }
              
                return !empty($truncated)?$truncated:"N/A";
            })  
            ->editColumn("status",function($get_data){  
               if($get_data->status=='1'){
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="'.$get_data->id.'" data-action="personal_support"  />
                        <span></span>
                    </div>';
                }else{
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="'.$get_data->id.'" data-action="personal_support"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at",function($get_data){
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action",function($get_data){

                $cr_form = '<form id="form_del_'.$get_data->id.'" action="'.route('personal_support.destroy',$get_data->id).'" method="POST">
                            <input type="hidden" name="_token" value="'.csrf_token().'" />';
  
                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 view_in_modal"
                  data-id="'.$get_data->id.'" data-bs-toggle="modal" data-bs-target="#modal_view_dt" ><i class="fa fa-eye"></i></a>'; 
 
                 $cr_form .= '<a href="#" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" data-id="'.$get_data->id.'" data-bs-toggle="modal" data-bs-target="#addForm" ><i class="fa fa-pencil"></i></a> ';   
 
                $cr_form .='</form>';

                return $cr_form;
            }) 
            ->rawColumns(['course_name','status','action'])->with(['recordsTotal'=>$totalData, "recordsFiltered" => $totalFiltered,'start' => $start])->make(true);

    }
    
    public function get_data(Request $request)
    {   
        if($request->record_id){
            $get_data = PersonalSupport::where('id',$request->record_id)->get()->toArray(); 
            return response()->json(['status'=>1,'message'=>'Record Found.','result'=>$get_data]);
        }else{
            return response()->json(['status'=>0,'message'=>'No Record Found.','result'=>array() ]);
        } 

    } 
    public function store(Request $request)
    {  
        $request->validate([
            'course' => 'required',  
            'support_title' => 'required',  
        ]);

        if($request->record_id){
 
            $res_data = PersonalSupport::find($request->record_id); 
            $res_data->course_id = $request->course;
            $res_data->support_title = $request->support_title;
            $res_data->support_link = $request->support_link ?? "";
            $res_data->status = $request->status;  
            $res_data->save();
            
            return response()->json(['status'=>1,'message'=>'Record Updated Successfully.' ]);
        }else{
            $res_data = PersonalSupport::where(['course_id' => $request->course])->first();
            if(isset($res_data->id)){
                return response()->json(['status'=>2,'message'=>'Record Already Exist.' ]);
            }else{ 
                $input = $request->all(); 
                $input['course_id'] = $request->course;
                PersonalSupport::create($input);
            }
            return response()->json(['status'=>1,'message'=>'Record Added Successfully.']);
        } 
        return response()->json(['status'=>0,'message'=>'Record Submission Failed.']); 
    }
    
    public function destroy($id)
    {  
        $insert = PersonalSupport::where('id',$id)->delete();

        return redirect()->route('personal_support.index')->with('success','Personal Support deleted successfully');
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
  
}