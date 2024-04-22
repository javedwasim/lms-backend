@extends('layouts.master')

<style>
   #inputPropImgRow .input-group{
      display: inline-flex;
   }
   #removePropImgRow{
      padding: 12px;
      margin-left: 3px;
   }
</style>
@section('content')
   <ul class="breadcrumb">
        <li><a href="#">Home</a></li> 
        <li><a href="#">Edit Page</a></li> 
    </ul> 
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Edit Page</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('page.index') }}"> Back</a>
            </div>
        </div>
    </div>


    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="panel panel-default">  

                <div class="page-head">        
                    <div class="page-head-text">
                        <h3 class="panel-title"><strong>Edit Page</strong></h3> 
                    </div>
                    <div class="page-head-controls">  
                    </div>                    
                </div>  
                <div class="panel-default">
                    <div class="panel-body"> 

                            <form id="validate" action="{{ route('page.update',$get_data->id) }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
  
                                @csrf
                                @method('PUT') 
                                 <div class="row fil_ters">
                                   <div class="col-md-12">
                                      <div class="card-body">
                                         <div class="row">

                                            <div class="col-md-12"> 
                                                <div class="form-group">
                                                    <label>Page Name<sub>*</sub></label>
                                                    <input readonly type="text" class="form-control  validate[required]"
                                                     value="{{ old('page_name') ? old('page_name') : $get_data->page_name }}"
                                                     id="page_name" placeholder="Page Name" name="page_name" autocomplete="off">
                                                </div> 
                                            </div> 
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Page Content<sub>*</sub></label>
                                                    <div class=""> 
                                                     <textarea  class="validate[required] summernote" name="page_content" >{{ old('page_content') ? old('page_content') : $get_data->page_content }}</textarea>
                                                    </div>
                                                </div>
                                            </div>      
                                         </div>
                                      </div>
                                   </div> 
                                </div> 
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div> 
                            </form>                           

                    </div>
                </div>
            </div>                                                

        </div> 
    </div>
    

 
@endsection