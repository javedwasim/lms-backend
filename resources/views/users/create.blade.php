@extends('layouts.master')
<style>
   #inputPropImgRow .input-group {
      display: inline-flex;
   }

   #removePropImgRow {
      padding: 12px;
      margin-left: 3px;
   }
</style>

@section('content')

<div class="card">
   <div class="pb-0 card-header">
      <h5>Add New User
         <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('users.index') }}"> Back</a>
      </h5>
   </div>
   <div class="card-body">
      <form id="validate" action="{{ route('users.store') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
         @csrf
         <div class="row fil_ters">
            <div class="col-md-12">
               <div class="card-body">
                  <div class="row">

                     <div class="col-md-6">
                        <div class="form-group">
                           <label>User Name<sub>*</sub></label>
                           <input type="text" class="form-control validate[required]" value="{{ old('name') }}" id="name" placeholder="User Name" name="name" autocomplete="off">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Email<sub>*</sub></label>
                           <input type="email" class="form-control validate[required]" value="{{ old('email') }}" id="email" placeholder="Email" name="email" autocomplete="off">
                        </div>
                     </div>


                     <div class="col-md-6">
                        <div class="form-group mensa">
                           <label style="">Profile Image</label> <br>
                           <input type="file" class="form-control" name="profile_photo_path">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Status<sub>*</sub></label>
                           <select id="status" name="status" class="form-control">
                              <option value="">Select Status</option>
                              <option value="1">Active</option>
                              <option value="0">InActive</option>
                           </select>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Country Code<sub>*</sub></label>
                           <input type="text" class="form-control validate[required]" value="" id="password" placeholder="Country Code" name="country_code" autocomplete="off">
                        </div>
                     </div>


                     {{-- <div class="col-md-6" style="">
                        <div class="form-group">
                           <label>Role<sub>*</sub></label>
                           {!! Form::select('roles[]', $roles,3, array('class' => 'form-control','')) !!}
                        </div>
                     </div> --}}
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Password<sub>*</sub></label>
                           <input type="password" class="form-control validate[required]" value="" id="password" placeholder="Password" name="password" autocomplete="off">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Confirm Password<sub>*</sub></label>
                           <input type="password" class="form-control  validate[required]" value="" id="confirm-password" placeholder="Confirm Password" name="confirm-password" autocomplete="off">
                        </div>
                     </div>
                     <div class="clear:both"></div>
                     <div class="col-md-4">
                        <div class="form-group">
                           <label>Course Type<sub>*</sub></label>
                           <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="1">
                              <option value="">Select Course Type</option>
                              @foreach($CourseType as $val)
                              <option value="{{$val->id}}">{{$val->name}}</option>
                              @endforeach


                           </select>
                        </div>
                     </div>
                     <div class="col-md-4">
                        <div class="form-group">
                           <label>Enroll Course<sub>*</sub></label>
                           <select id="course1" name="course[]" class="form-control course" data-id="1">
                              <option value="">Select Course</option>
                              @foreach($course as $val)
                              <option value="{{$val->id}}">{{$val->course_name}}</option>
                              @endforeach


                           </select>
                        </div>
                     </div>
                     <div class="col-md-4">
                        <div class="form-group">
                           <label>Package<sub>*</sub></label>
                           <select id="package" name="package[]" class="form-control package1">



                           </select>
                        </div>
                     </div>
                     <div class="col-md-2"  style="    margin-top: 27px;">
                        <a href="javascript:" class="btn btn-primary" onclick="addmoreAddon()"> Add More</a>
                     </div>
                     <div id="morecontent">

                     </div>
                  </div>
               </div>
            </div>
         </div>
         <input type="hidden" id="noofcontent" value="1">
         <div class="row">
            <div class="text-right col-xs-12 col-sm-12 col-md-12">
               <button type="submit" class="btn btn-primary">Submit</button>
            </div>
         </div>
      </form>
   </div>
</div>

@endsection
@section('script')
<script>
   function addmoreAddon() {
      getAdd = $("#noofcontent").val();
      newAdd = parseInt(getAdd) + 1;
      str = '<div class="row"> <div class="col-md-4"> <div class="form-group"> <label> Course Type<sub>*</sub></label> <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="' + newAdd + '">   <option value="">Select Course Type</option>  @foreach($CourseType as $val)     <option value="{{$val->id}}">{{$val->name}}</option>     @endforeach     </select>     </div>       </div>   <div class="col-md-4"> <div class="form-group"> <label>Enroll Course<sub>*</sub></label> <select id="course'+ newAdd + '" name="course[]" class="form-control course" data-id="' + newAdd + '">   <option value="">Select Course</option>  @foreach($course as $val)     <option value="{{$val->id}}">{{$val->course_name}}</option>     @endforeach     </select>     </div>       </div>       <div class="col-md-4">  <div class="form-group"> <label>Package<sub>*</sub></label> <select id="package" name="package[]" class="form-control package' + newAdd + '" >  </select>  </div>  </div>  <div class="col-md-2" style="    margin-top: 27px;">  <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
      $("#morecontent").append(str);
      $("#noofcontent").val(newAdd);
      $(".course").on("change", function() {
         var id = $(this).val();
         var countId = $(this).data("id");
         $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getpackage")}}',
            data: {
               'id': id
            },
            success: function(data) {
               // elem = $(this).closest('div').find("select").html(); 
               str = '';
               for (const iterator of data) {
                  console.log(iterator);
                  str = str + '<option value="' + iterator.id + '"> ' + iterator.package_title + ' </option>';
               }
               console.log(str);
               $(".package" + countId).html(str);

            }
         });

      })
      $(".coursetype").on("change", function() {
         var id = $(this).val();
         var countId = $(this).data("id");
         $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getcourse")}}',
            data: {
               'id': id
            },
            success: function(data) {
               // elem = $(this).closest('div').find("select").html(); 
               str = '<option value="">Select Course </option>';
               for (const iterator of data) {
                  console.log(iterator);
                  str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
               }
               console.log(str);
               $("#course" + countId).html(str);

            }
         });

      })

   }

   function remove(e) {
      e.parentElement.parentElement.remove();
   }
   $(".course").on("change", function() {
      var id = $(this).val();
      var countId = $(this).data("id");
      $.ajax({
         type: "GET",
         dataType: "json",
         url: '{{url("/getpackage")}}',
         data: {
            'id': id
         },
         success: function(data) {
            // elem = $(this).closest('div').find("select").html(); 
            str = '';
            for (const iterator of data) {
               console.log(iterator);
               str = str + '<option value="' + iterator.id + '"> ' + iterator.package_title + ' </option>';
            }
            console.log(str);
            $(".package" + countId).html(str);

         }
      });

   })


   $(".coursetype").on("change", function() {
         var id = $(this).val();
         var countId = $(this).data("id");
         $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getcourse")}}',
            data: {
               'id': id
            },
            success: function(data) {
               // elem = $(this).closest('div').find("select").html(); 
               str = '<option value="">Select Course </option>';
               for (const iterator of data) {
                  console.log(iterator);
                  str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
               }
               console.log(str);
               $("#course" + countId).html(str);

            }
         });

      })
</script>
@endsection