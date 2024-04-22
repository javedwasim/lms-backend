@extends('layouts.master')
@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css"
        crossorigin="anonymous">
    <style>
        .inside_dv1 {
            width: 66%;
            margin-left: 2px;
            float: left;
        }

        .inside_dv2 {
            width: 20%;
            margin-left: 12px;
            float: left;
            background: #f4f4f4;
        }

        .inside_dv2_1 {
            margin-left: 12px;
            float: left;
            padding: 10px;
        }

        .inside_dv3 {
            width: 70%;
            margin: 2px;
            float: left;
            background: #cfdde1;
        }

        .bootstrap-tagsinput {
            width: 100%;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 5px;
            color: white;
        }

        .label-info,
        .badge-info {
            background-color: #3a87ad;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            padding: 2px 3px;
        }

        .bootstrap-tagsinput input {
            margin: 5px !important;
        }

        .tox-notification--warning {
            display: none !important;
        }
    </style>
    <div class="card">
        <div class="pb-0 card-header">
            <h5>Add Question
                <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('question.index') }}"> Back</a>
            </h5>
        </div>
        <div class="card-body">
            <form id="validate" action="{{ route('question.store') }}" class="form-horizontal comm_form" method="POST"
                role="form" enctype="multipart/form-data">
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
                    {{-- <div class="col-md-6">
                        <div class="form-group">
                           <label>Course Type<sub>*</sub></label>
                           <select id="coursetype" name="course_type_id" class="form-control coursetype" data-id="1">
                              <option value="">Select Course Type</option>
                              @foreach($CourseType as $val)
                              <option value="{{$val->id}}">{{$val->name}}</option>
                              @endforeach


                           </select>
                        </div>
                     </div> --}}
                    {{-- <div class="col-md-6">
                        <div class="form-group">
                            <label>Course<code>*</code></label>
                            <select class="form-control validate[required] multiple-selected" id="course" name="course[]"
                                multiple>
                                @foreach ($getCourse as $courseDt)
                                    <option {{ old('course') == $courseDt->id ? 'selected' : '' }}
                                        value="{{ $courseDt->id }}">{{ $courseDt->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                </div>
                <div class="row fil_ters">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category<code>*</code></label>
                            <select class="form-control  validate[required]" id="category" name="category">
                                <option value="">Select Category</option>
                                @foreach ($getCategory as $categoryDt)
                                    <option value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sub Category<code>*</code></label>
                            <select class="form-control  validate[required]" id="sub_category" name="sub_category" required>
                                <option value="">Select Sub Category</option>
                                @foreach ($getSubCategory as $subCategoryDt)
                                    <option value="{{ $subCategoryDt->id }}">{{ $subCategoryDt->sub_category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="row fil_ters div_type_1 div_Paragraph">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Paragraph</label>
                            <textarea id="paragraph_id" name="paragraph" class="form-control validate[required]">{{ old('paragraph') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Paragraph Image <code>*</code></label>
                            <input type="file" name="paragraph_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div>
                    </div>
                </div>
                <div class="row fil_ters">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Question<code>*</code></label>
                            <textarea id="question_id" name="question" class="form-control validate[required]">{{ old('question') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Question Image ( Image Size should be 500 x 500 )<code>*</code></label>
                            <input type="file" name="question_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div>
                    </div>
                </div>

                <div class="row fil_ters div_type_1">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option A</label>
                            <textarea id="option_a" name="option_a" class="form-control validate[required]">{{ old('option_a') }}</textarea>
                        </div>
                     {{--    <div class="form-group">
                            <label>Option A Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_a_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option B</label>
                            <textarea id="option_b" name="option_b" class="form-control validate[required]">{{ old('option_b') }}</textarea>
                        </div>
                     {{--    <div class="form-group">
                            <label>Option B Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_b_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option C</label>
                            <textarea id="option_c" name="option_c" class="form-control validate[required]">{{ old('option_c') }}</textarea>
                        </div>
                      {{--   <div class="form-group">
                            <label>Option C Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_c_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option D</label>
                            <textarea id="option_d" name="option_d" class="form-control validate[required]">{{ old('option_d') }}</textarea>
                        </div>
                      {{--   <div class="form-group">
                            <label>Option D Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_d_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option E</label>
                            <textarea id="option_e" name="option_e" class="form-control validate[required]">{{ old('option_e') }}</textarea>
                        </div>
                     {{--    <div class="form-group">
                            <label>Option E Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_e_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option F</label>
                            <textarea id="option_f" name="option_f" class="form-control validate[required]">{{ old('option_f') }}</textarea>
                        </div>
                      {{--   <div class="form-group">
                            <label>Option F Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_f_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option G</label>
                            <textarea id="option_g" name="option_g" class="form-control validate[required]">{{ old('option_g') }}</textarea>
                        </div>
                      {{--   <div class="form-group">
                            <label>Option G Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_g_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option H</label>
                            <textarea id="option_h" name="option_h" class="form-control validate[required]">{{ old('option_h') }}</textarea>
                        </div>
                    {{--     <div class="form-group">
                            <label>Option H Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_h_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option I</label>
                            <textarea id="option_i" name="option_i" class="form-control validate[required]">{{ old('option_i') }}</textarea>
                        </div>
                       {{--  <div class="form-group">
                            <label>Option I Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_i_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Option J</label>
                            <textarea id="option_j" name="option_j" class="form-control validate[required]">{{ old('option_j') }}</textarea>
                        </div>
                     {{--    <div class="form-group">
                            <label>Option J Image ( Image Size should be 200 x 200 )<code>*</code></label>
                            <input type="file" name="option_j_img" class="form-control" accept=".png, .jpg, .jpeg">
                        </div> --}}
                    </div>
                    




                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Correct Answer</label>
                            <!-- <input type="text" name="correct_answer" value="{{ old('correct_answer') }}" class="form-control" /> -->

                            <select class="form-control  validate[required]" id="correct_answer" name="correct_answer">
                                <option value="">Select Correct Answer</option>
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                                <option value="e">E</option>
                                <option value="f">F</option>
                                <option value="g">G</option>
                                <option value="h">H</option>
                                <option value="i">I</option>
                                <option value="j">J</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row fil_ters div_type_2 div_type_3" style="border: 1px solid #e3e2e2; background: #f4f4f4;">
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
                        <div class="form-group" style="padding-top: 34px;">
                            <button type="button" id="addOptionImgeRow"
                                class="form-group btn btn-sm btn-primary">Add</button>
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
                <div class="row fil_ters">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Explanation</label>
                            <textarea id="explanation" name="explanation" class="form-control validate[required]">{{ old('explanation') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Video Explanation</label>
                        <div class="form-group">
                            <input type="file" class="form-control" name="explanation_video" accept="video/*">
                        </div>
                    </div>
                </div>
                <div class="row fil_ters">

                    <div class="col-md-6">
                        <label>Admin Tags</label>
                        <div class="form-group">
                            <select class="form-control" name="question_tags">
                              <option value="">select tag</option>
                                @foreach ($adminTag as $item)
                                    <option value="{{ $item->name }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <br />
                <div class="row">
                    <div class="text-right col-xs-12 col-sm-12 col-md-12">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js"
        integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"
        crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      /*   tinymce.init({
            selector: '#paragraph_id',
            menubar: false,
         
            branding: false,
            statusbar: false,
        
            height: "180"
        }); */
        tinymce.init({
            selector: '#question_id',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
     /*    tinymce.init({
            selector: '#option_a',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_b',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_c',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_d',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_e',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_f',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_g',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_h',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_i',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        tinymce.init({
            selector: '#option_j',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        }); */
        
      /*   tinymce.init({
            selector: '#explanation',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        }); */
    </script>
    <script>
        var highlits_select = new SlimSelect({
            select: '.multiple-selected',
            //showSearch: false,
            placeholder: 'Select',
            deselectLabel: '<span>&times;</span>',
            hideSelectedOption: true,
        })
    </script>

    <script>
        $('#question_tags').tagsinput('add', 'tags data');
        CKEDITOR.replace('explanation',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('paragraph_id',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );  
         CKEDITOR.replace('question_id',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        // CKEDITOR.replace('question_id');

        CKEDITOR.replace('option_a',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_b',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_c',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_d',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_e',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_f',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_g',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_h',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_i',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        CKEDITOR.replace('option_j',{
                extraPlugins: 'uploadimage',
                // height: 300,

                // Upload images to a CKFinder connector (note that the response type is set to JSON).
                uploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&responseType=json&_token={{csrf_token()}}',

                // Configure your file manager integration. This example uses CKFinder 3 for PHP.
                // filebrowserBrowseUrl: '/ckfinder/ckfinder.html',
                // filebrowserImageBrowseUrl: '/ckfinder/ckfinder.html?type=Images',
                filebrowserUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Files&_token={{csrf_token()}}',
                filebrowserImageUploadUrl: '{{url("uploadckeditorimage")}}?command=QuickUpload&type=Images&_token={{csrf_token()}}'
        } );
        // CKEDITOR.replace('explanation');

        $(document).on('click', '#removeOptionImgRow', function() {
            data_id = $(this).data('id');
            $('#' + data_id).remove();
        });

        $(document).ready(function() {

            var URL = '{{ url('/') }}';

            $("#addOptionImgeRow").click(function() {

                $('#newOptionImgRow').html('');
                $('#newAnswerTypeImgRow').html('');

                $('#newAnswerTypeImgRow_type3').html('');
                $('#newOptionImgRow_type3').html('');

                no_of_options = $('#no_of_options').val();

                var html_option = '';

                question_type = $("#question_type").val();

                for (i = 1; i <= no_of_options; i++) {
                    if (question_type == 2 || question_type == 4) {
                        html_option += '<div class="form-group" id="option_dv_' + i + '" >' +
                            '<input type="text" name="option[]" placeholder="Option ' + i +
                            '" class="form-control inside_dv1" />' +
                            '<input type="text" name="option_answer[]" placeholder="Value ' + i +
                            '" class="form-control inside_dv2" />' +
                            '<button type="button" id="removeOptionImgRow" data-id="option_dv_' + i +
                            '" class="form-group btn btn-sm btn-danger inside_dv2_1">x</button>' +
                            '</div>';
                    } else if (question_type == 3) {
                        html_option += '<div class="form-group" id="option_dv_' + i + '" >' +
                            '<input type="text" name="option[]" placeholder="Option ' + i +
                            '" class="form-control inside_dv1" style="width:37%" />' +
                            '<input type="text" name="option_answer[]" placeholder="Value ' + i +
                            '" class="form-control inside_dv2"  style="width:53%" />' +
                            '<button type="button" id="removeOptionImgRow" data-id="option_dv_' + i +
                            '" class="form-group btn btn-sm btn-danger inside_dv2_1">x</button>' +
                            '</div>';
                    }
                }

                if (question_type == 2 || question_type == 4) {
                    $('#newOptionImgRow').append(html_option);
                } else if (question_type == 3) {
                    $('#newOptionImgRow_type3').append(html_option);
                }

                no_of_answer_values = $('#no_of_answer_values').val();

                var html_option_ans = '';
                for (j = 1; j <= no_of_answer_values; j++) {
                    html_option_ans += '<div class="form-group">' +
                        '<input type="text" name="option_attr[]" placeholder="Answer ' + j +
                        '" class="form-control inside_dv3" />' +
                        '</div>';
                }
                if (question_type == 2 || question_type == 4) {
                    $('#newAnswerTypeImgRow').append(html_option_ans);
                } else if (question_type == 3) {
                    $('#newAnswerTypeImgRow_type3').append(html_option_ans);
                }
            });

            $('.div_type_2').hide();
            $('.div_type_3').hide();

            $("#question_type").change(function() {

                $('#newOptionImgRow').html('');
                $('#newOptionImgRow_type3').html('');
                $('#newAnswerTypeImgRow_type3').html('');

                $('#no_of_options').val('0');
                $('#no_of_answer_values').val('0');

                type_id = $(this).val();

                if (type_id == '5') {
                    $('.div_type_1').show();
                    $('.div_Paragraph').hide();
                    $('.div_type_2').hide();
                    $('.div_type_3').hide();
                } else if (type_id == '4') {
                    $('.div_type_1').hide();
                    $('.div_type_2').show();
                } else if (type_id == '2' || type_id == '3') {
                    $('.div_type_1').hide();
                    if (type_id == '2') {
                        $('.div_type_3').hide();
                        $('.div_type_2').show();
                    } else if (type_id == '3') {
                        $('.div_type_2').hide();
                        $('.div_type_3').show();
                    }
                } else {
                    $('.div_type_1').show();
                    $('.div_type_2').hide();
                    $('.div_type_3').hide();
                }

            });

            $("#category").change(function() {

                course_id = $("#course").val();
                category_id = $(this).val();

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                var form_data = new FormData();
                form_data.append("course_id", course_id);
                form_data.append("category_id", category_id);

                $.ajax({
                    type: "POST",
                    url: URL + "/get_subcategory_ajax",
                    data: form_data,
                    enctype: 'multipart/form-data',
                    processData: false, // Important!
                    contentType: false,
                    cache: false,
                    dataType: "JSON",
                    success: function(response) {
                        htm = '<option value="">Select Sub Category</option>';

                        if (response.data.length > 0) {
                            for (i = 0; i < response.data.length; i++) {
                                htm += '<option value="' + response.data[i].id + '">' + response
                                    .data[i].sub_category_name + '</option>';
                            }
                        }
                        $('#sub_category').html(htm);
                    }
                });

                $.ajax({
                    type: "POST",
                    url: URL + "/get_tutorial_ajax",
                    data: form_data,
                    enctype: 'multipart/form-data',
                    processData: false, // Important!
                    contentType: false,
                    cache: false,
                    dataType: "JSON",
                    success: function(response) {
                        htm = '<option value="">Select Tutorial</option>';

                        if (response.data.length > 0) {
                            for (i = 0; i < response.data.length; i++) {
                                htm += '<option value="' + response.data[i].id + '">' + response
                                    .data[i].chapter_name + '</option>';
                            }
                        }
                        $('#tutorial').html(htm);
                    }
                });
            });

        });
        $("#coursetype").on("change", function() {
         var id = $(this).val();
         var countId = $(this).data("id");
         $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getcourse")}}',
            data: {
               'id': id
            },
            success: function(data) {
               // elem = $(this).closest('div').find("select").html(); 
               str = '';
               for (const iterator of data) {
                  console.log(iterator);
                  str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
               }
               console.log(str);
               $("#course").html(str);

            }
         });

      })
    </script>
@endsection
