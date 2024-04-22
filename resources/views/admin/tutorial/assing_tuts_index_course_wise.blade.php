@extends('layouts.master')


@section('content')   
<?php 
$course = \App\Models\Course::find($courseid);
?> 
    <div class="card">
      <div class="card-header pb-0">
        <h5>Assign Tutorial For {{ ucwords(str_replace("_"," ",$course->course_name)) }}
            <a style="margin-left: 15px;" href="{{ url('course') }}" class="btn btn-default btn-icon bg-gradient-secondary float-right">Back</a>
            <button class="btn btn-icon bg-gradient-primary float-right" id="assign_btn">Submit</button>
            <a href="{{url('unassignquestionAll/')}}/{{ $courseid }}/tutorail" class="btn btn-danger float-right" onclick="return confirm('Are you really want to unassign all question')"> UnAssign All </a>
        </h5>
      </div>
      <div class="card-body"> 
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="category" name="category" onchange="redrawTable()">
                        <option value="">Select Category</option>
                        @foreach ($getCategory as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div> 
          {{--   <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_id" name="course_id" onchange="redrawTable()">
                        <option value="">Select Course</option>
                        @foreach ($allCourse as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}
        </div>
          <form id="assign_form" action="{{ url('tutorial_common_assign_submit_course_wise') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" > 
          <input type="hidden" name="course_id" id="course_id" value="{{ $courseid }}" />
              @csrf  

              <div class="table-responsive">
                  <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                      <thead>
                          <tr>
                            <th width="50">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th> 
                            <th>Category</th>
                            <th>Tutorial Name</th>  
                            <th width="180">Action</th>                              
                          </tr>
                      </thead>
                      <tbody>       
                            
                      </tbody>
                  </table>
              </div>
          </form>
      </div> 
    </div>
@endsection

 @section('script')    
    <script> 
     function redrawTable()
        {
            $('#my_data_table').DataTable().ajax.reload();
        }
        $(document).on('click', '#select_all', function (){ 
            this.value = this.checked ? 1 : 0;
            if(this.checked){
                $('.assign_que_id').prop('checked', true);
            }else{
                $('.assign_que_id').prop('checked', false);

            }    
        });
        
        $(document).ready(function() {  

            var URL = '{{url('/')}}';
            
            
            $("#assign_btn").click(function(){
                $("#assign_form").submit();
            });
  
            if($('#my_data_table').length > 0){
                $('#my_data_table').DataTable({
                    aLengthMenu: [
                        [5,10,50,100,200,500,-1],
                        [5,10,50,100,200,500,"All"]
                    ],
                    iDisplayLength: -1,
                    processing:true,
                    serverSide:true,
                    "order": [[3,'desc']],
                    "pageLength": 5, 
                    ajax: {
                        "url": URL+"/call_tutorial_list_data_course_wise",
                        "type": "GET",
                        "data": function(d) {
                            d.course_id = $("#course_id").val();
                            d.page_type = $("#page_type").val();
                            d.category = $("#category").val();
                            // d.course_id = $("#course_id").val();

                        }
                    },
                    columns:[
                        {data:"assign_checkbx",name:"assign_checkbx",orderable:false}, 
                        {data:"category_name",name:"category_name"}, 
                        {data:"chapter_name",name:"chapter_name"}, 
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            } 
        });
     </script>  
@endsection