<link href="https://cdn.jsdelivr.net/timepicker.js/latest/timepicker.min.css" rel="stylesheet"/>
@extends('layouts.master')
@section('content') 
<style>
  ._jw-tpk-container ol>li {
      float: left !important;
      display: inline-block !important;
  }
</style>

<div class="card">
  <div class="card-header pb-0">
    <h5>Report Issue
       
    </h5>
  </div>
  <div class="card-body"> 

      <div class="table-responsive">
          <table id="my_data_table" class="table table-bordered table-striped table-actions" >
              <thead>
                  <tr>
                      <th width="50">No</th>
                      <th>User</th>
                      <th>Email</th>
                      <th>Question</th>
                      <th>Options</th>
                      <th>Description</th>
                      <th>Date Time</th>
                    
                  </tr>
              </thead>
              <tbody>       
                    
              </tbody>
          </table>
      </div>
  </div> 
</div>



@endsection


@section('script')  
  <script src="https://cdn.jsdelivr.net/timepicker.js/latest/timepicker.min.js"></script>

  <script>  
      var URL = '{{url('/')}}';  
       

       
              $('#my_data_table').DataTable({
                  processing:true,
                  serverSide:true,
                  "order": [[3,'desc']],
                  "pageLength": 50,
                  ajax: URL+"/report_issue_api",
                  columns:[
                      {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                      {data:"username",name:"username"},
                      {data:"emailid",name:"emailid"},
                      {data:"questions",name:"questions",orderable:false},
                      {data:"options",name:"options",orderable:false},
                      {data:"desc",name:"desc",orderable:false},
                      {data:"datetime",name:"datetime"}
                  ]
              });
          
     
  </script> 
@endsection