<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseType;
use App\Models\Tips;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;
use yajra\Datatables\Datatables;

class TipController extends Controller
{
    public function index()
    {
        return view('admin.tip.index', [
            'getCourse' => Course::where('status', '1')->orderBy('id', 'desc')->get(),
            'CourseType' => CourseType::all()
        ]);
    }

    public function status_update(Request $request)
    {
        $request->validate([
            'record_id' => 'required|integer',
            'status' => 'required|integer',
        ]);

        Tips::where('id', $request->record_id)->update(array('status' => $request->status));

        return response()->json(['status' => 1, 'message' => 'Tips status updated.']);
    }

    public function call_data(Request $request)
    {
        $get_data = Tips::orderBy('status', 'desc')->orderBy('id', 'desc')->get();

        if ($request->tip_type) {
            $get_data = Tips::orderBy('status', 'desc')->orderBy('id', 'desc')->
                where(['type' => $request->tip_type]);
        } else {
            $get_data = Tips::orderBy('status', 'desc')->orderBy('id', 'desc');
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;
               
        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_name", function ($get_data) {
                return (@$get_data->course_id) ? @$get_data->course_detail->course_name : "";
            })
            ->editColumn("tip_type", function ($get_data) {
                return $get_data->tip_type;
            })
            ->editColumn("tip_date", function ($get_data) {
                return $get_data->tip_date ? $get_data->tip_date : '-';
            })
            ->editColumn("tip_title", function ($get_data) {

                $truncated = '';
                if (!empty(trim($get_data->tip_title))) {
                    $truncated = Str::limit(strip_tags($get_data->tip_title), 20, '...');
                }

                return ($truncated) ?? "";
            })
            ->editColumn("status", function ($get_data) {
                if ($get_data->status == '1') {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" checked value="1" class="common_status_update ch_input form-check-input"
                         title="Active" data-id="' . $get_data->id . '" data-action="tip"  />
                        <span></span>
                    </div>';
                } else {
                    return '<div class="form-check form-switch">
                        <input type="checkbox" value="0" class="common_status_update ch_input form-check-input"
                         title="Inactive" data-id="' . $get_data->id . '" data-action="tip"  />
                        <span></span>
                    </div>';
                }
            })
            ->editColumn("created_at", function ($get_data) {
                return date("Y-m-d", strtotime($get_data->created_at));
            })
            ->editColumn("action", function ($get_data) {

                $cr_form = '<form id="form_del_' . $get_data->id . '" action="' . route('tip.destroy', $get_data->id) . '" method="POST">
                            <input type="hidden" name="_token" value="' . csrf_token() . '" />';

                $cr_form .= '<a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 view_in_modal" data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#modal_view_dt" ><i class="fa fa-eye"></i></a>';

                $cr_form .= '<a href="#" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act" data-id="' . $get_data->id . '" data-bs-toggle="modal" data-bs-target="#addForm" ><i class="fa fa-pencil"></i></a> ';

                $cr_form .= '<input type="hidden" name="_method" value="DELETE"> ';
                $cr_form .= '<button type="button" data-id="' . $get_data->id . '" class="btn btn-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';

                // $cr_form .= '<button type="button" data-id="'.$get_data->id.'" class="btn btn-danger btn-rounded btn-condensed btn-sm del-confirm" ><i class="fa fa-trash"></i></button>';

                $cr_form .= '</form>';

                return $cr_form;
            })
            ->rawColumns(['course_name', 'tip_type', 'status', 'action'])->with(['recordsTotal'=>$totalData, "recordsFiltered" => $totalFiltered,'start' => $start])->make(true);

    }

    public function get_data(Request $request)
    {
        if ($request->record_id) {
            $get_data = Tips::where('id', $request->record_id)->get()->toArray();
            return response()->json(['status' => 1, 'message' => 'Record Found.', 'result' => $get_data]);
        } else {
            return response()->json(['status' => 0, 'message' => 'No Record Found.', 'result' => array()]);
        }

    }

    public function store(Request $request)
    {
        $request->validate([
            'tip_type' => 'required',
            'course' => 'required',
            'tip_title' => 'required',
        ]);

        if (!empty($request->record_id)) {

            $res_data = Tips::find($request->record_id);
            $res_data->type = $request->tip_type;
            $res_data->course_id = $request->course;
            $res_data->course_type_id = $request->course_type_id;
            $res_data->tip_title = $request->tip_title;

            $res_data->web_link = $request->web_link ?? "";
            $res_data->description = $request->description ?? "";
            $res_data->status = $request->status;
            if (isset($request->tip_date)) {
                $res_data->tip_date = $request->tip_date;
            }

            if ($res_data->save()) {
                return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
            }
            return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);
        } else {
            $res_data = Tips::where(['type' => $request->tip_type, 'course_id' => $request->course, 'tip_title' => $request->tip_title])->first();
            if (isset($res_data->id)) {
                return response()->json(['status' => 2, 'message' => 'Record Already Exist.']);
            } else {
                $input = $request->all();

                if (isset($request->tip_type)) {
                    $input['type'] = $request->tip_type;
                }
                if (isset($request->course)) {
                    $input['course_id'] = $request->course;
                }

                $findWeekly = Tips::where(['type' => 2, 'course_id' => $request->course])->orderBy('id', 'desc')->first();
                $findWorkShop = Tips::where(['type' => 3, 'course_id' => $request->course])->orderBy('id', 'desc')->first();

                if ($input['type'] == 2) {
                    if (!empty($findWeekly)) {
                        $findUpdate = Tips::find($findWeekly->id);
                        $findUpdate->tip_title = $request->tip_title;
                        $findUpdate->web_link = $request->web_link ?? '';
                        $findUpdate->course_type_id = $request->course_type_id;
                        $findUpdate->description = $request->description ?? '';
                        $findUpdate->type = $request->tip_type;
                        $findUpdate->status = $request->status;
                        $findUpdate->tip_date = $request->tip_date;

                        $findUpdate->save();
                        return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
                    }
                }

                if ($input['type'] == 3) {
                    if (!empty($findWorkShop)) {
                        $findUpdateWorkshop = Tips::find($findWorkShop->id);
                        $findUpdateWorkshop->tip_title = $request->tip_title;
                        $findUpdateWorkshop->web_link = $request->web_link ?? '';
                        $findUpdateWorkshop->description = $request->description ?? '';
                        $findUpdateWorkshop->type = $request->tip_type;
                        $findUpdateWorkshop->course_type_id = $request->course_type_id;
                        $findUpdateWorkshop->status = $request->status;
                        $findUpdateWorkshop->tip_date = $request->tip_date;

                        $findUpdateWorkshop->save();
                        return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
                    }
                }

                Tips::create($input);
                return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);

            }
            return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);
        }
        return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);

    }

    public function store_OLD(Request $request)
    {
        $request->validate([
            'tip_type' => 'required',
            'course' => 'required',
            'tip_title' => 'required',
        ]);

        if (!empty($request->record_id)) {

            $res_data = Tips::find($request->record_id);
            $res_data->type = $request->tip_type;
            $res_data->course_id = $request->course;
            $res_data->course_type_id = $request->course_type_id;
            $res_data->tip_title = $request->tip_title;

            $res_data->web_link = $request->web_link ?? "";
            $res_data->description = $request->description ?? "";
            $res_data->status = $request->status;
            if (isset($request->tip_date)) {
                $res_data->tip_date = $request->tip_date;
            }

            if ($res_data->save()) {
                return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
            }
            return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);

        } else {
            $res_data = Tips::where(['type' => $request->tip_type, 'course_id' => $request->course, 'tip_title' => $request->tip_title])->first();
            if (isset($res_data->id)) {
                return response()->json(['status' => 2, 'message' => 'Record Already Exist.']);
            } else {
                $input = $request->all();

                if (isset($request->tip_type)) {
                    $input['type'] = $request->tip_type;
                }
                if (isset($request->course)) {
                    $input['course_id'] = $request->course;
                }

                $findWeekly = Tips::where(['type' => 2, 'course_id' => $request->course])->orderBy('id', 'desc')->first();
                $findWorkShop = Tips::where(['type' => 3, 'course_id' => $request->course])->orderBy('id', 'desc')->first();

                if ($input['type'] == 2) {
                    if (!empty($findWeekly)) {
                        $findUpdate = Tips::find($findWeekly->id);
                        $findUpdate->tip_title = $request->tip_title;
                        $findUpdate->web_link = $request->web_link ?? '';
                        $findUpdate->course_type_id = $request->course_type_id;
                        $findUpdate->description = $request->description ?? '';
                        $findUpdate->type = $request->tip_type;
                        $findUpdate->status = $request->status;
                        $findUpdate->tip_date = $request->tip_date;

                        $findUpdate->save();
                        return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
                    }
                }

                if ($input['type'] == 3) {
                    if (!empty($findWorkShop)) {
                        $findUpdateWorkshop = Tips::find($findWorkShop->id);
                        $findUpdateWorkshop->tip_title = $request->tip_title;
                        $findUpdateWorkshop->web_link = $request->web_link ?? '';
                        $findUpdateWorkshop->description = $request->description ?? '';
                        $findUpdateWorkshop->type = $request->tip_type;
                        $findUpdateWorkshop->course_type_id = $request->course_type_id;
                        $findUpdateWorkshop->status = $request->status;
                        $findUpdateWorkshop->tip_date = $request->tip_date;

                        $findUpdateWorkshop->save();
                        return response()->json(['status' => 1, 'message' => 'Record Updated Successfully.']);
                    }
                }

                Tips::create($input);
                return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);

            }
            return response()->json(['status' => 1, 'message' => 'Record Added Successfully.']);
        }
        return response()->json(['status' => 0, 'message' => 'Record Submission Failed.']);

    }

    public function destroy(Tips $tip,)
    {
        $tip->delete();
        
        return redirect()->route('tip.index')->with('success', 'Tip deleted successfully');
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
