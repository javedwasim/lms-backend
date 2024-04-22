<?php
    
namespace App\Http\Controllers\Question;
    
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
 
use App\Models\Paragraph;

use yajra\Datatables\Datatables;  

class ParagraphController extends Controller
{ 
    public function index(Request $request)
    {
        return view('admin.paragraph.index',[]);       
    }
     
    public function call_data(Request $request)
    {
        $get_data = Paragraph::orderBy('id','desc')->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->editColumn("paragraph",function($get_data){
                return strlen($get_data->paragraph) > 50 ? substr($get_data->paragraph,0,50)."..." : $get_data->paragraph;                                                              
            })   
            ->editColumn("created_at",function($get_data){
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action",function($get_data){

                $cr_form = '<form id="form_del_'.$get_data->id.'" action="'.route('paragraph.destroy',$get_data->id).'" method="POST">
                            <input type="hidden" name="_token" value="'.csrf_token().'" />';
 
                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="'.route('paragraph.show',$get_data->id).'" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm" href="'.route('paragraph.edit',$get_data->id).'"><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';

                $cr_form .= '<button type="button" data-id="'.$get_data->id.'" class="btn btn-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>'; 

                $cr_form .='</form>';

                return $cr_form;
             })->rawColumns(['action'])->make(true);

    } 

    public function create()
    { 
        return view('admin.paragraph.create',[]);
    }
    
    public function store(Request $request)                                     
    { 
        $this->validate($request, [
            'paragraph' => 'required',  
        ]);
        
        $input = $request->all(); 
     
        $user = Paragraph::create($input); 
         
        return redirect()->route('paragraph.index')->with('success','Paragraph created successfully');
    }
     
    public function show($id)
    {
        $get_data = Paragraph::find($id); 
 
        $page_title = 'paragraph';
        return view('admin.paragraph.show',compact('get_data','page_title'));
    }
     
    public function edit($id)
    {   
        $get_data = Paragraph::find($id); 
        $page_title = 'paragraph';
        return view('admin.paragraph.edit',compact('get_data','page_title'));
    }
 
    public function update(Request $request, $id)
    { 
        $this->validate($request, [
            'paragraph' => 'required',   
        ]); 
 
        $input = $request->all();
        
        $page_title = 'Paragraph';

        $user = Paragraph::find($id);

        $user->update($input);
    
        return redirect()->route('paragraph.index')->with('success','Paragraph updated successfully'); 
    }
   
    public function destroy($id)
    {
        Paragraph::find($id)->delete();
        return redirect()->route('paragraph.index')->with('success','Paragraph deleted successfully');
    } 

}