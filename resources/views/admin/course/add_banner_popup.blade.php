@extends('layouts.master') 
@section('content')  
    <style>
        tr{
            background: whitesmoke;
        }
        .tox-notification--warning{
            display:none !important;
        }
    </style>
    <div class="card">
      <div class="card-header pb-0">
        <h5>Add/Edit Banner And PopUp   
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('course.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ url('banner_store') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
               
               <input type="hidden" name="record_id" value="{{ $getData->id }}" />
                @csrf 
                <div class="row fil_ters"> 
                       
                    <div class="col-md-12">
                        <div class="form-group">  
                          <label>Banner Title<code>*</code><small style="color:#808080;">(60 Maximum Character limit)</small></label>  
                          <input type="text" class="form-control  validate[required]" name="banner_content" id="frm_banner_content" maxlength="60" value="{{ $getData->banner_content }}">
                          
                        </div> 
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">  
                          <label>Banner Link<code>*</code></label>
                          <input type="text" class="form-control  validate[required]" name="banner_link" id="frm_banner_link" value="{{ $getData->banner_link }}" > 
                        </div> 
                    </div>
                    <hr/>
                    <div class="col-md-12">
                        <div class="form-group">  
                          <label>PopUp Image<code>*</code> 
                          <span id="frm_popup_image_edit">
                              @if(isset($getData->popup_course_image))
                                    <a href="{{ url('uploads/'.$getData->popup_course_image) }}" target="_blank" class="" >
                                        <img src="{{ url('uploads/'.$getData->popup_course_image) }}" style="width: 30px;" />
                                    </a>

                              @endif
                          </span></label>
                          <input type="file" class="form-control" name="popup_course_image" id="frm_popup_course_image" value="" > 
                        </div> 
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">  
                          <label>PopUp Content<code>*</code></label> 
                          <textarea class="form-control  validate[required]" name="popup_content" id="frm_popup_content" >{{ $getData->popup_content }}</textarea>    
                        </div> 
                    </div> 
                    <div class="col-md-12">
                        <div class="form-group">  
                          <label>PopUp Link<code>*</code></label>
                          <input type="text" class="form-control  validate[required]" name="popup_link" id="frm_popup_link" value="{{ $getData->popup_link }}" > 
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

@section('script') 
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>


        $(document).ready(function(){


            
//   tinymce.init({
//     selector: '#frm_banner_content', menubar: false,
//     menubar: false,
//      branding: false,
//      statusbar: false,
//      height : "180"
//   });
//   tinymce.init({
//     selector: '#frm_popup_content', menubar: false,
//     menubar: false,
//      branding: false,
//      statusbar: false,
//      height : "180"
//   });
            var URL = '{{url('/')}}';

            // CKEDITOR.replace('frm_banner_content');
            // CKEDITOR.replace('frm_popup_content');

            /*$("#course_select").change(function(){ 
                   course_id = $(this).val(); 
                   
                   $.ajaxSetup({
                       headers: { 
                           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                       }
                   }); 
     
                   var form_data = new FormData();
                   form_data.append("course_id",course_id);  
                     
                    $.ajax({
                        type:"POST", 
                        url: URL+"/get_ajax_course_question",
                        data:form_data, 
                        enctype: 'multipart/form-data',
                        processData: false,  // Important!
                        contentType: false,
                        cache: false,
                        dataType: "JSON", 
                        success: function(response){   
                            console.log(response.data.question_list);
                            htm_que = ''; htm_tue='';
                            question_list = response.data.question_list;
                            tutorial_list = response.data.tutorial_list;

                            if(question_list.length>0){ 
                                for(i=0;i<question_list.length;i++){ 
                                    htm_que +='<tr><th><input type="checkbox" id="customRadio_'+question_list[i].id+'" name="question_id[]" value="'+question_list[i].id+'" class="custom-control-input"></th><td>'+question_list[i].question_name+'</td></tr>';
                                }  
                            }
                          
                            $('#question_div').html(htm_que);

                            if(tutorial_list.length>0){ 
                                for(i=0;i<tutorial_list.length;i++){  
                                    htm_tue +='<tr><th><input type="checkbox" id="customRadio_'+tutorial_list[i].id+'" name="tutorial_id[]" value="'+tutorial_list[i].id+'" class="custom-control-input"></th><td>'+tutorial_list[i].chapter_name+'</td></tr>';
                                }  
                            }
                            $('#tutorial_div').html(htm_tue);
                        }
                   });
            });*/
        });
                    
    </script>
@endsection