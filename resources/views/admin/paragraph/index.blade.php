@extends('layouts.master')


@section('content')    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Paragraph  
            @can('paragraph-create')
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('paragraph.create') }}"> Add </a>
            @endcan
        </h5>
      </div>
      <div class="card-body"> 

          <div class="table-responsive">
              <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                  <thead>
                      <tr>
                        <th width="50">No</th>
                        <th>Paragraph</th>  
                        <th width="90">Created Date</th>  
                        <th width="180">Action</th>
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
                    "order": [[3,'desc']],
                    "pageLength": 5,
                    ajax: URL+"/paragraph_call_data",
                    columns:[
                        {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                        {data:"paragraph",name:"paragraph"}, 
                        {data:"created_at",name:"created_at"},
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            } 
        });
     </script>  
@endsection