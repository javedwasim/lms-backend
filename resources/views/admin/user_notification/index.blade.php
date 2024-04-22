@extends('layouts.master')


@section('content')    
    <div class="card">
      <div class="card-header pb-0">
        <h5>User Notification   
        </h5>
      </div>
      <div class="card-body"> 

          <div class="table-responsive">
              <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                  <thead>
                      <tr>
                        <th width="140px">No</th>
                        <th>User Name</th> 
                        <th>Message</th> 
                        <th>Create Date</th>
                        <th width="170px">Action</th>
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
    <script>

        $(document).ready(function() {  

            var URL = '{{url('/')}}';
  
            if($('#my_data_table').length > 0){
                $('#my_data_table').DataTable({
                    processing:true,
                    serverSide:true,
                    "ordering": false,
                    "pageLength": 5,
                    ajax: URL+"/user_notification_call_data",
                    columns:[
                        {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                        {data:"user_id",name:"user_id"}, 
                        {data:"message",name:"message"}, 
                        {data:"created_at",name:"created_at"},
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            } 
        });
     </script>  
@endsection