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
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Send Notification   
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('notification.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ route('notification.store') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                <div class="row fil_ters">
                    <div class="col-md-12">
                       <div class="card-body">
                          <div class="row">

                             <div class="col-md-12"> 
                                   <div class="form-group">
                                      <label>Title<sub>*</sub></label>
                                      <input type="text" class="form-control validate[required]" value="{{ old('notification_title') }}" id="notification_title" placeholder="Title Name" name="notification_title" autocomplete="off" >
                                   </div> 
                             </div> 
                             <div class="col-md-12"> 
                                   <div class="form-group">
                                      <label>Description<sub>*</sub></label> 
                                      <textarea name="description"  class="form-control validate[required]">{{ old('description') }}</textarea>
                                   </div> 
                             </div>
                              
                          </div>
                       </div>
                    </div> 

                </div>   
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                         <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </form>
      </div> 
    </div>
     
@endsection