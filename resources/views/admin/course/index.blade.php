@extends('layouts.master')
@section('content') 
<div class="card">
  <div class="card-header pb-0">
    <h5>Course  
        <button type="button" class="btn btn-icon bg-gradient-secondary float-right reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
          Add
        </button> 
      
    </h5>
  </div>
  <div class="card-body"> 

      <div class="table-responsive">
          <table id="my_data_table" class="table table-bordered table-striped table-actions" >
              <thead>
                  <tr>
                      <th width="5%">No</th>
                      <th width="15%">Course Id</th>
                      <th width="15%">Course Type</th>
                      <th width="15%">Course Name</th>
                      <th width="5%">Enrolled<br> Users</th>
                      <th width="5%">Status</th> 
                      <th width="5%">Total<br> Hours</th>
                      <th width="5%">Ordering</th>
                      <th width="5%">Popup <br>Display</th>

                      <th width="5%">Create<br> Date</th>
                      
                      <th width="10%" class="text-center">Assign<br> T/Q</th>
                      <th width="10%" class="text-center">Test<br> Mode</th>
                  
                      <th width="20%">Action</th>
                  </tr>
              </thead>
              <tbody>       
                    
              </tbody>
          </table>
      </div>
  </div> 
</div>
  
<div class="modal fade" id="addForm" tabindex="-1" role="dialog" >
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add/Edit Form</h5>
        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <form id="validate" action="{{ route('course.store') }}"  class="form-horizontal comm_form" method="POST" role="form" >
        @csrf
        <div class="modal-body"> 
            <div id="message_box"></div>
            <input type="hidden" id="record_id" class="form-control" name="record_id" value="" />
            <div class="form-group">
              <label class="col-form-label">Course Type<code>*</code>:</label>
              <select name="course_type_id"  id="course_type_id" class="form-control validate[required]" >
                <option value="">Select Course Type</option>
                  @foreach($courseType as $val)
                  <option value="{{$val->id}}">{{$val->name}}</option>
                  @endforeach
              </select> 
            </div>
            <div class="form-group">
              <label class="col-form-label">Course Name <code>*</code>:</label>
              <input type="text" class="form-control  validate[required]" name="course_name" id="frm_course_name" value="" >
            </div>

            <div class="form-group">
              <label class="col-form-label">Icon Image : <span id="frm_course_image_edit"></span></label> 
              <input type="file" class="form-control" name="course_image" id="frm_course_image" value="" >
              <span class="img-size-text">Maximum Upload  size  300*200</span>
            </div> 
            <div class="form-group">
              <label class="col-form-label">Video Image : <span id="frm_video_image_edit"></span></label> 
              <input type="file" class="form-control" name="video_image" id="frm_video_image" value="" >
              <span class="img-size-text">Maximum Upload  size  300*200</span>
            </div>
            <div class="form-group">
              <label class="col-form-label">Categories<code>*</code>:</label>
              
              {!! Form::select('categories[]',\App\Models\Category::where('status',1)->pluck('category_name','id')->toArray(),null,['class'=>'select2 form-control ','id'=>'categories','required'=>true,'multiple'=>true,'style'=>'width:100%']) !!}
            </div>
            <div class="form-group">
              <label class="col-form-label">Total Video Hours <code>*</code>:</label>
              <input type="number" min="1" class="form-control  validate[required]" name="total_hours" id="total_hours" value="" >
            </div>
            <div class="form-group">
              <label class="col-form-label">Sort <code>*</code>:</label>
              <input type="number" min="1" class="form-control  validate[required]" name="sort" id="sort" value="" >
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_modal"  id="is_modal" value="1">
              <label class="form-check-label" for="is_modal">
                Popup Status
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_tutorial"  id="is_tutorial" value="1">
              <label class="form-check-label" for="is_tutorial">
               Tutorial
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_question"  id="is_question" value="1">
              <label class="form-check-label" for="is_question">
               Question
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_test"  id="is_test" value="1">
              <label class="form-check-label" for="is_test">
               Test
              </label>
            </div>
            <div class="form-group">
              <label for="message-text" class="col-form-label">Status <code>*</code>:</label> 
              <select name="status"  id="frm_status" class="form-control validate[required]" >
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select> 
            </div> 
        </div>
        <div class="modal-footer">
          <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="common_form_submit" class="btn bg-gradient-primary">Submit</button>
        </div>
      </form> 
    </div>
  </div>
</div>

