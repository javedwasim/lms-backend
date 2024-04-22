@extends('layouts.master')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
<div class="card">
  <div class="pb-0 card-header">
    <h5>Create Testimonial
      <!-- <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('tutorial.index') }}"> Back</a> -->
    </h5>
  </div>
  <div class="card-body">
    <form id="validate" action="/testimonial" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
      @csrf
      <div class="row fil_ters">


        <div class="col-md-12">
          <div class="form-group">
            <label>Testimonial<code>*</code></label>
            <textarea placeholder="Title"  class="form-control " required  name="testimonial" autocomplete="off"></textarea>

          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group">
            <label>Submited by <code>*</code></label>
            <input type="text" class="form-control " required  id="sub_title" placeholder="Submited by " name="submited_by" autocomplete="off">

          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group">
            <label>Position<code>*</code></label>
            <input type="text" class="form-control "  required id="section_title_1" placeholder="Position" name="position" autocomplete="off">

          </div>
        </div>
       

      </div> <br />
      <div class="row">
        <div class="text-right col-xs-12 col-sm-12 col-md-12">
          <input type="hidden" name="type" value="{{$type}}">
          <button type="submit" class="form-group btn btn-primary">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection