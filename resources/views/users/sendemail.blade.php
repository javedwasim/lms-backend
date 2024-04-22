@extends('layouts.master')
@section('content')
<style>
    #course_dt_id {
        padding: 5px;
        margin-right: 8px;
    }
</style>
<div class="card">
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />

    <div class="card-body">

        <form id="validate" action="" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
            @csrf
            <div class="row fil_ters">
                <div class="col-md-12">
                    <div class="card-body">
                        <div class="row">
                            @php
                                $Colle = []; 
                                if(isset($mailUserIds)){
                                    $Colle = explode(",", $mailUserIds);
                                }
                            @endphp
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>To</label>
                                    <select class="form-control validate[required] multiple-selected" id="user" name="userid[]"
                                multiple>
                                        @foreach($userlist as $val)
                                        <option value="{{$val->id}}"  {{ in_array($val->id,$Colle) ? 'selected' : '' }}>{{$val->name}} ({{$val->email}})</option>
                                        @endforeach
                                       
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Test Email</label>
                                    <input type="email" class="form-control" name="testemail">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mensa">
                                    <label style="">Subject</label> <br>
                                    <input type="text" class="form-control" name="subject">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Message<sub>*</sub></label>
                                    <textarea name="message" id="trans_script" class="form-control validate[required]">{{ old('trans_script') }}</textarea>
                                </div>
                            </div>
                          
                            <div class="clear:both"></div>
                            

                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="noofcontent" value="1">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>

    </div>
</div>
@endsection

@section('script')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.17.1/full/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('trans_script',{
                extraPlugins: 'uploadimage',
                uploadUrl: 'https://app.studymind.co.uk/admin/uploadckeditorimage?command=QuickUpload&type=Files&responseType=json&_token=uL5WKaQleGDJ9HXkV9XsTk4m8kq1JvrjZHZd6xZn',
                filebrowserUploadUrl: 'https://app.studymind.co.uk/admin/uploadckeditorimage?command=QuickUpload&type=Files&_token=uL5WKaQleGDJ9HXkV9XsTk4m8kq1JvrjZHZd6xZn',
                filebrowserImageUploadUrl: 'https://app.studymind.co.uk/admin/uploadckeditorimage?command=QuickUpload&type=Images&_token=uL5WKaQleGDJ9HXkV9XsTk4m8kq1JvrjZHZd6xZn'
        } );
        var slim  = new SlimSelect({
            select: '.multiple-selected',
            placeholder: 'Select',
            deselectLabel: '<span>&times;</span>',
            hideSelectedOption: true,
        });
        
    </script>

<script>
    $("#courseid").on("change", function() {
        var id = $(this).val();
        var countId = $(this).data("id");
        $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getcoursewiseuser")}}',
            data: {
                'id': id
            },
            success: function(data) {
                str = '<option value="all">Select all</option>';
                for (const iterator of data) {
                    console.log(iterator);
                    str = str + '<option value="' + iterator.id + '"> ' + iterator.name + ' ('+iterator.email+') </option>';
                }
                console.log(str);
                $("#user").html(str);
                var highlits_select = new SlimSelect({
                    select: '.multiple-selected',
                    placeholder: 'Select',
                    deselectLabel: '<span>&times;</span>',
                    hideSelectedOption: true,
                })
            }
        });

    })
</script>
@endsection