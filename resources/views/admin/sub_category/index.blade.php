@extends('layouts.master')
@section('content') 
<div class="card">
  <div class="card-header pb-0">
    <h5>Sub Category
        <button type="button" class="btn btn-icon bg-gradient-secondary float-right reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
          Add
        </button>

    </h5>
  </div>
  <div class="card-body"> 
  <div class="row">

<div class="col-md-3">
  <div class="form-group">
    <label>Status<code>*</code></label>
    <select class="form-control  validate[required]" id="status" name="label" onchange="redrawTable()">
      <option value="">Select Status</option>
      <option value="1">Active</option>
      <option value="0">Inactive</option>


    </select>
  </div>
</div>

</div>
      <div class="table-responsive">
          <table id="my_data_table" class="table table-bordered table-striped table-actions" >
              <thead>
                  <tr>
                      <th width="50">No</th>
                      <th>Category</th>
                      <th>Sub Category</th>
                      <th width="100">Status</th> 
                      <th width="90">Create Date</th>
                      <th width="180">Action</th>
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
      <form id="validate" action="{{ route('sub_category.store') }}"  class="form-horizontal comm_form" method="POST" role="form" >
        @csrf
        <div class="modal-body"> 
            <div id="message_box"></div>
            <input type="hidden" id="record_id" class="form-control" name="record_id" value="" />
            <div class="form-group">
              <label class="col-form-label">Name <code>*</code>:</label>
              <input type="text" class="form-control  validate[required]" name="sub_category_name" id="frm_sub_category_name" value="" >
            </div> 
            <div class="form-group">
              <label for="message-text" class="col-form-label">Category <code>*</code>:</label> 
              <select name="category_name"  id="frm_category" class="form-control validate[required]" >
                <option value="">Select Category</option>
                @foreach($get_category as $cat_val)
                    <option value="{{ $cat_val->id }}">{{ $cat_val->category_name }}</option>
                @endforeach 
              </select> 
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
          <label class="col-form-label">Name:</label>
          <span class="form-control" id="show_sub_category_name" ></span>
        </div> 
        <div class="form-group">
          <label for="message-text" class="col-form-label">Category <code>*</code>:</label>  
          <span class="form-control" id="show_category_name" ></span> 
        </div>
        <div class="form-group">
          <label for="message-text" class="col-form-label">Status:</label> 
          <span class="form-control" id="show_status" ></span>
        </div> 
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button> 
      </div> 
    </div>
  </div>
</div>

@endsection


@section('script')  
  <script>  
      var URL = '{{url('/')}}';  
      function redrawTable() {
      console.log("R");
        $('#my_data_table').DataTable().ajax.reload();
    }
       
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
              url: URL+"/sub_category_get_data",
              data:form_data, 
              enctype: 'multipart/form-data',
              processData: false,  // Important!
              contentType: false,
              cache: false,
              dataType: "JSON", 
              success: function(response){   
                  if(response.result.length>0){ 
                    sub_category_name = response.result[0].sub_category_name;
                    category_id = response.result[0].category_id;
                    category_name = response.result[0].category_name;
                    status_name = response.result[0].status_name;  
  
                    $('#show_sub_category_name').text(sub_category_name);
                    $('#show_category_name').text(category_name);
                    $('#show_status').text(status_name);  
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

          var form_data = new FormData();
          form_data.append("record_id",form_data_id); 
           
          $.ajax({
              type:"POST", 
              url: URL+"/sub_category_get_data",
              data:form_data, 
              enctype: 'multipart/form-data',
              processData: false,  // Important!
              contentType: false,
              cache: false,
              dataType: "JSON", 
              success: function(response){   
                  if(response.result.length>0){ 
                 
                      id = response.result[0].id;
                      sub_category_name = response.result[0].sub_category_name;
                      category_name = response.result[0].category_name;
                      category_id = response.result[0].category_id;
                      status = response.result[0].status; 

                      $('#record_id').val(id);
                      $('#frm_sub_category_name').val(sub_category_name);
                      $('#frm_category').val(sub_category_name);
                      
                      $('#frm_category').val(category_id);
                      $('#frm_status').val(status);

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
              $('#validate')[0].reset();
          });

          if($('#my_data_table').length > 0){
              $('#my_data_table').DataTable({
                  processing:true,
                  serverSide:true,
                  "ordering": false,
                  "pageLength": 50,
                  "ajax": {
                    "url": URL + "/sub_category_call_data",
                    "type": "get",
                    "data": function(d) {
                        d.status = $("#status").val();
                      


                    }
                },
                  // ajax: URL+"/sub_category_call_data",
                  columns:[
                      {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                      {data:"category_name",name:"category_name"},
                      {data:"sub_category_name",name:"sub_category_name"},
                      {data:"status",name:"status"},
                      {data:"created_at",name:"created_at"},
                      {data:"action",name:"DT_RowIndex",orderable:false},
                  ]
              });
          }  
      });
  </script> 
@endsection