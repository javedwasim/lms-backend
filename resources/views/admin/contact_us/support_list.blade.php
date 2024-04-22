@extends('layouts.master')

@section('content') 

    <ul class="breadcrumb">
        <li><a href="#">Home</a></li> 
        <li><a href="#">Help & Support List</a></li> 
    </ul> 

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2><span class="fa fa-support"></span> Help & Support List</h2>
            </div>
            <div class="pull-right">  
                
            </div>
        </div>
    </div>  

    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="panel panel-default">  

                <div class="page-head">        
                    <div class="page-head-text">
                        <h3 class="panel-title"><strong>Help & Support List</strong></h3> 
                    </div>
                    <div class="page-head-controls">   
                    </div>                    
                </div>  
                <div class="panel-default">
                    <div class="panel-body">
                        <div class="table-responsive"> 

                            <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                                <thead>
                                    <tr>
                                        <th>No</th> 
                                        <th>Subject</th> 
                                        <th>Admin Reply</th>
                                        <th>Create Date</th>
                                        <th width="280px">Action</th>
                                    </tr>
                                </thead> 
                            </table>
                        </div>                           

                    </div>
                </div>
            </div>                                                

        </div> 
    </div> 
@endsection
 
@section('script')    
    <script>

        $(document).ready(function() { 
            uid = "{{ $user_id}}";  
            if($('#my_data_table').length > 0){
                $('#my_data_table').DataTable({
                    processing:true,
                    serverSide:true,
                    "pageLength": 5,
                    ajax: URL+"/contact_us_call_data/"+uid,
                    columns:[
                        {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                        {data:"enquiry",name:"enquiry"}, 
                        {data:"support_reply",name:"support_reply"},
                        {data:"created_at",name:"created_at"},
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            }  
        });
    </script>  
@endsection
    

  