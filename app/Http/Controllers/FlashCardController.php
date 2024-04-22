<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\FlashCard;
use App\Models\FlashCardAddon;
use App\Models\FlashCardTestimonial;
use App\Models\FlashCardTutor;

class FlashCardController extends Controller
{
    public function index()
    {
        return view('admin.flashcard.index', [
            'flashcard' => FlashCard::all(),
        ]);
    }

    public function create()
    {
        return view('admin.flashcard.create');
    }

    public function store(Request $request)
    {
        $flashcard = new FlashCard();
        $flashcard->title = $request->title;
        $flashcard->sub_title = $request->sub_title;
        $flashcard->start_time = $request->start_time;
        $flashcard->end_time = $request->end_time;
        $flashcard->background_gradient = $request->background_gradient;
        $flashcard->description = $request->description;
        $flashcard->position = $request->position;
        $flashcard->status = "active";

        $flashcard->save();
        $request['addon'] = array_filter($request->addon);
        $request['tutor_name'] = array_filter($request->tutor_name);
        $request['testimonial'] = array_filter($request->testimonial);
        if (!empty($request->addon)) {
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                $addonInsert[$key]['flash_card_id'] = $flashcard->id;
                $addonInsert[$key]['description'] = $val;
            }
            FlashCardAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                $tutorInsert[$key]['flash_card_id'] = $flashcard->id;
                if (!empty($request->tutor_image[$key])) {
                    $image =   $request->tutor_image[$key]->store('tutor');
                    $tutorInsert[$key]['tutor_image'] = $image;
                }

                $tutorInsert[$key]['tutor_name'] = $val;
            }
            FlashCardTutor::insert($tutorInsert);
        }
        if (!empty($request->testimonial)) {
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                $testimonialInsert[$key]['flash_card_id'] = $flashcard->id;

                $testimonialInsert[$key]['testimonial'] = $val;
            }
            FlashCardTestimonial::insert($testimonialInsert);
        }

        return redirect("/flashcard")->with('success', 'FlashCard Created Successfully');
    }

    public function edit(FlashCard $flashcard)
    {
        return view('admin.flashcard.edit', [
            'flashcard' => $flashcard
        ]);
    }

    public function update(Request $request, FlashCard $flashcard)
    {
        // $allInput = $request->all();

        $flashcard->title = $request->title;
        $flashcard->sub_title = $request->sub_title;
        $flashcard->start_time = $request->start_time;
        $flashcard->end_time = $request->end_time;
        $flashcard->background_gradient = $request->background_gradient;
        $flashcard->description = $request->description;
        $flashcard->position = $request->position;
        $flashcard->status = "active";

        $flashcard->save();
        if (!empty($request->addon)) {
            FlashCardAddon::where("flash_card_id", $flashcard->id)->delete();
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                if (!empty($val)) {
                    $addonInsert[$key]['flash_card_id'] = $flashcard->id;
                    $addonInsert[$key]['description'] = $val;
                }
            }
            FlashCardAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert[$key]['flash_card_id'] = $flashcard->id;
                    $image =   $request->tutor_image[0]->store('tutor');
                    $tutorInsert[$key]['tutor_image'] = $image;
                    $tutorInsert[$key]['tutor_name'] = $val;
                }
            }
            if (!empty($tutorInsert)) {
                FlashCardTutor::insert($tutorInsert);
            }
        }
        if (!empty($request->tutor_name_old)) {
            $tutorInsert = array();
            FlashCardTutor::WhereNotIn("id", $request->tutor_id)->delete();
            foreach ($request->tutor_name_old as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert['flash_card_id'] = $flashcard->id;
                    if (@$request->tutor_image_old[$key]) {
                        $image =   @$request->tutor_image_old[$key]->store('tutor');

                        $tutorInsert['tutor_image'] = $image;
                    }
                    $tutorInsert['tutor_name'] = $val;
                }
                FlashCardTutor::where("id", $key)->update($tutorInsert);
            }
        }
        if (!empty($request->testimonial)) {
            FlashCardTestimonial::where("flash_card_id", $flashcard->id)->delete();
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                if (!empty($val)) {
                    $testimonialInsert[$key]['flash_card_id'] = $flashcard->id;

                    $testimonialInsert[$key]['testimonial'] = $val;
                }
            }
            FlashCardTestimonial::insert($testimonialInsert);
        }

        return redirect("/flashcard")->with('success', 'FlashCard Updated Successfully');
    }

    public function destroy(FlashCard $flashcard)
    {
        $flashcard->delete();

        return redirect("/flashcard")->with('success', ' Deleted Successfully');
    }

    public function cms()
    {
        $cmsData = CmsPage::where("type", "flashcard")->first();

        return view(
            'admin.flashcard.cms',
            [
                'cmsData' => $cmsData
            ]
        );
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
