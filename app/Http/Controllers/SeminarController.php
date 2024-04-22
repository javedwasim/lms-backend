<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Seminar;
use App\Models\SeminarAddon;
use App\Models\SeminarTestimonial;
use App\Models\SeminarTutor;

class SeminarController extends Controller
{
    public function index(Request $request)
    {
        $seminar = Seminar::query();
        if ($request->fromdate) {
            $seminar = $seminar->whereDate("start_time", ">=", $request->fromdate);
        }
        if ($request->todate) {
            $seminar = $seminar->whereDate("start_time", "<=", $request->todate);
        }
        $seminar = $seminar->get();

        return view('admin.seminar.index', [
            'seminar' => $seminar
        ]);
    }

    public function create()
    {
        return view('admin.seminar.create');
    }

    public function store(Request $request)
    {
        if ((!empty($request->start_time) && empty($request->end_time)) || (empty($request->start_time) && !empty($request->end_time))) {
            return redirect()->back()->with('error', 'Please Select Start and end date both');
        }
        $seminar = new Seminar();
        $seminar->title = $request->title;
        $seminar->order = $request->order;
        $seminar->sub_title = $request->sub_title;
        $seminar->start_time = $request->start_time;
        $seminar->end_time = $request->end_time;
        $seminar->background_gradient = $request->background_gradient;
        $seminar->description = $request->description ? $request->description  : "null";
        $seminar->status = "active";

        $seminar->save();

        $request['addon'] = array_filter($request->addon);
        $request['tutor_name'] = array_filter($request->tutor_name);
        $request['testimonial'] = array_filter($request->testimonial);

        if (!empty($request->addon)) {
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                $addonInsert[$key]['seminar_id'] = $seminar->id;
                $addonInsert[$key]['description'] = $val;
            }
            SeminarAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                $tutorInsert[$key]['seminar_id'] = $seminar->id;
                if (@$request->tutor_image[$key]) {
                    $image =   $request->tutor_image[$key]->store('tutor');
                    $tutorInsert[$key]['tutor_image'] = $image;
                }

                $tutorInsert[$key]['tutor_name'] = $val;
            }
            SeminarTutor::insert($tutorInsert);
        }
        if (!empty($request->testimonial)) {
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                $testimonialInsert[$key]['seminar_id'] = $seminar->id;

                $testimonialInsert[$key]['testimonial'] = $val;
            }
            SeminarTestimonial::insert($testimonialInsert);
        }

        return redirect("/seminar")->with('success', 'Seminar Created Successfully');
    }

    public function edit(Request $request, Seminar $seminar)
    {
        if ($request->isMethod('post')) {
        }

        return view('admin.seminar.edit', [
            'seminar' => $seminar
        ]);
    }

    public function update(Request $request, Seminar $seminar)
    {
        // $allInput = $request->all();

        $seminar->title = $request->title;
        $seminar->order = $request->order;
        $seminar->sub_title = $request->sub_title;
        $seminar->start_time = $request->start_time;
        $seminar->end_time = $request->end_time;
        $seminar->background_gradient = $request->background_gradient;
        $seminar->description = $request->description;
        $seminar->status = "active";
        $seminar->save();

        if (!empty($request->addon)) {
            SeminarAddon::where("seminar_id", $seminar->id)->delete();
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                if (!empty($val)) {
                    $addonInsert[$key]['seminar_id'] = $seminar->id;
                    $addonInsert[$key]['description'] = $val;
                }
            }
            SeminarAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert[$key]['seminar_id'] = $seminar->id;
                    if (@$request->tutor_image[$key]) {
                        $image =   $request->tutor_image[$key]->store('tutor');
                        $tutorInsert[$key]['tutor_image'] = $image;
                    }
                    $tutorInsert[$key]['tutor_name'] = $val;
                }
            }
            if (!empty($tutorInsert)) {
                SeminarTutor::insert($tutorInsert);
            }
        }
        if (!empty($request->tutor_name_old)) {
            $tutorInsert = array();
            SeminarTutor::WhereNotIn("id", $request->tutor_id)->delete();
            foreach ($request->tutor_name_old as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert['seminar_id'] = $seminar->id;
                    if (@$request->tutor_image_old[$key]) {
                        $image =   @$request->tutor_image_old[$key]->store('tutor');

                        $tutorInsert['tutor_image'] = $image;
                    }


                    $tutorInsert['tutor_name'] = $val;
                }

                SeminarTutor::where("id", $key)->update($tutorInsert);
            }
        }
        if (!empty($request->testimonial)) {
            SeminarTestimonial::where("seminar_id", $seminar->id)->delete();
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                if (!empty($val)) {
                    $testimonialInsert[$key]['seminar_id'] = $seminar->id;

                    $testimonialInsert[$key]['testimonial'] = $val;
                }
            }
            SeminarTestimonial::insert($testimonialInsert);
        }

        return redirect("/seminar")->with('success', 'Seminal Updated Successfully');
    }

    public function destroy(Seminar $seminar)
    {
        $seminar->delete();

        return redirect("/seminar")->with('success', ' Deleted Successfully');
    }

    public function cms()
    {
        return view('admin.seminar.cms', [
            'cmsData' => CmsPage::where("type", "seminar")->first()
        ]);
    }

    public function cmssave(Request $request)
    {

        if ($request->isMethod('post')) {
            $type = $request->type;
            $checkUrl = CmsPage::where("type", $type)->first();
            $input = $request->all();
            unset($input['_token']);
            unset($input['center_image']);
            unset($input['section_icon_1']);
            unset($input['section_icon_2']);
            unset($input['section_icon_3']);
            unset($input['section_icon_4']);
            unset($input['section_icon_5']);

            if ($request->hasFile('section_icon_1')) {

                $profile_photo_img_path = $request->section_icon_1->store('cms');
                $input['section_icon_1'] = $profile_photo_img_path;
            }
            if ($request->hasFile('section_icon_2')) {

                $profile_photo_img_path = $request->section_icon_2->store('cms');
                $input['section_icon_2'] = $profile_photo_img_path;
            }
            if ($request->hasFile('section_icon_3')) {

                $profile_photo_img_path = $request->section_icon_3->store('cms');
                $input['section_icon_3'] = $profile_photo_img_path;
            }
            if ($request->hasFile('section_icon_4')) {

                $profile_photo_img_path = $request->section_icon_4->store('cms');
                $input['section_icon_4'] = $profile_photo_img_path;
            }
            if ($request->hasFile('section_icon_5')) {

                $profile_photo_img_path = $request->section_icon_5->store('cms');
                $input['section_icon_5'] = $profile_photo_img_path;
            }
            if ($request->hasFile('center_image')) {

                $profile_photo_img_path = $request->center_image->store('cms');
                $input['center_image'] = $profile_photo_img_path;
            }

            if (empty($checkUrl)) {
                CmsPage::insert($input);
            } else {
                CmsPage::where("id", $checkUrl->id)->update($input);
            }

            return redirect()->back()->with('success', 'Updated successfully');
        }
    }
}
