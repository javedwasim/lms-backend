<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Models\Category;
use App\Models\CategoryUcatScore;
use App\Models\CourseType;
use yajra\Datatables\Datatables;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function index()
    {
        $getCategory = Category::all();
        $CourseType = CourseType::all();
        
        return view(
            'admin.ucat_score.index',
            [
                'getCategory' => $getCategory,
                'CourseType' => $CourseType,
            ]
        );
    }
    
    public function ucat_call_data(Request $request)
    {
        $categoryId = $request->categoryId;
        $course_type_id = $request->course_type_id;
        $get_data = CategoryUcatScore::query();
        
        if ($categoryId) {
            $get_data = $get_data->where("category_id", $categoryId);
        }
        if ($course_type_id) {
            $get_data = $get_data->where("course_type_id", $course_type_id);
        }

        $totalData = $get_data->count();
        $totalFiltered = $totalData;

        $limit = ($request->get('length')) ? $request->get('length') : 10;
        $start = ($request->get('start')) ? $request->get('start') : 0;

        $get_data = $get_data->orderBy('id', 'desc')->offset($start)->limit($limit)->get();

        return Datatables::of($get_data)
            ->addIndexColumn()
            ->setOffset($start)
            ->editColumn("course_type", function ($get_data) {
                return @$get_data->courseType->name;
            })
            ->editColumn("category_name", function ($get_data) {
                return $get_data->category->category_name;
            })
            ->editColumn("band", function ($get_data) {
                return @$get_data->band->name ? $get_data->band->name : "";
            })
            ->editColumn("min_score", function ($get_data) {
                return $get_data->min_score ?? 'N/A';
            })
            ->editColumn("max_score", function ($get_data) {
                return $get_data->max_score ?? 'N/A';
            })
            ->editColumn("ucat_score", function ($get_data) {
                return $get_data->score ?? 'N/A';
            })
            ->editColumn("action", function ($get_data) {
                $cr_form = '<a href="' . url('ucatscore/' . $get_data->id . '/edit') . '" class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm form_data_act"  ><i class="fa fa-pencil"></i></a> ';
                $cr_form .= '<form action="' . url('ucatscore/' . $get_data->id) . '" method="post" class="form_data_act" style="display:inline-block;">';
                $cr_form .= csrf_field();
                $cr_form .= method_field('delete');
                $cr_form .= '<button type="submit" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm form_data_act" onclick="deleteRecord(this)" data-name="' . $get_data->name . '" data-type="ucatscore" data-id="' . $get_data->id . '" ><i class="fa fa-trash"></i></button>';
                $cr_form .= '</form>';
                
                return $cr_form;
            })
            ->rawColumns(['action'])->with(['recordsTotal' => $totalData, "recordsFiltered" => $totalFiltered, 'start' => $start])->make(true);
    }

    public function create()
    {
        $category = Category::all();
        $band = Band::all();
        $CourseType = CourseType::all();

        return view('admin.ucat_score.create', [
            'category' => $category,
            'band' => $band,
            'CourseType' => $CourseType,
        ]);
    }

    public function store(Request $request)
    {
        $categoryUcatscore = new CategoryUcatScore();
        $categoryUcatscore->min_score = $request->min_score;
        $categoryUcatscore->max_score = $request->max_score;
        $categoryUcatscore->band_id = $request->band_id;
        $categoryUcatscore->course_type_id = $request->course_type_id;
        $categoryUcatscore->score = $request->score;
        $categoryUcatscore->category_id = $request->category_id;
        $categoryUcatscore->save();

        return redirect("ucatscore")->with('success', 'UCAT Created Successfully');
    }

    public function edit(CategoryUcatScore $ucatscore)
    {
        $category = Category::all();
        $band = Band::all();
        $CourseType = CourseType::all();

        return view('admin.ucat_score.edit', [
            'category' => $category,
            'band' => $band,
            'CourseType' => $CourseType,
            'ucat' => $ucatscore,
        ]);
    }

    public function update(CategoryUcatScore $ucatscore, Request $request)
    {
        $ucatscore->min_score = $request->min_score;
        $ucatscore->max_score = $request->max_score;
        $ucatscore->course_type_id = $request->course_type_id;
        $ucatscore->band_id = $request->band_id;
        $ucatscore->score = $request->score;
        $ucatscore->category_id = $request->category_id;
        $ucatscore->save();

        return redirect("ucatscore")->with('success', 'UCAT Score updated Successfully');
    }
    
    public function destroy(CategoryUcatScore $ucatscore)
    {
        $ucatscore->delete();
        
        return redirect("ucatscore")->with('success', 'UCAT Score Deleted Successfully');
    }
}
