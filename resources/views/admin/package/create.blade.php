@extends('layouts.master') 
@section('content')  
    <style>
        tr{
            background: whitesmoke;
        }
    </style>
     <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
    <div class="card">
      <div class="pb-0 card-header">
        <h5>Add Package   
            <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('package.index') }}?type=<?php echo @$_GET['type']; ?>"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ route('package.store') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                <input type="hidden" value="{{@$_GET['type']?$_GET['type']:1}}" name="package_for">
                <div class="row fil_ters"> 
                    @if(@$_GET['type']=="seminar")
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Seminar<code>*</code></label> 
                          <select class="form-control  validate[required]" id="course_select" name="course">
                              <option value="">Select Seminar</option>
                              @foreach($getSeminar as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->title }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                    @elseif(@$_GET['type']=="flashcard")

                    <div class="col-md-6">
                        <div class="form-group">
                          <label>FlashCard<code>*</code></label> 
                          <select class="form-control  validate[required]" id="course_select" name="course">
                              <option value="">Select FlashCard</option>
                              @foreach($getFlashCard as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->title }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                    @elseif(@$_GET['type']=="book")
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Book<code>*</code></label> 
                          <select class="form-control  validate[required]" id="course_select" name="course">
                              <option value="">Select Book</option>
                              @foreach($getBook as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->title }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>

                    @else
                    <div class="col-md-6">
                        <div class="form-group">
                           <label>Course Type<sub>*</sub></label>
                           <select id="coursetype" name="course_type_id" class="form-control coursetype" data-id="1">
                              <option value="">Select Course Type</option>
                              @foreach($CourseType as $val)
                              <option value="{{$val->id}}">{{$val->name}}</option>
                              @endforeach


                           </select>
                        </div>
                     </div>
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Course<code>*</code></label> 
                          <select class="form-control  validate[required]" id="course" name="course">
                              <option value="">Select Course</option>
                              @foreach($getCourse as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->course_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Free Course<code>*</code></label> 
                          <select class="form-control  validate[required]  multiple-selected" id="freecourse" name="freecourse[]" multiple>
                              <option value="">Select Course</option>
                              @foreach($getCourse as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->course_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>

                    @endif
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Package Type<code>*</code></label> 
                          <select class="form-control  validate[required]" id="packagetype" name="packagetype">
                              <option value="">Package Type</option>
                              <option value="free">Free</option>
                              <option value="onetime">One Time Specific Date</option>
                              <option value="subscription">Subscription</option>
                              <option value="subscription_onetime">One Time Specific Month</option>
                             
                          </select>
                        </div> 
                     </div>

                     <div class="col-md-6 packagemonth">
                        <div class="form-group">
                          <label>Package For Month<code>*</code></label>
                          <input type="in" class="form-control  validate[required] decimal_number" name="package_for_month" id="frm_package_for_month" maxlength="2" value="" >
                        </div> 
                     </div>
                     <div class="col-md-6 packageexpire">
                        <div class="form-group">
                          <label>Package Expire Date<code>*</code></label>
                          <input type="date" class="form-control decimal_number" name="expire_date" id="date"  value="" >
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Price <code>*</code>:</label> 
                          <input type="text" class="form-control  validate[required] decimal_number" name="price" id="frm_package_price" value="" >
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Status <code>*</code>:</label> 
                          <select name="status"  id="frm_status" class="form-control validate[required]" >
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                          </select>
                        </div> 
                     </div>  
                     <div class="col-md-12">
                        <div class="form-group">  
                          <label>Package Title<code>*</code></label>
                          <input type="text" class="form-control  validate[required]" name="package_title" id="frm_package_title" value="" > 
                        </div> 
                     </div>
                     <div class="col-md-12">
                        <div class="form-group">  
                          <label>Description<code>*</code></label>
                          <textarea class="form-control  validate[required]" name="package_description" id="frm_description" ></textarea>  
                        </div> 
                     </div> 

                     <div class="col-md-12">
                        <div class="form-group">  
                          <label>Package Features<code>*</code></label>
                         
                        </div> 
                     </div> 
                     <div class="col-md-12">
                        <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>Value</th>
                                <th>Icon Color Status</th>
                                <th>Action</th>
                            </tr>
                            <tr>  
                                <td><input type="text" name="multi_pack_value[]" placeholder="Enter your Name" class="form-control" required /></td>  
                                <td>
                                    <select name="multi_pack_status[]" id="" class="form-control" required>
                                        <option value="1">Active</option>
                                        <option value="2">Inactive</option>
                                    </select>
                                </td>
                               
                                <td><button type="button" name="add" id="add" class="btn btn-success">Add More</button></td>  
                            </tr>  
                        </table> 
                     </div>

                </div> <br/> 
              
                <div class="row">
                    <div class="text-right col-xs-12 col-sm-12 col-md-12">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
      </div> 
    </div>
     
@endsection

@section('script') 
<script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.js"></script>
<script type="text/javascript">
   
   $("#packagetype").on("change",function(){
      var packagetype=$("#packagetype").val(); 
      $(".packagemonth").hide();
      $(".packageexpire").hide();
      if(packagetype=="onetime")
      {
        $(".packageexpire").show();
      }
      if(packagetype=="subscription" || packagetype=="subscription_onetime")
      {
        // $(".packageexpire").show();
        $(".packagemonth").show();
      }
      if(packagetype=="free")
      {
        $("#frm_package_price").attr("readonly",true);
        $("#frm_package_price").val(0);
      }
   })
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;
   
        $("#dynamicTable").append(`<tr>  
                                <td><input type="text" name="multi_pack_value[]" placeholder="Enter your Name" class="form-control" required /></td>  
                                <td>
                                    <select name="multi_pack_status[]" id="" class="form-control" required>
                                        <option value="1">Active</option>
                                        <option value="2">Inactive</option>
                                    </select>
                                </td>
                               
                                <td><button type="button" class="text-white btn btn-danger remove-tr">Remove</button></td>
                            </tr> `);
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    });  
   
