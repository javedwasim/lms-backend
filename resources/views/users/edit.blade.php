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
      <h5>Edit {{ ucfirst($page_title) }}
         <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('users.index') }}"> Back</a>
      </h5>
   </div>
   <div class="card-body">
      <form id="validate" action="{{ route('users.update',$user->id) }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">

         @csrf
         @method('PUT')
         <input type="hidden" name="page_title" value="{{ $page_title }}" />
         <div class="row fil_ters">
            <div class="col-md-12">
               <div class="card-body">
                  <div class="row">

                     <div class="col-md-12">
                        <div class="form-group">
                           <label>Name<sub>*</sub></label>
                           <input type="text" class="form-control  validate[required]" value="{{ old('name') ? old('name') : $user->name }}" id="name" placeholder="Name" name="name" autocomplete="off">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>User Name<sub>*</sub></label>
                           <input type="text" class="form-control validate[required]" value="{{ old('name') ? old('name') : $user->name }}" id="name" placeholder="User Name" name="name" autocomplete="off">
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                           <label>Email<sub>*</sub></label>
                           <input type="email" class="form-control validate[required]" value="{{ old('name') ? old('name') : $user->email }}" id="email" placeholder="Email" name="email" autocomplete="off">
                        </div>
                     </div>
                     <div class="col-md-12">
                        <div class="form-group mensa">
                           <label style="">Profile Image</label>
                           @if($user->profile_photo_path)
                           <a href="{{ url('uploads/'.$user->profile_photo_path) }}" target="_balnk">
                              <img src="{{ url('uploads/'.$user->profile_photo_path) }}" style="width: 17px; margin-left:3px;" />
                           </a>
                           @endif
                           <br>
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
                           <input  maxlength="6" type="text" class="form-control validate[required]" value="{{ old('country_code') ? old('country_code') : $user->country_code }}" id="country_code" placeholder="Country Code" name="country_code" autocomplete="off">
                        </div>
                     </div>
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
                     <div >
                     <table class="table table-bordered table-actions">
                    <tr>
                        <th>Course Name</th>
                        <th>Package Name</th>
                     
                        <th>Expire Date</th>
                        <th>Action</th>
                     

                    </tr>
                    @if($subscription)
               @foreach(@$subscription as $val)
                    <tr>
                        <td>{{$val->course_name}}</td>
                        <td>{{$val->package_title}}</td>
                       
                        <td>{{$val->expiry_date}} 
                           <span><i class="fa fa-pencil" style="margin-left: 12px;" data-bs-toggle="modal" data-bs-target="#exampleModalMessage{{$val->id}}"></i></span>
                           
                        </td>
                        <td><a href="javascript:" class="btn btn-danger" onclick="unenrollcourse({{$val->particular_record_id}})">X</a></td>
                       

                    </tr>
                    @endforeach
                    @endif
                     </table>
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
                   

                  <!--    <div class="col-md-6">
                        <div class="form-group">
                           <label>Phone<sub>*</sub></label>
                           <input readonly maxlength="10" type="text" class="form-control validate[required]" value="{{ old('phone') ? old('phone') : $user->phone }}" id="phone" placeholder="Phone" name="phone" autocomplete="off">
                        </div>
                     </div> -->
                  </div>
               </div>
            </div>
            {{-- <div class="col-md-6">
               <div class="card-body">
                  <div class="form-group">
                     <label>Role<sub>*</sub></label>
                     {!! Form::select('roles[]', $roles, 3, array('class' => 'form-control','')) !!}
                  </div>
               </div>
            </div> --}}
         </div>
         <div class="row">
            <div class="text-right col-xs-12 col-sm-12 col-md-12">
               <button type="submit" class="btn btn-primary">Submit</button>
            </div>
         </div>
      </form>
   </div>
</div>
@if($subscription)
@foreach(@$subscription as $val)
<div class="modal fade" id="exampleModalMessage{{$val->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalMessageTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Update Expire Date</h5>
            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
            </button>
         </div>
         <form  method="post" action="{{route('updateExpiryDate')}}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
               <input type="hidden" name="modal_id" value="{{$val->id}}"> 
               <div class="form-group">
                  <label for="recipient-name" class="col-form-label">Expire Date:</label>
                  <input type="datetime-local" class="form-control" name="modal_expiry_date" value="{{date('Y-m-d H:i:s',strtotime($val->expiry_date))}}" id="modal_expiry_date">
               </div>
            </div>
            <div class="modal-footer">
               <input type="hidden" id="noofcontent" value="1">
               <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
               <button type="submit" class="btn bg-gradient-primary">Update</button>
            </div>
         </form>
      </div>
   </div>
</div>
@endforeach
@endif
@endsection
@section('script')
<script>
   function unenrollcourse(courseid)
   {
      $.ajax({
         type: "post",
         dataType: "json",
         url: '{{url("/unassignCourse")}}',
         data: {
            'course': courseid,
            'userids': "{{$user->id}}",
            '_token': "{{csrf_token()}}",
         },
         success: function(data) {
            window.location.reload();

         }
      });
   }
   function addmoreAddon() {
      getAdd = $("#noofcontent").val();
      newAdd = parseInt(getAdd) + 1;
      str = '<div class="row"> <div class="col-md-4"> <div class="form-group"> <label> Course Type<sub>*</sub></label> <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="' + newAdd + '">   <option value="">Select Course Type</option>  @foreach($CourseType as $val)     <option value="{{$val->id}}">{{$val->name}}</option>     @endforeach     </select>     </div>       </div> <div class="col-md-4"> <div class="form-group"> <label>Enroll Course<sub>*</sub></label> <select id="course" name="course[]" class="form-control course" data-id="' + newAdd + '">   <option value="">Select Course</option>  @foreach($course as $val)     <option value="{{$val->id}}">{{$val->course_name}}</option>     @endforeach     </select>     </div>       </div>       <div class="col-md-4">  <div class="form-group"> <label>Package<sub>*</sub></label> <select id="package" name="package[]" class="form-control package' + newAdd + '" >  </select>  </div>  </div>  <div class="col-md-2" style="    margin-top: 27px;">  <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
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