<div class="modal fade" id="modal_view_dt" tabindex="-1" role="dialog" >
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">View Details</h5>
        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div> 
      <div class="modal-body">  
        <div class="form-group">
          <label class="col-form-label">Course Name:</label>
          <span class="form-control" id="show_course_name" ></span>
        </div>
        <div class="form-group">
          <label class="col-form-label">Course Image:</label>
          <span class="form-control img_box" id="show_course_image" ></span>
        </div> 
        <div class="form-group">
          <label class="col-form-label">Video Image:</label>
          <span class="form-control img_box" id="show_video_image" ></span>
        </div> 
        <div class="form-group">
          <label class="col-form-label">Categories:</label>
          <span class="form-control categories" id="categoriesdata" ></span>
        </div> 
        <div class="form-group">
          <label for="message-text" class="col-form-label">Total Video Hours:</label> 
          <span class="form-control total_hours" id="total_hours" ></span>
        </div> 
        <div class="form-group">
          <label for="message-text" class="col-form-label">Sort:</label> 
          <span class="form-control sort" id="sort" ></span>
        </div> 
        <div class="form-group">
          <label for="message-text" class="col-form-label">Popup Status:</label> 
          <span class="form-control is_modal" id="is_modal" ></span>
        </div> 
        
        <div class="form-group">
          <label for="message-text" class="col-form-label">Status:</label> 
          <span class="form-control" id="show_course_status" ></span>
        </div> 
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button> 
      </div> 
    </div>
  </div>
</div>
<input type="hidden" id="json" data-values="{{ \App\Models\Category::pluck('category_name','id')->toJson() }}">

@endsection


