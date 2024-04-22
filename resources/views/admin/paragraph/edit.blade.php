@extends('layouts.master') 
@section('content')  
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Edit Paragraph  
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('paragraph.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body">
            <form id="validate" action="{{ route('paragraph.update',$get_data->id) }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" > 
                @csrf
                @method('PUT')
                <div class="row fil_ters">
                    <div class="col-md-12">
                       <div class="card-body">
                          <div class="row">

                             <div class="col-md-12"> 
                                <div class="form-group">
                                   <label>Paragraph<code>*</code></label>
                                   <textarea name="paragraph" class="form-control validate[required]">{{ old('paragraph') ? old('paragraph') : $get_data->paragraph }}</textarea>
                                </div> 
                             </div>  

                          </div>
                       </div>
                    </div> 
                </div> <br/> 
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                         <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form> 
      </div> 
    </div>
     
@endsection