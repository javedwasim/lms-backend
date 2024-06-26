@extends('layouts.master')
@section('content')
<style>
    .custombtn {
    padding-top: 31px;
}
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
<div class="card">
  <div class="card-header pb-0">
    <h5>Instruction
      <!-- <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('tutorial.index') }}"> Back</a> -->
    </h5>
  </div>
  <div class="card-body">
    <form id="validate" action="" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
      @csrf
      <div class="row fil_ters">
     

        <div class="col-md-12">
          <div class="form-group">
            <label>Description <code>*</code></label>
            <textarea class="form-control " required name="description" id="description" autocomplete="off">{{$course->instruction}}</textarea>

          </div>
        </div>
      </div> 
           
      <br />
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 text-right">
         
          <button type="submit" class="form-group btn btn-primary">Submit</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@section('script')  
  <script>  
  function addmoretestimonial()
  {
      str='<div class="row"> <div class="col-md-6"><div class="form-group"><label> Testimonial<code>*</code></label> <input type="text" class="form-control "  id="end_time" placeholder="Testimonial" name="testimonial[]" autocomplete="off"> </div>  </div> <div class="col-md-6"><div class="form-group custombtn"><a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div></div></div>';
      $(".testimonial").append(str);

  }
  function addmoretutor()
  {
      str='<div class="row">  <div class="col-md-6"> <div class="form-group"><label> Tutor Name<code>*</code></label><input type="text" class="form-control "  id="end_time" placeholder=" Tutor Name" name="tutor_name[]" autocomplete="off"></div>  </div>  <div class="col-md-4"> <div class="form-group"><label> Tutor Image<code>*</code></label><input type="file" class="form-control "  id="end_time" placeholder="Sub Title" name="tutor_image[]" autocomplete="off"></div>  </div>  <div class="col-md-2"> <div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div>  </div></div>';
 $(".addon").append(str);

  }
  function addmoreAddon()
  {
      str='<div class="row"> <div class="col-md-6"> <div class="form-group"><label> Book Addon<code>*</code></label><input type="text" class="form-control "  id="addon" placeholder="Book Addon" name="addon[]" autocomplete="off"></div> </div> <div class="col-md-6"><div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
      $(".addon").append(str);

  }
  function remove(e)
  {
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