@extends('layouts.master')


@section('content') 

    <ul class="breadcrumb">
        <li><a href="#">Home</a></li> 
        <li><a href="#">Page</a></li> 
    </ul> 

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2><span class="fa fa-book"></span> Manage Page</h2>
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
                        <h3 class="panel-title"><strong>Page</strong></h3> 
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
                                        <th width="50">No</th>
                                        <th>Page Name</th>
                                        <th>Page Content</th>   
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
                    ajax: URL+"/page_call_data",
                    columns:[
                        {data:"DT_RowIndex",name:"DT_RowIndex",orderable:false},
                        {data:"page_name",name:"page_name"},
                        {data:"page_content",name:"page_content"},
                        {data:"created_at",name:"created_at"},
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            } 
        });
     </script>  
@endsection