@extends('layouts.master') 
@section('content')  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" /> 
 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" crossorigin="anonymous">
    <style>
        .inside_dv1{
            width: 66%; margin-left: 2px; float: left;
        }
        .inside_dv2{
            width: 20%; margin-left: 12px; float: left; background: #f4f4f4;
        }
        .inside_dv2_1{
            margin-left: 12px; float: left; padding: 10px;
        }
        .inside_dv3{
            width: 70%; margin: 2px; float: left; background: #cfdde1;
        }
        .bootstrap-tagsinput{
            width: 100%;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 5px;
            color: white;
        }
        .label-info, .badge-info {
            background-color: #3a87ad;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            padding: 2px 3px;
        }
        .bootstrap-tagsinput input{
            margin: 5px !important;
        }
        .tox-notification--warning{
            display:none !important;
        }
    </style> 
    <div class="card">
      <div class="card-header pb-0">
        <h5>Admin Note
          {{--   <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('question.index') }}"> Back</a> --}}
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action=""  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                
                <div class="row fil_ters div_type_1 div_Paragraph">
                  
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Admin Note</label> 
                          <textarea id="paragraph_id" name="comment" class="form-control validate[required]">{{ @$comment->admin_note }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-12" >
                        <div class="form-group">
                          <label>Status<code>*</code></label> 
                          <select class="form-control  validate[required]" id="question_type" name="status">   
                                 <option value="0" @if($comment->status==0) selected @endif >Pending</option> 
                                 <option value="1" @if($comment->status==1) selected @endif >Resolved</option> 
                                
                          </select>
                        </div> 
                     </div>
                   
                </div>
              
            

                <br/>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
      </div> 
    </div>
     
@endsection
@section('script')  
    
  
@endsection