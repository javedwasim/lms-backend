<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type;
        $testimonial = Testimonial::where("type", $type)->get();

        return view('admin.testimonial.index', [
            'testimonial' => $testimonial,
            'type' => $type
        ]);
    }

    public function testimonial(Request $request)
    {
        $type = $request->type;
        $testimonial = Testimonial::where("type", $type)->get();

        return view('admin.testimonial.index', [
            'testimonial' => $testimonial,
            'type' => $type
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->type;

        return view('admin.testimonial.create', [
            'type' => $type
        ]);
    }

    public function store(Request $request)
    {
        $checkUrl = new Testimonial();
        $checkUrl->submited_by = $request->submited_by;
        $checkUrl->position = $request->position;
        $checkUrl->testimonial = $request->testimonial;
        $checkUrl->type = $request->type;
        $checkUrl->save();

        return redirect("/testimonial?type=" . $request->type)->with('success', 'Testimonial Created Successfully');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonial.edit', [
            'testimonial' => $testimonial,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $testimonial->submited_by = $request->submited_by;
        $testimonial->position = $request->position;
        $testimonial->testimonial = $request->testimonial;
        $testimonial->save();

        return redirect("/testimonial?type=" . $testimonial->type)->with('success', 'Testimonial Updated Successfully');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->back()->with('success', 'Testimonial Deleted Successfully');
    }
}
