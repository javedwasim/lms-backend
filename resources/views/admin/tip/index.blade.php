@extends('layouts.master')
@section('content')
    <style>
        #tip_type {
            padding: 5px;
            margin-right: 8px;
            background: linear-gradient(310deg, #ffffff 0%, #5a6682 100%);
        }
    </style>
    <div class="card">
        <div class="card-header pb-0">
            <h5>Tip
                <div style="float:right">
                    <button type="button" class="btn btn-icon bg-gradient-secondary float-right reset_form"
                        data-bs-toggle="modal" data-bs-target="#addForm">
                        Add
                    </button>
                </div>
                <div style="float:right">
                    <select id="tip_type">
                        <option value="">Filter By</option>
                        <option value="1">Tip of the day</option>
                        <option value="2">Weekly webinar</option>
                        <option value="3">One day workshop</option>
                    </select>
                </div>
            </h5>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table id="my_data_table" class="table table-bordered table-striped table-actions">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Type</th>
                            <th>Course</th>
                            <th>Tip Title</th>
                            <th width="100">Status</th>
                            <th width="90">Tip Date</th>
                            <th width="90">Create Date</th>
                            <th width="180">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="modal fade" id="addForm" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add/Edit Form</h5>
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="validate" action="{{ route('tip.store') }}" class="form-horizontal comm_form" method="POST"
                    role="form">
                    @csrf
                    <div class="modal-body">
                        <div id="message_box"></div>
                        <input type="hidden" id="record_id" class="form-control" name="record_id" value="" />
                        <div class="form-group">
                            <label class="col-form-label">Tip Title <code>*</code>:</label>
                            <input type="text" class="form-control  validate[required]" name="tip_title"
                                id="frm_tip_title" value="">
                        </div>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label">Type <code>*</code>:</label>
                            <select name="tip_type" id="frm_type" class="form-control validate[required]">
                                <option value="">Select Type</option>
                                <option value="1">Tip of the day</option>
                                <option value="2">Weekly webinar</option>
                                <option value="3">One day workshop</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course Type<sub>*</sub></label>
                            <select id="coursetype" name="course_type_id" class="form-control coursetype" data-id="1">
                                <option value="">Select Course Type</option>
                                @foreach ($CourseType as $val)
                                    <option value="{{ $val->id }}">{{ $val->name }}</option>
                                @endforeach


                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label">Course <code>*</code>:</label>
                            <select name="course" id="frm_course" class="form-control validate[required]">
                                <option value="">Select Course</option>
                                @foreach ($getCourse as $course_val)
                                    <option value="{{ $course_val->id }}">{{ $course_val->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="frm_tip_date_div">
                            <label for="message-text" class="col-form-label">Tip Date :</label>
                            <input type="date" name="tip_date" id="frm_tip_date" class="form-control"
                                value="" />
                        </div>
                        <div class="form-group" id="frm_web_link_div">
                            <label for="message-text" class="col-form-label">Web Link :</label>
                            <input type="text" name="web_link" id="frm_web_link" class="form-control" value="" />
                        </div>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label">Description :</label>
                            <textarea name="description" id="frm_description" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="message-text" class="col-form-label">Status <code>*</code>:</label>
                            <select name="status" id="frm_status" class="form-control validate[required]">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="common_form_submit" class="btn bg-gradient-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_view_dt" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">View Details</h5>
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-form-label">Tip Title:</label>
                        <span class="form-control" id="show_tip_title"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Type :</label>
                        <span class="form-control" id="show_type"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Course Name :</label>
                        <span class="form-control" id="show_course_name"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Tip Date :</label>
                        <span class="form-control" id="show_tip_date"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Web Link :</label>
                        <span class="form-control" id="show_web_link"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Description :</label>
                        <span class="form-control" id="show_description"></span>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">Status:</label>
                        <span class="form-control" id="show_status"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script>
        var URL = '{{ url('/') }}';

        $(document).on("click", '.reset_form', function(e) {
            $('#message_box').html('');
        });

        $(document).on("click", '.view_in_modal', function(e) {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            form_data_id = $(this).data('id');

            var form_data = new FormData();
            form_data.append("record_id", form_data_id);

            $.ajax({
                type: "POST",
                url: URL + "/tip_get_data",
                data: form_data,
                enctype: 'multipart/form-data',
                processData: false, // Important!
                contentType: false,
                cache: false,
                dataType: "JSON",
                success: function(response) {

                    if (response.result.length > 0) {
                        tip_title = response.result[0].tip_title;
                        tip_type = response.result[0].tip_type;
                        course_name = response.result[0].course_name;
                        description = response.result[0].description;
                        tip_date = response.result[0].tip_date;
                        web_link = response.result[0].web_link;
                        status_name = response.result[0].status_name;

                        $('#show_tip_title').text(tip_title);
                        $('#show_type').text(tip_type);
                        $('#show_course_name').text(course_name);
                        $('#show_description').text(description);
                        $('#show_tip_date').text(tip_date);
                        $('#show_web_link').text(web_link);
                        $('#show_status').text(status_name);
                    }
                }
            });
        });

        $('#frm_web_link_div').hide();
        $('#frm_tip_date_div').hide();

        $("#frm_type").change(function() {
            type_id = $(this).val();
            if (type_id == 2 || type_id == 3) {
                $('#frm_web_link_div').show();
                $('#frm_tip_date_div').show();
            } else {
                $('#frm_web_link_div').hide();
                $('#frm_tip_date_div').hide();
            }
        });

        $("#tip_type").change(function() {
            $('#my_data_table').DataTable().ajax.reload();
        });

        $(document).on("click", '.form_data_act', function(e) { // worked with dynamic loaded jquery content

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            form_data_id = $(this).data('id');

            $('#record_id').val(form_data_id);

            var form_data = new FormData();
            form_data.append("record_id", form_data_id);

            $.ajax({
                type: "POST",
                url: URL + "/tip_get_data",
                data: form_data,
                enctype: 'multipart/form-data',
                processData: false, // Important!
                contentType: false,
                cache: false,
                dataType: "JSON",
                success: function(response) {
                    if (response.result.length > 0) {

                        id = response.result[0].id;
                        tip_title = response.result[0].tip_title;
                        type = response.result[0].type;
                        course_id = response.result[0].course_id;
                        course_type_id = response.result[0].course_type_id;
                        description = response.result[0].description;
                        tip_date = response.result[0].tip_date;
                        web_link = response.result[0].web_link;
                        status = response.result[0].status;

                        if (type == 2 || type == 3) {
                            $('#frm_web_link_div').show();
                            $('#frm_tip_date_div').show();
                        } else {
                            $('#frm_web_link_div').hide();
                            $('#frm_tip_date_div').hide();
                        }
                        $('#record_id').val(id);
                        $('#frm_tip_title').val(tip_title);
                        $('#frm_type').val(type);
                        $('#frm_course').val(course_id);
                        $('#coursetype').val(course_type_id);
                        $('#frm_description').val(description);
                        $('#frm_tip_date').val(tip_date);
                        $('#frm_web_link').val(web_link);
                        $('#frm_status').val(status);
                    }
                    $('#message_box').html('');

                }
            });
        });

        $(document).ready(function() {


            $(".comm_form").submit(function(e) {

                e.preventDefault();

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                var form = $(this);
                var url = form.attr('action');

                errorFlag = true;
                $(this).find("input, select, textarea").each(function() {

                    if ($(this).hasClass("validate[required]") && $(this).val() == "") {

                        $(this).addClass("is-invalid");
                        errorFlag = false;
                    }
                });

                $('#message_box').html('');

                if (errorFlag) {
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: new FormData(this),
                        enctype: 'multipart/form-data',
                        processData: false, // Important!
                        contentType: false,
                        cache: false,
                        dataType: "JSON",
                        success: function(response) {
                            if (response.status == '2' || response.status == '1' || response
                                .status == '0') {

                                if (response.status == '2')
                                    alert_type = 'alert-warning';
                                else if (response.status == '1') {
                                    alert_type = 'alert-success';
                                    $(this).removeClass('is-invalid');
                                } else
                                    alert_type = 'alert-danger';

                                var htt_box = '<div class="alert ' + alert_type +
                                    ' " role="alert">' +
                                    '<button type="button" class="close" data-dismiss="alert">' +
                                    'x</button>' + response.message + '</div>';

                                $('#message_box').html(htt_box);

                                $('#my_data_table').DataTable().ajax.reload();
                                $('.reset_form').click();
                                // setTimeout(function(){ $('#message_box').html(''); }, 6000);
                            }

                        }
                    });
                }

            });

            $(".reset_form").click(function() {
                $('#record_id').val('');
                $('#validate')[0].reset();
            });

            if ($('#my_data_table').length > 0) {
                $('#my_data_table').DataTable({
                    processing: true,
                    serverSide: true,
                    "order": [
                        [3, 'desc']
                    ],
                    "pageLength": 50,
                    ajax: {
                        "url": URL + "/tip_call_data",
                        "type": "GET",
                        "data": function(d) {
                            d.tip_type = $("#tip_type").val();
                        }
                    },
                    columns: [{
                            data: "DT_RowIndex",
                            name: "DT_RowIndex",
                            orderable: false
                        },
                        {
                            data: "tip_type",
                            name: "tip_type"
                        },
                        {
                            data: "course_name",
                            name: "course_name"
                        },
                        {
                            data: "tip_title",
                            name: "tip_title"
                        },
                        {
                            data: "status",
                            name: "status"
                        },
                        {
                            data: "tip_date",
                            name: "tip_date"
                        },
                        {
                            data: "created_at",
                            name: "created_at"
                        },
                        {
                            data: "action",
                            name: "DT_RowIndex",
                            orderable: false
                        },
                    ]
                });
            }
        });
        $(".coursetype").on("change", function() {
            var id = $(this).val();
            var countId = $(this).data("id");
            $.ajax({
                type: "GET",
                dataType: "json",
                url: '{{ url('/getcourse') }}',
                data: {
                    'id': id
                },
                success: function(data) {
                    // elem = $(this).closest('div').find("select").html(); 
                    str = '<option value="">Select Course </option>';
                    for (const iterator of data) {
                        console.log(iterator);
                        str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name +
                            ' </option>';
                    }
                    console.log(str);
                    $("#frm_course").html(str);

                }
            });

        })
    </script>
@endsection
