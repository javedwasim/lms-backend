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
            <h5>Update Book
                <!-- <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('tutorial.index') }}"> Back</a> -->
            </h5>
        </div>
        <div class="card-body">
            <form id="validate" action="/book/{{$book->id}}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row fil_ters">


                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Title<code>*</code></label>
                            <input type="text" class="form-control " id="title" placeholder="title" name="title"
                                autocomplete="off" value="{{ $book->title }}">

                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Sub Title<code>*</code></label>
                            <input type="text" class="form-control " id="subtitle" placeholder="Sub Title"
                                name="sub_title" autocomplete="off" value="{{ $book->title }}">

                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Background Gradient<code>*</code></label>
                            <input type="text" class="form-control " id="subtitle" placeholder="Background Gradient"
                                name="background_gradient" autocomplete="off" value="{{ $book->background_gradient }}">

                        </div>
                    </div>
                    <?php 
                    $startDate=$book->start_time;
                    $startDate=str_replace(" ","T",$startDate);
                    ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Start Date<code>*</code></label>
                            <input type="datetime-local" class="form-control " id="start_time" placeholder="Sub Title"
                                name="start_time" autocomplete="off" value="{{ $startDate }}">

                        </div>
                    </div>
                    <?php 
                    $endDate=$book->end_time;
                    $endDate=str_replace(" ","T",$endDate);
                    ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>End Date<code>*</code></label>
                            <input type="datetime-local" class="form-control " id="end_time" placeholder="Sub Title"
                                name="end_time" autocomplete="off" value="{{ $endDate }}">

                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Description <code>*</code></label>
                            <textarea class="form-control " name="description" id="description" autocomplete="off">{{ $book->description }}</textarea>

                        </div>
                    </div>
                </div>
                @if (!empty($book->addOns))
                    @foreach ($book->addOns as $val)
                        <div class="row fil_ters ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> Book Addon<code>*</code></label>
                                    <input type="text" class="form-control " id="addon" placeholder=" Book Addon"
                                        name="addon[]" autocomplete="off" value="{{ $val->description }}">

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group custombtn">
                                    <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a>

                                </div>
                            </div>

                        </div>
                    @endforeach
                @endif

                <div class="row fil_ters ">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label> Book Addon<code>*</code></label>
                            <input type="text" class="form-control " id="addon" placeholder=" Book Addon"
                                name="addon[]" autocomplete="off">

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group custombtn">
                            <a href="javascript:" class="btn btn-primary" onclick="addmoreAddon()">Add More</a>

                        </div>
                    </div>
                    <div class="addon"></div>
                </div>

                @if (!empty($book->tutor))
                    @foreach ($book->tutor as $val)
                        <div class="row fil_ters ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> Tutor Name<code>*</code></label>
                                    <input type="text" class="form-control " id="end_time" placeholder=" Tutor Name"
                                        name="tutor_name_old[{{ $val->id }}]" autocomplete="off" value="{{ $val->tutor_name }}">

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label> Tutor Image<code>*</code></label>
                                    <input type="file" class="form-control " id="end_time" placeholder="Sub Title"
                                        name="tutor_image_old[{{ $val->id }}]" autocomplete="off">
                                    <img src="{{ asset('uploads') }}/{{ $val->tutor_image }}" width="100">

                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group custombtn">
                                    <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a>

                                </div>
                            </div>
                            <input type="hidden" name="tutor_id[]" value="{{$val->id}}">
                          

                        </div>
                    @endforeach
                @endif
                <div class="row fil_ters ">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label> Tutor Name<code>*</code></label>
                            <input type="text" class="form-control " id="end_time" placeholder=" Tutor Name"
                                name="tutor_name[]" autocomplete="off">

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Tutor Image<code>*</code></label>
                            <input type="file" class="form-control " id="end_time" placeholder="Sub Title"
                                name="tutor_image[]" autocomplete="off">

                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group custombtn">
                            <a href="javascript:" class="btn btn-primary" onclick="addmoretutor()">Add More</a>

                        </div>
                    </div>
                    <div class="tutor"></div>
                </div>
              
            
                <div class="row fil_ters ">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label> Testimonial<code>*</code></label>
                            <input type="text" class="form-control " id="end_time" placeholder="testimonial"
                                name="testimonial[]" autocomplete="off" value="{{@$book->testimonial[0]->testimonial}}">

                        </div>
                    </div>

                  {{--   <div class="col-md-6">
                        <div class="form-group custombtn">
                            <a href="javascript:" class="btn btn-primary" onclick="addmoretestimonial()">Add More</a>

                        </div>
                    </div>
                    <div class="testimonial"></div> --}}
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Order<code>*</code></label>
                        <input type="number" class="form-control " id="position" placeholder="Enter Order" name="position"
                             value="{{ $book->position }}">

                    </div>
                </div>
                <br />
                <div class="row">
                    <div class="text-right col-xs-12 col-sm-12 col-md-12">
                      <input type="hidden" value="{{@$book->id}}" name="id">
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
                '<div class="row"> <div class="col-md-6"> <div class="form-group"><label> Book Addon<code>*</code></label><input type="text" class="form-control "  id="addon" placeholder="Book Addon" name="addon[]" autocomplete="off"></div> </div> <div class="col-md-6"><div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
            $(".addon").append(str);

        }

        function remove(e) {
            e.parentElement.parentElement.parentElement.remove();
        }

  CKEDITOR.replace('description',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
    </script>
@endsection
