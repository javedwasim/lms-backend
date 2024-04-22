<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Book;
use App\Models\BookAddon;
use App\Models\BookTestimonial;
use App\Models\BookTutor;

class BookController extends Controller
{
    public function index()
    {
        return view(
            'admin.book.index',
            [
                'book' => Book::all()
            ]
        );
    }

    public function create()
    {
        return view('admin.book.create');
    }

    public function store(Request $request)
    {
        $book = new Book();
        $book->title = $request->title;
        $book->sub_title = $request->sub_title;
        $book->start_time = $request->start_time;
        $book->end_time = $request->end_time;
        $book->background_gradient = $request->background_gradient;
        $book->description = $request->description;
        $book->position = $request->position;
        $book->status = "active";

        $book->save();
        $request['addon'] = array_filter($request->addon);
        $request['tutor_name'] = array_filter($request->tutor_name);
        $request['testimonial'] = array_filter($request->testimonial);

        if (!empty($request->addon)) {
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                $addonInsert[$key]['book_id'] = $book->id;
                $addonInsert[$key]['description'] = $val;
            }
            BookAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                $tutorInsert[$key]['book_id'] = $book->id;
                if (!empty($request->tutor_image[$key])) {
                    $image =   $request->tutor_image[$key]->store('tutor');
                    $tutorInsert[$key]['tutor_image'] = $image;
                }

                $tutorInsert[$key]['tutor_name'] = $val;
            }
            BookTutor::insert($tutorInsert);
        }
        if (!empty($request->testimonial)) {
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                $testimonialInsert[$key]['book_id'] = $book->id;

                $testimonialInsert[$key]['testimonial'] = $val;
            }
            BookTestimonial::insert($testimonialInsert);
        }

        return redirect("book")->with('success', 'Book Created Successfully');
    }

    public function edit(Book $book)
    {
        return view('admin.book.edit', [
            'book' => $book
        ]);
    }

    public function update(Request $request, Book $book)
    {
        // $allInput = $request->all();
        $book->title = $request->title;
        $book->sub_title = $request->sub_title;
        $book->start_time = $request->start_time;
        $book->background_gradient = $request->background_gradient;
        $book->end_time = $request->end_time;
        $book->description = $request->description;
        $book->position = $request->position;
        $book->status = "active";

        $book->save();

        if (!empty($request->addon)) {
            BookAddon::where("book_id", $book->id)->delete();
            $addonInsert = array();
            foreach ($request->addon as $key => $val) {
                if (!empty($val)) {
                    $addonInsert[$key]['book_id'] = $book->id;
                    $addonInsert[$key]['description'] = $val;
                }
            }
            BookAddon::insert($addonInsert);
        }
        if (!empty($request->tutor_name)) {
            $tutorInsert = array();
            foreach ($request->tutor_name as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert[$key]['book_id'] = $book->id;
                    $image =   $request->tutor_image[0]->store('tutor');
                    $tutorInsert[$key]['tutor_image'] = $image;
                    $tutorInsert[$key]['tutor_name'] = $val;
                }
            }
            if (!empty($tutorInsert)) {
                BookTutor::insert($tutorInsert);
            }
        }
        if (!empty($request->tutor_name_old)) {
            $tutorInsert = array();
            BookTutor::WhereNotIn("id", $request->tutor_id)->delete();
            foreach ($request->tutor_name_old as $key => $val) {
                if (!empty($val)) {
                    $tutorInsert['book_id'] = $book->id;
                    if (@$request->tutor_image_old[$key]) {
                        $image =   @$request->tutor_image_old[$key]->store('tutor');

                        $tutorInsert['tutor_image'] = $image;
                    }


                    $tutorInsert['tutor_name'] = $val;
                }

                BookTutor::where("id", $key)->update($tutorInsert);
            }
        }
        if (!empty($request->testimonial)) {
            BookTestimonial::where("book_id", $book->id)->delete();
            $testimonialInsert = array();
            foreach ($request->testimonial as $key => $val) {
                if (!empty($val)) {
                    $testimonialInsert[$key]['book_id'] = $book->id;

                    $testimonialInsert[$key]['testimonial'] = $val;
                }
            }
            BookTestimonial::insert($testimonialInsert);
        }

        return redirect("book")->with('success', 'Book Updated Successfully');
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return redirect("book")->with('success', ' Deleted Successfully');
    }

    public function cms()
    {
        $cmsData = CmsPage::where("type", "book")->first();

        return view('admin.book.cms', [
            'cmsData' => $cmsData
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
