<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use yajra\Datatables\Datatables;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.category.index');
    }

    public function call_data(Request $request)
    {
        $status = $request->status;
        $get_data = Category::query();
        if ($status != '') {
            $get_data = $get_data->where("status", $status);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("category_name", function ($get_data) {
                return $get_data->category_name;
            })
            ->editColumn("short_name", function ($get_data) {
                return $get_data->short_name ?? 'N/A';
            })
            ->editColumn("status", function ($get_data) {
                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="category"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="category"  />
                        <span></span>
                    </div>';
                }
            })

            ->editColumn("sort", function ($get_data) {
                return $get_data->sort ?? 'N/A';
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('category.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 view_in_modal"
                  data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#modal_view_dt" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a href="#" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#addForm" ><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';

                // $cr_form .= '<button type="button" data-id="'.$get_data->id.'" class="btn btn-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>'; 

                $cr_form .= '</form>';

                return $cr_form;
            })
            ->rawColumns(['status', 'action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function get_data(Request $request)
    {
        if ($request->record_id) {
            $get_data = Category::where('id', $request->record_id)->get()->toArray();
            return response()->json(['status' => 1, 'message' => 'Record Found.', 'result' => $get_data]);
        } else {
            return response()->json(['status' => 0, 'message' => 'No Record Found.', 'result' => array()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required',
            'time' => 'required',
            'short_name' => 'required|max:2',
            'sort' => 'required'
        ]);

        if ($request->record_id) {
            $res_data = Category::find($request->record_id);
            $res_data->category_name = $request->input('category_name');
            $res_data->time = $request->input('time');
            $res_data->status = $request->input('status');
            $res_data->short_name = $request->short_name;
            $res_data->sort = $request->input('sort');
            $res_data->save();

            return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
        } else {
            $res_data = Category::where(['category_name' => $request->category_name])->first();
            if (isset($res_data->id)) {
                return response()->json(['status' => 2, 'message' => 'Record Already Exist.']);
            } else {
                $res_data = new Category;
                $res_data->category_name = $request->input('category_name');
                $res_data->time = $request->input('time');
                $res_data->status = $request->input('status');
                $res_data->short_name = $request->short_name;
                $res_data->sort = $request->input('sort');
                $res_data->save();
            }
            return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);
        }
        return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('category.index')->with('success', 'Category deleted successfully');
    }

    
    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        Category::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Category status updated.']);
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

    public function update(Request $request)
    {
    }
}
