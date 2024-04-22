@extends('layouts.master')
@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h5>Package
            <!-- <button type="button" class="btn btn-icon bg-gradient-secondary float-right reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
              Add
            </button> -->

            @can('package-create')
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('package.create') }}?type=<?php echo @$_GET['type']; ?>"> Add </a>
            @endcan

        </h5>
    </div>
    <div class="card-body">
        @if($_GET['type']=="course")
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course Type<code>*</code></label>
                    <select class="form-control " id="coursetype" name="course_type_id">
                        <option value="">Select Course Type</option>
                        @foreach ($CourseType as $val)
                        <option value="{{ $val->id }}">{{ $val->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_id" name="course">
                        <option value="">Select Course</option>
                        @foreach ($course as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @endif
        <!-- <div class="col-md-3">
                <div class="form-group">
                   <button onclick="randerTable()">Search</button>
                </div> -->
    </div>

    <div class="table-responsive">
        <table id="my_data_table" class="table table-bordered table-striped table-actions">
            <thead>
                <tr>
                    <th width="50">No</th>
                    @if($_GET['type']=="course")

                    <th>{{@$_GET['type']=="seminar" ?  "Seminar":(@$_GET['type']=="book"?"Book":(@$_GET['type']=="flashcard"?"FlashCard":"Course"))}} Id</th>
                    <th>Package Id</th>
                    @endif
                    <th>Package Title</th>
                    <th>{{@$_GET['type']=="seminar" ?  "Seminar":(@$_GET['type']=="book"?"Book":(@$_GET['type']=="flashcard"?"FlashCard":"Course"))}} </th>
                    <th width="100">Status</th>
                    <th width="90">Expire Date</th>
                    <th width="90">Create Date</th>
                    @if(@$_GET['type']=="course")
                    <th width="100" class="text-center">Assign</th>
                    @endif

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
            <form id="validate" action="{{ route('package.store') }}" class="form-horizontal comm_form" method="POST" role="form">
                @csrf
                <div class="modal-body">
                    <div id="message_box"></div>
                    <input type="hidden" id="record_id" class="form-control" name="record_id" value="" />
                    <div class="form-group">
                        <label class="col-form-label">Title <code>*</code>:</label>
                        <input type="text" class="form-control  validate[required]" name="package_title" id="frm_package_title" value="">
                    </div>
                    <div class="form-group">
                        <label class="col-form-label">Package For Month <code>*</code>:</label>
                        <input type="text" class="form-control  validate[required] decimal_number" name="package_for_month" id="frm_package_for_month" maxlength="2" value="">
                    </div>
                    <div class="form-group">
                        <label class="col-form-label">Description <code>*</code>:</label>
                        <textarea class="form-control  validate[required]" name="package_description" id="frm_description"></textarea>
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
                    <label class="col-form-label">Title:</label>
                    <span class="form-control" id="show_package_title"></span>
                </div>
                <div class="form-group">
                    <label class="col-form-label">Description:</label>
                    <span class="form-control" id="show_description"></span>
                </div>
                <div class="form-group">
                    <label class="col-form-label">Package For Month:</label>
                    <span class="form-control" id="show_package_for_month"></span>
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
    var URL = '{{ url("/") }}';

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
            url: URL + "/package_get_data",
            data: form_data,
            enctype: 'multipart/form-data',
            processData: false, // Important!
            contentType: false,
            cache: false,
            dataType: "JSON",
            success: function(response) {
                if (response.result.length > 0) {
                    package_title = response.result[0].package_title;
                    description = response.result[0].description;
                    package_for_month = response.result[0].package_for_month;
                    status_name = response.result[0].status_name;

                    $('#show_package_title').text(package_title);
                    $('#show_description').text(description);
                    $('#show_package_for_month').text(package_for_month);
                    $('#show_status').text(status_name);
                }
            }
        });
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
            url: URL + "/package_get_data",
            data: form_data,
            enctype: 'multipart/form-data',
            processData: false, // Important!
            contentType: false,
            cache: false,
            dataType: "JSON",
            success: function(response) {
                if (response.result.length > 0) {

                    id = response.result[0].id;
                    package_title = response.result[0].package_title;
                    description = response.result[0].description;
                    package_for_month = response.result[0].package_for_month;
                    status = response.result[0].status;
                    $('#record_id').val(id);
                    $('#frm_package_title').val(package_title);
                    $('#frm_description').val(description);
                    $('#frm_package_for_month').val(package_for_month);
                    $('#frm_status').val(status);

                }
                $('#message_box').html('');

            }
        });
    });
    let table = '';
    // $(document).ready(function() {


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
        let type = "<?php echo @$_GET['type']; ?>";
        let allColums = [];
        if (type == "course") {
            allColums = [{
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false
                },
                {
                    data: "course_id",
                    name: "course_id"
                },
                {
                    data: "package_id",
                    name: "package_id"
                },
                {
                    data: "package_title",
                    name: "package_title"
                },
                {
                    data: "course_name",
                    name: "course_name"
                },
                {
                    data: "status",
                    name: "status"
                },
                {
                    data: "expire_date",
                    name: "expire_date"
                },
                {
                    data: "created_at",
                    name: "created_at"
                },

                {
                    data: "assign",
                    name: "assign"
                },
                {
                    data: "action",
                    name: "DT_RowIndex",
                    orderable: false
                },
            ];
        } else {
            allColums = [{
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false
                },
                {
                    data: "package_title",
                    name: "package_title"
                },
                {
                    data: "course_name",
                    name: "course_name"
                },
                {
                    data: "status",
                    name: "status"
                },
                {
                    data: "expire_date",
                    name: "expire_date"
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
            ];
        }

        table = $('#my_data_table').DataTable({
            processing: true,
            serverSide: true,
            // "ordering": false,
            "pageLength": 50,

            "ajax": {
                "url": URL + "/package_call_data",
                "type": "get",

                "data": function(d) {
                    d.type = type,
                        d.course = $("#course_id").val();

                }
            },

            columns: allColums
        });
    }
    // });
    $("#course_id").change(function() {
        table.draw();
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
                str = '<option value="">Select Course </option>';
                for (const iterator of data) {
                    console.log(iterator);
                    str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
                }
                console.log(str);
                $("#course_id").html(str);

            }
        });

    })

    // function randerTable(){
    //     // console.log($("#course_id").val());
    //     // $('#my_data_table').DataTable().ajax.reload();
    //     table.draw();
    // }
</script>
@endsection