@extends('layouts.master')
@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
    <div class="card">
        <div class="card-header pb-0">
            <h5>Edit {{ ucfirst($page_title) }}
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('tutorial.index') }}"> Back</a>
            </h5>
        </div>
        <div class="card-body">
            <form id="validate" action="{{ route('tutorial.store') }}" class="form-horizontal comm_form" method="POST"
                role="form" enctype="multipart/form-data">

                <input type="hidden" name="record_id" value="{{ $getData->id }}" />
                @csrf
                <input type="hidden" name="page_title" value="{{ $page_title }}" />
                <div class="row fil_ters">
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category Name<code>*</code></label>
                            <select class="form-control  validate[required]" id="category" name="category">
                                <option value="">Select Category</option>
                                @foreach ($getCategory as $categoryDt)
                                    <option
                                        {{ old('category') == $categoryDt->id || $getData->category_id == $categoryDt->id ? 'selected' : '' }}
                                        value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Course Type<code>*</code></label>
                            <select class="form-control " id="coursetype" name="course_type_id"
                                >
                                <option value="">Select Course Type</option>
                                @foreach ($CourseType as $val)
                                    <option {{$getData->course_type_id == $val->id ? 'selected' : '' }}
                                        value="{{ $val->id }}">{{ $val->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Course<code>*</code></label>

                            <select class="form-control validate[required] multiple-selected" id="course" name="course[]"
                                multiple>
                                <option value="">Select Course</option>
                                @foreach ($getCourse as $courseDt)
                                    <option {{ in_array($courseDt->id, $selectedCourseArr) ? 'selected' : '' }}
                                        value="{{ $courseDt->id }}">{{ $courseDt->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tutorial Name<code>*</code></label>
                            <input type="text" class="form-control validate[required]"
                                value="{{ old('tutorial_name') ? old('tutorial_name') : $getData->chapter_name }}"
                                id="tutorial_name" placeholder="Tutorial Name" name="tutorial_name" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Video Length(ex. 00:00:00)<code>*</code> ( Time of video must be same as length of the
                                video )</label>
                            <input type="text" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}"
                                class="form-control validate[required]"
                                value="{{ old('video_length') ? old('video_length') : $getData->total_video_time }}"
                                id="video_length" placeholder="00:00:00" name="video_length" maxlength="8"
                                autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Upload Video {{ $getData->video_type }}<code>*</code></label>
                            <input type="radio" name="videoType" id="videoType" value="1"
                                @if ($getData->video_type != 2) checked @endif onclick="checkradio(this)">
                            <label> Video Url<code>*</code></label>
                            <input type="radio" name="videoType" id="videoType1" value="2" onclick="checkradio(this)"
                                @if ($getData->video_type == 2) checked @endif>

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Video Heading</label>
                            <input type="text" class="form-control" name="video_heading" id="video_heading" value="{{ $getData->video_heading }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group videoupload"
                            @if ($getData->video_type != 2) style="display:block" @else style="display:none" @endif>
                            <label>Video
                                <span>
                                    @if ($getData->video_url)
                                        <a href="{{ $getData->video_url }}" target="_balnk">
                                            <i class="fa fa-video" style="width: 30px; margin-left:3px;"></i>
                                        </a>
                                    @endif
                                </span>
                            </label>
                            <input type="file" class="form-control" name="video_url" id="frm_video_url" value=""
                                accept="video/mp4,video/x-m4v,video/*" >
                        </div>
                        <div class="form-group videourl"
                            @if ($getData->video_type == 2) style="display:block" @else style="display:none" @endif>
                            <label>Video Url;<code>*</code>
                                <span>
                                    @if ($getData->video_url)
                                        <a href="{{ $getData->video_url }}" target="_balnk">
                                            <i class="fa fa-video" style="width: 30px; margin-left:3px;"></i>
                                        </a>
                                    @endif
                                </span>
                            </label>
                            <input type="text" class="form-control" name="video_url" id="frm_video_url"
                                value="{{ $getData->video_url }}">
                        </div>
                    </div>
                 
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Pdf Heading</label>
                            <input type="text" class="form-control" name="pdf_heading" id="pdf_heading" value="{{ $getData->pdf_heading }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                      
                        <div class="form-group">
                            <label>Pdf   @if ($getData->pdf_url)
                              <a href="{{ $getData->pdf_url }}" target="_balnk">
                                  <i class="fa fa-file-pdf-o" style="width: 30px; margin-left:3px;"></i>
                              </a>
                          @endif <code>*</code></label>
                            <input type="file" class="form-control" name="pdf_url" id="frm_video_url1"
                                value="" accept="application/pdf">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Select Video or Pdf Order<code>*</code></label>
                            <select name="video_pdf_order" id="video_pdf_order" class="form-control validate[required]">
                                <option {{ old('status') == '1' || $getData->status == '1' ? 'selected' : '' }}
                                    value="1">Show Video First Then Pdf If Exists</option>
                                <option {{ old('status') == '0' || $getData->status == '2' ? 'selected' : '' }}
                                    value="2">Show Pdf First Then Video If Exists</option>
                            </select>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Custom Code<code>*</code></label>
                            <input type="text" name="custom_code"  class="form-control" id="" value="{{ $getData->custom_code }}">

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status<code>*</code></label>
                            <select name="status" id="frm_status" class="form-control validate[required]">
                                <option {{ old('status') == '1' || $getData->status == '1' ? 'selected' : '' }}
                                    value="1">Active</option>
                                <option {{ old('status') == '0' || $getData->status == '0' ? 'selected' : '' }}
                                    value="0">Inactive</option>
                            </select>

                        </div>
                    </div>
                    <div class="col-md-6" style="display:none">
                        <div class="form-group">
                            <label>Order<code>*</code></label>
                            <input type="number" class="form-control" name="tutorialorder" id="tutorialorder" value="{{@$getData->tutorialorder}}">

                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Transcript<code>*</code></label>
                            <textarea name="trans_script" id="trans_script" class="form-control validate[required]">{{ old('trans_script') ? old('trans_script') : $getData->trans_script }}</textarea>
                        </div>
                    </div>
                </div> <br />
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#trans_script',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
        var highlits_select = new SlimSelect({
            select: '.multiple-selected',
            //showSearch: false,
            placeholder: 'Select',
            deselectLabel: '<span>&times;</span>',
            hideSelectedOption: true,
        })

        function checkradio(e) {
            var type = $(e).val();
            if (type == 1) {
                $(".videoupload").show();
                $(".videourl").hide();
                $("#frm_video_url").val('');
            } else {
                $(".videoupload").hide();
                $(".videourl").show();
                $("#frm_video_url1").val('');
            }

        }
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
