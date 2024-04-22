<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseType;

class CourseTypeController extends Controller
{
    public function index(Request $request)
    {
        $courseType = CourseType::query();
        if($request->fromdate)
        {
            $courseType=$courseType->whereDate("start_time",">=",$request->fromdate);
        }
        if($request->todate)
        {
            $courseType=$courseType->whereDate("start_time","<=",$request->todate);
        }
        $courseType=$courseType->get();
        return view('admin.courseType.index', compact('courseType'));
    }

    public function create()
    {
        return view('admin.courseType.create');
    }

    public function store(Request $request)
    {
        $courseType = new CourseType();
        $courseType->name = $request->name;
        $courseType->save();
       
        return redirect("/coursetype")->with('success', 'Course Type Created Successfully');
    }

    public function edit(Request $request, CourseType $coursetype)
    { 
        return view('admin.courseType.edit', [
            'courseType' => $coursetype
        ]);
    }

    public function update(Request $request, CourseType $coursetype)
    {
        $coursetype->name = $request->name;      
        $coursetype->save();
      
        return redirect("/coursetype")->with('success', 'Course Type Updated Successfully');
    }

    public function destroy(CourseType $coursetype)
    {
        $coursetype->delete();

        return redirect("/coursetype")->with('success', ' Deleted Successfully');
    }
}
