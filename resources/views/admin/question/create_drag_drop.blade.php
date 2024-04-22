@extends('layouts.master') 
@section('content')  
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
    </style>
    <div class="card">
      <div class="card-header pb-0">
        <h5>Add Question   
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('question.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ route('question.store') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                <div class="row fil_ters"> 
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Question Type<code>*</code></label> 
                          <select class="form-control  validate[required]" id="question_type" name="question_type"> 
                                 <option value="1">Question left Option right</option> 
                                 <option value="2">Drag and Drop</option> 
                                 <option value="3">No division</option> 
                                 <option value="4">Type 4</option> 
                                 <option value="5">Type 5</option> 
                          </select>
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Course<code>*</code></label> 
                          <select class="form-control  validate[required]" id="course" name="course">
                              <option value="">Select Course</option>
                              @foreach($getCourse as $courseDt)
                                 <option {{ (old('course')==$courseDt->id) ? 'selected' : '' }} value="{{ $courseDt->id }}">{{ $courseDt->course_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                </div>
                <div class="row fil_ters">
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Category<code>*</code></label> 
                          <select class="form-control  validate[required]" id="category" name="category">
                              <option value="">Select Category</option>
                              @foreach($getCategory as $categoryDt)
                                 <option value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Sub Category<code>*</code></label> 
                          <select class="form-control  validate[required]" id="sub_category" name="sub_category">
                              <option value="">Select Sub Category</option>
                          </select>
                        </div> 
                     </div>  
                </div> <hr/> 
                <div class="row fil_ters div_type_1">
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Paragraph</label> 
                          <textarea name="paragraph" class="form-control validate[required]">{{ old('paragraph') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="row fil_ters">
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Question<code>*</code></label>
                          <textarea name="question" class="form-control validate[required]">{{ old('question') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="row fil_ters div_type_1">  
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Option A</label> 
                          <input type="text" name="option_a" value="{{ old('option_a') }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option B</label> 
                          <input type="text" name="option_b" value="{{ old('option_b') }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option C</label> 
                          <input type="text" name="option_c" value="{{ old('option_c') }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option D</label> 
                          <input type="text" name="option_d" value="{{ old('option_d') }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option E</label> 
                          <input type="text" name="option_e" value="{{ old('option_e') }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option F</label> 
                          <input type="text" name="option_f" value="{{ old('option_f') }}" class="form-control" />
                        </div> 
                     </div> 
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Correct Answer</label> 
                          <input type="text" name="correct_answer" value="{{ old('correct_answer') }}" class="form-control" />
                        </div> 
                     </div>
                </div>

                <div class="row fil_ters div_type_2 div_type_3" style="border: 1px solid #e3e2e2; background: #f4f4f4;" >   
                    <div class="col-md-4">
                        <div class="form-group">
                          <label>No. Of Options</label> 
                          <input type="number" id="no_of_options" value="0" class="form-control" />
                        </div> 
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                          <label>No. Of Answer</label> 
                          <input type="number" id="no_of_answer_values" value="0" class="form-control" />
                        </div> 
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" style="padding-top: 34px;" >  
                          <button type="button" id="addOptionImgeRow" class="form-group btn btn-sm btn-primary">Add</button>
                        </div> 
                    </div> 
                </div>
                <div class="div_type_2"> 
                  <div class="row">
                     <div class="col-lg-9"> 
                        <div id="newOptionImgRow"></div> 
                     </div>
                     <div class="col-lg-3"> 
                        <div id="newAnswerTypeImgRow"></div> 
                     </div> 
                  </div> 
                </div>

                <div class="div_type_3"> 
                  <div class="row">
                     <div class="col-lg-12"> 
                        <div id="newOptionImgRow_type3"></div> 
                     </div>
                     <div class="col-lg-12"> 
                        <div id="newAnswerTypeImgRow_type3"></div> 
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
    <script>  

        $(document).on('click', '#removeOptionImgRow', function (){
            data_id = $(this).data('id'); 
            $('#'+data_id).remove();
        });

        $(document).ready(function(){

            var URL = '{{url('/')}}'; 

            $("#addOptionImgeRow").click(function () {  

                $('#newOptionImgRow').html('');
                $('#newAnswerTypeImgRow').html('');

                no_of_options = $('#no_of_options').val();
                
                var html_option = '';
                 
                question_type = $("#question_type").val(); 

                for(i=1;i<=no_of_options;i++){
                    if(question_type==2){
                        html_option +='<div class="form-group" id="option_dv_'+i+'" >'+ 
                                  '<input type="text" name="option[]" placeholder="Option '+i+'" class="form-control inside_dv1" />'+
                                  '<input type="text" name="option_answer[]" placeholder="Value '+i+'" class="form-control inside_dv2" />'+
                                  '<button type="button" id="removeOptionImgRow" data-id="option_dv_'+i+'" class="form-group btn btn-sm btn-danger inside_dv2_1">x</button>'+
                                '</div>';
                    }else if(question_type==3){
                        html_option +='<div class="form-group" id="option_dv_'+i+'" >'+ 
                                  '<input type="text" name="option[]" placeholder="Option '+i+'" class="form-control inside_dv1" style="width:37%" />'+
                                  '<input type="text" name="option_answer[]" placeholder="Value '+i+'" class="form-control inside_dv2"  style="width:53%" />'+
                                  '<button type="button" id="removeOptionImgRow" data-id="option_dv_'+i+'" class="form-group btn btn-sm btn-danger inside_dv2_1">x</button>'+
                                '</div>';
                    }            
                }

                if(question_type==2){
                    $('#newOptionImgRow').append(html_option);
                }else if(question_type==3){ 
                    $('#newOptionImgRow_type3').append(html_option);
                }

                no_of_answer_values = $('#no_of_answer_values').val();
                  
                var html_option_ans = '';  
                for(j=1;j<=no_of_answer_values;j++){
                    html_option_ans +=  '<div class="form-group">'+ 
                                          '<input type="text" name="option[]" placeholder="Answer '+j+'" class="form-control inside_dv3" />'+
                                        '</div>'; 
                }
                if(question_type==2){
                    $('#newAnswerTypeImgRow').append(html_option_ans);
                }else if(question_type==3){
                    $('#newAnswerTypeImgRow_type3').append(html_option_ans);
                }
            });

            $('.div_type_2').hide();
            $('.div_type_3').hide();

            $("#question_type").change(function(){
                
                $('#newOptionImgRow').html('');
                $('#newOptionImgRow_type3').html('');

                $('#no_of_options').val('0');
                $('#no_of_answer_values').val('0');

                type_id = $(this).val(); 
               
                if(type_id=='2' || type_id=='3' || type_id=='4'|| type_id=='5'){  
                    $('.div_type_1').hide();  
                    if(type_id=='2'){
                        $('.div_type_3').hide();  
                        $('.div_type_2').show();  
                    }else if(type_id=='3'){
                        $('.div_type_2').hide();
                        $('.div_type_3').show();  
                    }
               }else{
                    $('.div_type_1').show();  
                    $('.div_type_2').hide();
                    $('.div_type_3').hide();
               }

           });

            $("#category").change(function(){
               
               course_id = $("#course").val();  
               category_id = $(this).val(); 
               
               $.ajaxSetup({
                   headers: { 
                       'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                   }
               }); 
 
               var form_data = new FormData();
               form_data.append("course_id",course_id); 
               form_data.append("category_id",category_id); 
                 
                $.ajax({
                    type:"POST", 
                    url: URL+"/get_subcategory_ajax",
                    data:form_data, 
                    enctype: 'multipart/form-data',
                    processData: false,  // Important!
                    contentType: false,
                    cache: false,
                    dataType: "JSON", 
                    success: function(response){   
                        htm = '<option value="">Select Sub Category</option>';

                        if(response.data.length>0){ 
                            for(i=0;i<response.data.length;i++){
                                 htm +='<option value="'+response.data[i].id+'">'+response.data[i].sub_category_name+'</option>';
                            }  
                        }
                        $('#sub_category').html(htm);  
                    }
               });

               $.ajax({
                    type:"POST", 
                    url: URL+"/get_tutorial_ajax",
                    data:form_data, 
                    enctype: 'multipart/form-data',
                    processData: false,  // Important!
                    contentType: false,
                    cache: false,
                    dataType: "JSON", 
                    success: function(response){   
                        htm = '<option value="">Select Tutorial</option>';

                        if(response.data.length>0){ 
                            for(i=0;i<response.data.length;i++){
                                 htm +='<option value="'+response.data[i].id+'">'+response.data[i].chapter_name+'</option>';
                            }  
                        }
                        $('#tutorial').html(htm);  
                    }
               }); 
         });

        });
    </script>
@endsection