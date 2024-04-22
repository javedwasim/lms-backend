<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use yajra\Datatables\Datatables;

class SubCategoryController extends Controller
{
    public function index()
    {
        $get_category = Category::where('status', '1')->get(['id', 'category_name']);
        return view('admin.sub_category.index', compact('get_category'));
    }

    public function call_data(Request $request)
    {
        $status = $request->status;
        $get_data = SubCategory::query();
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
                return (@$get_data->category_id) ? @$get_data->category_detail->category_name : "";
            })
            ->editColumn("sub_category_name", function ($get_data) {
                return $get_data->sub_category_name ?? 'N/A';
            })
            ->editColumn("status", function ($get_data) {
                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="sub_category"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="sub_category"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at)) ?? 'N/A';;
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('sub_category.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 view_in_modal" data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#modal_view_dt" ><i class="fa fa-eye"></i></a>';

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
            $get_data = SubCategory::where('id', $request->record_id)->get()->toArray();
            return response()->json(['status' => 1, 'message' => 'Record Found.', 'result' => $get_data]);
        } else {
            return response()->json(['status' => 0, 'message' => 'No Record Found.', 'result' => array()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required',
            'sub_category_name' => 'required',
        ]);

        if ($request->record_id) {
            $res_data = SubCategory::find($request->record_id);
            $res_data->category_id = $request->input('category_name');
            $res_data->sub_category_name = $request->input('sub_category_name');
            $res_data->status = $request->input('status');
            $res_data->save();

            return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
        } else {
            $res_data = SubCategory::where(['sub_category_name' => $request->sub_category_name])->first();
            if (isset($res_data->id)) {
                return response()->json(['status' => 2, 'message' => 'Record Already Exist.']);
            } else {
                $res_data = new SubCategory;
                $res_data->category_id = $request->input('category_name');
                $res_data->sub_category_name = $request->input('sub_category_name');
                $res_data->status = $request->input('status');
                $res_data->save();
            }
            return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);
        }
        return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);
    }

    public function destroy(SubCategory $sub_category)
    {
        $sub_category->delete();

        return redirect()->route('sub_category.index')->with('success', 'Sub Category deleted successfully');
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        SubCategory::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Sub Category status updated.']);
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