</script>
    <script>

        
        $(document).ready(function(){

          var highlits_select = new SlimSelect({
            select: '.multiple-selected',
            //showSearch: false,
            placeholder: 'Select',
            deselectLabel: '<span>&times;</span>',
            hideSelectedOption: true,
        })
        
            var URL = '{{url('/')}}';

         //   CKEDITOR.replace('frm_description');
            
   

            

            /*$("#course_select").change(function(){ 
                   course_id = $(this).val(); 
                   
                   $.ajaxSetup({
                       headers: { 
                           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                       }
                   }); 
     
                   var form_data = new FormData();
                   form_data.append("course_id",course_id);  
                     
                    $.ajax({
                        type:"POST", 
                        url: URL+"/get_ajax_course_question",
                        data:form_data, 
                        enctype: 'multipart/form-data',
                        processData: false,  // Important!
                        contentType: false,
                        cache: false,
                        dataType: "JSON", 
                        success: function(response){   
                            console.log(response.data.question_list);
                            htm_que = ''; htm_tue='';
                            question_list = response.data.question_list;
                            tutorial_list = response.data.tutorial_list;

                            if(question_list.length>0){ 
                                for(i=0;i<question_list.length;i++){ 
                                    htm_que +='<tr><th><input type="checkbox" id="customRadio_'+question_list[i].id+'" name="question_id[]" value="'+question_list[i].id+'" class="custom-control-input"></th><td>'+question_list[i].question_name+'</td></tr>';
                                }  
                            }
                          
                            $('#question_div').html(htm_que);

                            if(tutorial_list.length>0){ 
                                for(i=0;i<tutorial_list.length;i++){  
                                    htm_tue +='<tr><th><input type="checkbox" id="customRadio_'+tutorial_list[i].id+'" name="tutorial_id[]" value="'+tutorial_list[i].id+'" class="custom-control-input"></th><td>'+tutorial_list[i].chapter_name+'</td></tr>';
                                }  
                            }
                            $('#tutorial_div').html(htm_tue);
                        }
                   });
            });*/
        });
        $("#coursetype").on("change", function() {
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
               str = '';
               for (const iterator of data) {
                  console.log(iterator);
                  str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
               }
               console.log(str);
               $("#course").html(str);

            }
         });

      })       
    </script>
@endsection