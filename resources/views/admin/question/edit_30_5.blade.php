@extends('layouts.master') 
@section('content')
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Edit {{ ucfirst($page_title) }}  
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('question.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ route('question.store') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
               
               <input type="hidden" name="record_id" value="{{ $getData->id }}" />
                @csrf 
                <input type="hidden" name="page_title" value="{{ $page_title }}" />
                <div class="row fil_ters"> 
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Category<code>*</code></label> 
                          <select class="form-control  validate[required]" id="category" name="category">
                              <option value="">Select Category</option>
                              @foreach($getCategory as $categoryDt)
                                 <option {{ (old('category')==$categoryDt->id || $getData->category_id == $categoryDt->id) ? 'selected' : '' }} value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Sub Category<code>*</code></label> 
                          <select class="form-control  validate[required]" id="sub_category" name="sub_category">
                              <option value="">Select Sub Category</option>
                              @foreach($getSubCategory as $sub_categoryDt)
                                 <option {{ (old('sub_category')==$sub_categoryDt->id || $getData->sub_category_ids == $sub_categoryDt->id) ? 'selected' : '' }} value="{{ $sub_categoryDt->id }}">{{ $sub_categoryDt->sub_category_name }}</option>
                              @endforeach
                          </select>
                        </div> 
                     </div> 
                </div> <hr/> 

                <div class="row fil_ters">
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Paragraph(If Exists)</label>
                          <select class="form-control  validate[required]" id="paragraph" name="paragraph">
                              <option value="">Select Paragraph</option>
                              @foreach($getParagraph as $paragraphDt)
                                 <option {{ (old('paragraph')==$paragraphDt->id || $getData->paragraph_id == $getData->paragraph_id) ? 'selected' : '' }} value="{{ $paragraphDt->id }}">{{ $paragraphDt->paragraph }}</option>
                              @endforeach
                          </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Question<code>*</code></label>
                          <textarea name="question" class="form-control validate[required]">{{ old('question') ? old('question') : $getData->question_name }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                          <label>Option A</label> 
                          <input type="text" name="option_a" value="{{ old('option_a') ? old('option_a') : $getData->option_a }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option B</label> 
                          <input type="text" name="option_b" value="{{ old('option_b') ? old('option_b') : $getData->option_b }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option C</label> 
                          <input type="text" name="option_c" value="{{ old('option_c') ? old('option_c') : $getData->option_c }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option D</label> 
                          <input type="text" name="option_d" value="{{ old('option_d') ? old('option_d') : $getData->option_d }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option E</label> 
                          <input type="text" name="option_e" value="{{ old('option_e') ? old('option_e') : $getData->option_e }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Option F</label> 
                          <input type="text" name="option_f" value="{{ old('option_f') ? old('option_f') : $getData->option_f }}" class="form-control" />
                        </div> 
                     </div>
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Correct Answer</label> 
                          <input type="text" name="correct_answer" value="{{ old('correct_answer') ? old('correct_answer') : $getData->correct_answer }}" class="form-control" />
                        </div> 
                     </div> 
                </div><br/> 
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                        <div class="form-group">
                           <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div> 
            </form>
      </div> 
    </div> 
 
@endsection

@section('script')  
    <script>  
        $(document).ready(function(){

            var URL = '{{url('/')}}'; 

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