@section('script')  
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>  
  var jsondata = $('#json').attr('data-values');
  $(document).ready(function() {
    $('.select2').select2();
});
      var URL = '{{url('/')}}';  
       
      $(document).on("click", '.view_in_modal', function(e){   
          
          $.ajaxSetup({
              headers: { 
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          }); 

          form_data_id = $(this).data('id');    

          var form_data = new FormData();
          form_data.append("record_id",form_data_id); 
           
          $.ajax({
              type:"POST", 
              url: URL+"/course_get_data",
              data:form_data, 
              enctype: 'multipart/form-data',
              processData: false,  // Important!
              contentType: false,
              cache: false,
              dataType: "JSON", 
              success: function(response){   
                  if(response.result.length>0){ 
                  
                    course_name = response.result[0].course_name;
                    status_name = response.result[0].status_name;  
                    sort        = response.result[0].sort;
                    course_image = response.result[0].course_image;  
                    total_hours        = response.result[0].total_hours;
                    categories = response.category;
                    is_modal        = response.result[0].is_modal;
                 
                    
                    console.log('data',{
                      total_hours,is_modal,sort
                    })
                    if(course_image!=''){
                        cat_img_dt = '<a target="_blank" href="'+URL+'/uploads/'+course_image+'" ><img src="'+URL+'/uploads/'+course_image+'" style="width:30px;" /></a>';
                        $('#show_course_image').html(cat_img_dt);
                    }

                    video_image = response.result[0].video_image;  

                    if(video_image!=''){
                        vid_img_dt = '<a target="_blank" href="'+URL+'/uploads/'+video_image+'" ><img src="'+URL+'/uploads/'+video_image+'" style="width:30px;" /></a>';
                        $('#show_video_image').html(vid_img_dt);
                    }

                    $('#show_course_name').text(course_name);
                    $('#show_course_status').text(status_name);  
                    $('.sort').html(sort); 
                    
                    if(typeof is_modal === 'object'  || is_modal === '0' || is_modal === 'null' || is_modal == 0)
                    {
                      $('.is_modal').html('<p>Inactive</p>');    
                    }else{
                      $('.is_modal').html('<p>Active</p>');    
                    }
                    
                   
                    
                    $('.total_hours').html(`<p>${response.result[0].total_hours}</p>`);  
                    
                    var string = '';
                    for(let y in categories)
                    {
                      string += categories[y].category_name + '<br>';    
                    }

                   
                    $('#categoriesdata').html(string); 
                     
                    
                  }  
              }
          });
      });

      $(document).on("click", '.form_data_act', function(e){  // worked with dynamic loaded jquery content
          
          $.ajaxSetup({
              headers: { 
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          }); 

          form_data_id = $(this).data('id');   

          $('#record_id').val(form_data_id);
          $('#banner_record_id').val(form_data_id);

          var form_data = new FormData();
          form_data.append("record_id",form_data_id); 
           
          $.ajax({
              type:"POST", 
              url: URL+"/course_get_data",
              data:form_data, 
              enctype: 'multipart/form-data',
              processData: false,  // Important!
              contentType: false,
              cache: false,
              dataType: "JSON", 
              success: function(response){   
                  if(response.result.length>0){ 

                      id = response.result[0].id;
                      course_name = response.result[0].course_name;       
                      course_type_id = response.result[0].course_type_id;       
                      sort = response.result[0].sort;
                      status = response.result[0].status; 
                      total_hours = response.result[0].total_hours; 
                      categories = response.result[0].categories; 
                      is_modal = response.result[0].is_modal; 
                      is_tutorial        = response.result[0].is_tutorial;
                    is_question        = response.result[0].is_question;
                    is_test        = response.result[0].is_test;
                      
                      
                      $('#record_id').val(id);
                      $('#frm_course_name').val(course_name);
                      $('#frm_status').val(status);
                      $('#course_type_id').val(course_type_id);
                      $('#frm_status').val(status);
                      $('#sort').val(sort);
                      $('#total_hours').val(total_hours);
                     
                      if(typeof is_modal === 'object'  || is_modal === '0' || is_modal === 'null' || is_modal == 0)
                    {
                       
                        $('#is_modal').removeAttr('checked'); 
                    }else{
                      
                        $('#is_modal').attr('checked','checked');    
                    }

                    if(typeof is_tutorial === 'object'  || is_tutorial === '0' || is_tutorial === 'null' || is_tutorial == 0)
                    {
                       
                        $('#is_tutorial').removeAttr('checked'); 
                    }else{
                      
                        $('#is_tutorial').attr('checked','checked');    
                    }
                    if(typeof is_question === 'object'  || is_question === '0' || is_question === 'null' || is_question == 0)
                    {
                       
                        $('#is_question').removeAttr('checked'); 
                    }else{
                      
                        $('#is_question').attr('checked','checked');    
                    }
                    if(typeof is_test === 'object'  || is_test === '0' || is_test === 'null' || is_test == 0)
                    {
                       
                        $('#is_test').removeAttr('checked'); 
                    }else{
                      
                        $('#is_test').attr('checked','checked');    
                    }


                    
                      
                      
                      
                      var selectedOptions = response.category.map((c) => {
                        return c.id;
                      })

                      for(var i in selectedOptions) {
                            var optionVal = selectedOptions[i];
                            $("#categories").find("option[value="+optionVal+"]").prop("selected", "selected");
                        }
                        $('#categories').change();
                      
                      course_image = response.result[0].course_image;  

                      if(course_image!=''){
                          cat_img_dt = '<a target="_blank" href="'+URL+'/uploads/'+course_image+'" ><img src="'+URL+'/uploads/'+course_image+'" style="width:30px;" /></a>';
                          $('#frm_course_image_edit').html(cat_img_dt);
                      }

                      video_image = response.result[0].video_image;  

                      if(video_image!=''){
                          vid_img_dt = '<a target="_blank" href="'+URL+'/uploads/'+video_image+'" ><img src="'+URL+'/uploads/'+video_image+'" style="width:30px;" /></a>';
                          $('#frm_video_image_edit').html(vid_img_dt);
                      }
                       
                  }
                  $('#message_box').html(''); 

              }
          });
      });

      $(document).ready(function() { 
           

          $(".comm_form").submit(function(e) {
              
              e.preventDefault(); 

              $.ajaxSetup({
                  headers: { 
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  }
              }); 

              var form = $(this);
              var url = form.attr('action');
              
              errorFlag = true;
              $(this).find("input, select, textarea").each(function () {

                  if ($(this).hasClass("validate[required]") && $(this).val() == "") {

                      $(this).addClass("is-invalid"); 
                      errorFlag = false;
                  }
              });

              $('#message_box').html('');

              if(errorFlag){  
                  $.ajax({ 
                      type:"POST", 
                      url: url,
                      data:new FormData(this), 
                      enctype: 'multipart/form-data',
                      processData: false,  // Important!
                      contentType: false,
                      cache: false,
                      dataType: "JSON",  
                      success: function(response)
                      {    
                           if(response.status=='2' || response.status=='1' || response.status=='0'){    

                              if(response.status=='2')
                                  alert_type = 'alert-warning';
                              else if(response.status=='1'){
                                  alert_type = 'alert-success';
                                  $(this).removeClass('is-invalid');
                              }else
                                   alert_type = 'alert-danger';

                              var htt_box = '<div class="alert '+alert_type+' " role="alert">'+
                                              '<button type="button" class="close" data-dismiss="alert">'+
                                              'x</button>'+ response.message+'</div>';

                              $('#message_box').html(htt_box);

                              $('#my_data_table').DataTable().ajax.reload(); 
                              $('.reset_form').click();
                              // setTimeout(function(){ $('#message_box').html(''); }, 6000);
                          } 
                      }
                   }); 
              }    
              
          });

          $(".reset_form").click(function(){
              $('#record_id').val('');
              $('#frm_course_image_edit').html(''); 
              $('#frm_video_image_edit').html('');    
              $('#sort').val('');
              $('#validate')[0].reset();
          });

          if($('#my_data_table').length > 0){
              $('#my_data_table').DataTable({
                  processing:true,
                  serverSide:true,
                  "ordering": false,
                  "pageLength": 50,
                  ajax: URL+"/course_call_data",
                  columns:[
                      {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                      {data:"course_id",name:"course_id"},
                      {data:"courseType",name:"courseType"},
                      {data:"course_name",name:"course_name"},
                      {data:"enrolled_user",name:"enrolled_user","className": "text-center"},
                      {data:"status",name:"status"},
                      {data:"total_hours",name:"total_hours"},
                      {data:"sort",name:"sort"},
                      {data:"is_modal",name:"is_modal"},
                      {data:"created_at",name:"created_at"},
                      
                      {data:"assign_course",name:"assign_course"},
                      {data:"assign",name:"assign"},
                      {data:"action",name:"DT_RowIndex",orderable:false},
                  ]
              });
          }  
      });
  </script> 
@endsection