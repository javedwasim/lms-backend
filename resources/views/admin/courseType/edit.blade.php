@extends('layouts.master')
@section('content')
    <style>
        .custombtn {
            padding-top: 31px;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
    <div class="card">
        <div class="pb-0 card-header">
            <h5>Update Course Type
                <!-- <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('tutorial.index') }}"> Back</a> -->
            </h5>
        </div>
        <div class="card-body">
            <form id="validate" action="/coursetype/{{$courseType->id}}" class="form-horizontal comm_form" method="POST" role="form"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row fil_ters">


                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Name<code>*</code></label>
                            <input type="text" class="form-control " id="name" placeholder="name" name="name"
                                autocomplete="off" value="{{ $courseType->name }}">

                        </div>
                    </div>
                 

              
                <div class="row">
                    <div class="text-right col-xs-12 col-sm-12 col-md-12">
                      <input type="hidden" value="{{@$courseType->id}}" name="id">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('script')
    <script>
        function addmoretestimonial() {
            str =
                '<div class="row"> <div class="col-md-6"><div class="form-group"><label> Testimonial<code>*</code></label> <input type="text" class="form-control "  id="end_time" placeholder="Testimonial" name="testimonial[]" autocomplete="off"> </div>  </div> <div class="col-md-6"><div class="form-group custombtn"><a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div></div></div>';
            $(".testimonial").append(str);

        }

        function addmoretutor() {
            str =
                '<div class="row">  <div class="col-md-6"> <div class="form-group"><label> Tutor Name<code>*</code></label><input type="text" class="form-control "  id="end_time" placeholder=" Tutor Name" name="tutor_name[]" autocomplete="off"></div>  </div>  <div class="col-md-4"> <div class="form-group"><label> Tutor Image<code>*</code></label><input type="file" class="form-control "  id="end_time" placeholder="Sub Title" name="tutor_image[]" autocomplete="off"></div>  </div>  <div class="col-md-2"> <div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div>  </div></div>';
            $(".addon").append(str);

        }

        function addmoreAddon() {
            str =
                '<div class="row"> <div class="col-md-6"> <div class="form-group"><label> Seminar Addon<code>*</code></label><input type="text" class="form-control "  id="addon" placeholder="Seminar Addon" name="addon[]" autocomplete="off"></div> </div> <div class="col-md-6"><div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
            $(".addon").append(str);

        }

        function remove(e) {
            e.parentElement.parentElement.parentElement.remove();
        }
    </script>
@endsection
