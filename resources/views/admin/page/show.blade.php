@extends('layouts.master')
<style type="text/css">
     label {
        font-size: 15px;
        line-height: 34px;
    }    
</style> 
@section('content')
   <ul class="breadcrumb">
        <li><a href="#">Home</a></li> 
        <li><a href="#">View Page</a></li> 
    </ul> 
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> View Page</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('page.index') }}"> Back</a>
            </div>
        </div>
    </div>
   
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="panel panel-default">  
                <div class="page-head">        
                    <div class="page-head-text">
                        <h3 class="panel-title"><strong>View Page</strong></h3> 
                    </div>
                    <div class="page-head-controls"> 
                    </div>                    
                </div>  
                <div class="panel-default">
                    <div class="panel-body">
                        <div class="row fil_ters"> 
                            <div class="col-md-12"> 
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-actions" >
                                        <tr>
                                            <th>Page Name</th>
                                            <td>{{ ucwords($get_data->page_name) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Content</th>
                                            <td>{{ $get_data->page_content }}</td>
                                        </tr>  
                                    </table>
                                </div>   
                            </div>    
                                        
                      </div><hr/>     
                  </div>
                </div>
            </div>
        </div> 
    </div>
    
@endsection 
 