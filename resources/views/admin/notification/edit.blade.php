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
        <h5>Edit {{ ucfirst($page_title) }}  
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('users.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ route('users.update',$user->id) }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
  
                @csrf
                @method('PUT')
                 <input type="hidden" name="page_title" value="{{ $page_title }}" />
                 <div class="row fil_ters">
                   <div class="col-md-6">
                      <div class="card-body">
                         <div class="row">

                            <div class="col-md-12"> 
                                <div class="form-group">
                                    <label>Name<sub>*</sub></label>
                                    <input type="text" class="form-control  validate[required]"
                                     value="{{ old('name') ? old('name') : $user->name }}"
                                     id="name" placeholder="Name" name="name" autocomplete="off">
                                </div> 
                            </div> 
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Email<sub>*</sub></label>
                                    <div class="">
                                     <input readonly type="email" class="form-control  validate[required]" value="{{ old('email') ? old('email') : $user->email }}" id="name" placeholder="Email" name="email" autocomplete="off">
                                    </div>
                                </div>
                            </div>                                          
                            
                            <div class="col-md-12"> 
                               <div class="form-group">
                                  <label>Phone<sub>*</sub></label>
                                  <input readonly maxlength="10" type="text" class="form-control validate[required]" value="{{ old('phone') ? old('phone') : $user->phone }}" id="phone" placeholder="Phone" name="phone" autocomplete="off" >
                               </div> 
                            </div>  
                         </div>
                      </div>
                   </div>
                   <div class="col-md-6">
                      <div class="card-body">
                         <div class="row">
                            <div class="col-md-12"> 
                               <div class="form-group">
                                  <label>Country Code<sub>*</sub></label>
                                  <input readonly maxlength="6" type="text" class="form-control validate[required]" value="{{ old('country_code') ? old('country_code') : $user->country_code }}" id="country_code" placeholder="Country Code" name="country_code" autocomplete="off" >
                               </div> 
                            </div>
                            <div class="col-md-12">
                               <div class="form-group mensa">
                                  <label style="">Profile Image</label>
                                      @if($user->profile_photo_path)
                                         <a href="{{ url('uploads/'.$user->profile_photo_path) }}" target="_balnk" >
                                            <img src="{{ url('uploads/'.$user->profile_photo_path) }}" style="width: 17px; margin-left:3px;" />
                                         </a>
                                      @endif
                                   <br> 
                                  <input type="file" class="form-control" name="profile_photo_path" >
                               </div>
                            </div>
                            <input type="hidden" name="roles[]" value="{{ $user->role_id }}" />
                              
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
 
@endsection