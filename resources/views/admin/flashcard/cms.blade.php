@extends('layouts.master')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
<div class="card">
  <div class="card-header pb-0">
    <h5>Update FlashCard Page
      <!-- <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('tutorial.index') }}"> Back</a> -->
    </h5>
  </div>
  <div class="card-body">
    <form id="validate" action="{{url('flashcard/cmssave')}}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
      @csrf
      <div class="row fil_ters">


        <div class="col-md-12">
          <div class="form-group">
            <label>Expert Tutors Heading Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->main_title}}" id="main_title" placeholder="Title" name="main_title" autocomplete="off">

          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label>Expert Tutors Heading Sub Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->sub_title}}" id="sub_title" placeholder="Sub Title" name="sub_title" autocomplete="off">

          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label>Section 1 Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_title_1}}" id="section_title_1" placeholder="Title" name="section_title_1" autocomplete="off">

          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Section 1 Description<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_description_1}}" id="section_description_1" placeholder="Description" name="section_description_1" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Section 1 Icon<code>*</code></label>
            <input type="file" class="form-control " value="{{$cmsData->section_icon_1}}" id="section_icon_1" placeholder="Title" name="section_icon_1" autocomplete="off">

          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label>Section 2 Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_title_2}}" id="section_title_2" placeholder="Title" name="section_title_2" autocomplete="off">

          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Section 2 Description<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_description_2}}" id="section_description_2" placeholder="Description" name="section_description_2" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Section 2 Icon<code>*</code></label>
            <input type="file" class="form-control " value="{{$cmsData->section_icon_2}}" id="section_icon_2" placeholder="Title" name="section_icon_2" autocomplete="off">

          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label>Section 3 Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_title_3}}" id="section_title_3" placeholder="Title" name="section_title_3" autocomplete="off">

          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Section 3 Description<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_description_3}}" id="section_description_3" placeholder="Description" name="section_description_3" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Section 3 Icon<code>*</code></label>
            <input type="file" class="form-control " value="{{$cmsData->section_icon_3}}" id="section_icon_3" placeholder="Title" name="section_icon_3" autocomplete="off">

          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label>Section 4 Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_title_4}}" id="section_title_4" placeholder="Title" name="section_title_4" autocomplete="off">

          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Section 4 Description<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_description_4}}" id="section_description_4" placeholder="Description" name="section_description_4" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Section 4 Icon<code>*</code></label>
            <input type="file" class="form-control " value="{{$cmsData->section_icon_4}}" id="section_icon_4" placeholder="Title" name="section_icon_4" autocomplete="off">

          </div>
        </div>

        <div class="col-md-3">
          <div class="form-group">
            <label>Section 5 Title<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_title_5}}" id="section_title_5" placeholder="Title" name="section_title_5" autocomplete="off">

          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Section 5 Description<code>*</code></label>
            <input type="text" class="form-control " value="{{$cmsData->section_description_5}}" id="section_description_5" placeholder="Description" name="section_description_5" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Section 5 Icon<code>*</code></label>
            <input type="file" class="form-control " value="{{$cmsData->section_icon_5}}" id="section_icon_5" placeholder="Title" name="section_icon_5" autocomplete="off">

          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Center Image<code>*</code></label>
            <input type="file" class="form-control " value="" id="center_image" placeholder="Title" name="center_image" autocomplete="off">

          </div>
        </div>
        <div class="col-md-12"></div>
          <div class="col-md-2">
            <div class="form-group">
              <label>Center Image<code>*</code></label><br>
              <img src="{{asset('public/uploads')}}/{{$cmsData->center_image}}" width="120px">

            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label>Section 1 Icon<code>*</code></label>
              <img src="{{asset('public/uploads')}}/{{$cmsData->section_icon_1}}" width="120px">

            </div>
          </div>
         
          <div class="col-md-2">
            <div class="form-group">
              <label>Section 2 Icon<code>*</code></label>
              <img src="{{asset('public/uploads')}}/{{$cmsData->section_icon_2}}" width="120px">

            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label>Section 3 Icon<code>*</code></label>
              <img src="{{asset('public/uploads')}}/{{$cmsData->section_icon_3}}" width="120px">

            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label>Section 4 Icon<code>*</code></label>
              <img src="{{asset('public/uploads')}}/{{$cmsData->section_icon_4}}" width="120px">

            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label>Section 5 Icon<code>*</code></label>
              <img src="{{asset('public/uploads')}}/{{$cmsData->section_icon_5}}" width="120px">

            </div>
          </div>
       

      </div> <br />
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 text-right">
          <input type="hidden" name="type" value="flashcard">
          <button type="submit" class="form-group btn btn-primary">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection