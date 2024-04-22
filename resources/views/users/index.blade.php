@extends('layouts.master')
@section('content')
<style>
    #course_dt_id {
        padding: 5px;
        margin-right: 8px;
    }
</style>
<div class="card">
    <div class="pb-0 card-header">
        <h5>Users
            <div style="float:right; margin-left:5px">
                <a class="float-right btn btn-icon bg-gradient-danger" href="javascript:" onclick="getcheckbox()"> Performance </a>
            </div>
            <div style="float:right; margin-left:5px">
                <form id="sendmailform" action="{{ url('sendemail') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="emailUserids" name="emailUserids[]">
                    <a href="javascript:;" class="float-right btn btn-icon bg-gradient-default reset_form" onclick="sendEmailToUser()">Send Mail</a>
                    <form>
            </div>
            <div style="float:right; margin-left:5px">
                <button type="button" class="float-right btn btn-icon bg-gradient-info reset_form" data-bs-toggle="modal" data-bs-target="#addForm2" onclick="getcheckbox1()">
                    Unenroll Course
                </button>
            </div>
            <div style="float:right; margin-left:5px">
                <button type="button" class="float-right btn btn-icon bg-gradient-warning reset_form" data-bs-toggle="modal" data-bs-target="#addForm1" onclick="getcheckbox1()">
                    Enroll Course
                </button>
            </div>
            <div style="float:right; margin-left:5px">
                <button type="button" class="float-right btn btn-icon bg-gradient-success reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
                    Import CSV
                </button>
            </div>
            <div style="float:right; margin-left:5px">
                <a class="float-right btn btn-icon bg-gradient-primary" href="{{ url('user_csv_export') }}"> Export CSV </a>
            </div>
            <div style="float:right">
                @can('user-create')
                <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('users.create') }}"> Add </a>
                @endcan
            </div>

        </h5>
    </div>
    <div class="modal fade" id="addForm3" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Send Mail</h5>

                </div>
                <form id="validate" action="{{ url('sendmail') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div id="message_box"></div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="hidden" value="" name="userids" class="selectedUserId">
                                <label>Subject<sub>*</sub></label>
                                <input type="text" class="form-control" name="subject">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="hidden" value="" name="userids" class="selectedUserId">
                                <label>Message<sub>*</sub></label>
                                <textarea name="message" class="form-control"></textarea>
                            </div>
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
    <div class="modal fade" id="addForm2" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Unenroll Course</h5>

                </div>
                <form id="validate" action="{{ url('unassignCourse') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div id="message_box"></div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Course Type<sub>*</sub></label>
                                <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="1">
                                    <option value="">Select Course Type</option>
                                    @foreach($CourseType as $val)
                                    <option value="{{$val->id}}">{{$val->name}}</option>
                                    @endforeach


                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="hidden" value="" name="userids" class="selectedUserId">
                                <label>Enroll Course<sub>*</sub></label>
                                <select id="course" name="course" class="form-control course" data-id="1">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $val)
                                    <option value="{{$val->id}}">{{$val->course_name}}</option>
                                    @endforeach


                                </select>
                            </div>
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
    <div class="modal fade" id="addForm1" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Enroll Course</h5>

                </div>
                <form id="validate" action="{{ url('assignCourse') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div id="message_box"></div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Course Type<sub>*</sub></label>
                                <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="1">
                                    <option value="">Select Course Type</option>
                                    @foreach($CourseType as $val)
                                    <option value="{{$val->id}}">{{$val->name}}</option>
                                    @endforeach


                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="hidden" value="" name="userids" id="userids">
                                <label>Enroll Course<sub>*</sub></label>
                                <select id="course" name="course" class="form-control course" data-id="1">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $val)
                                    <option value="{{$val->id}}">{{$val->course_name}}</option>
                                    @endforeach


                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Package<sub>*</sub></label>
                                <select id="package" name="package" class="form-control package1">



                                </select>
                            </div>
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

    <div class="modal fade" id="addForm" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Import Student</h5>
                    &nbsp; (<a href="{{url('/uploads/studentcsv/studentsamplenew.csv')}}">Download Sample</a>)
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form id="validate" action="{{ url('userimport') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div id="message_box"></div>
                        <input type="hidden" id="record_id" class="form-control" name="record_id" value="" />
                        <div class="form-group">
                            <label class="col-form-label">Import <code>*</code>:</label>
                            <input type="file" class="form-control  validate[required]" name="file" id="file" value="">
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
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course Type<sub>*</sub></label>
                    <select id="coursetype" name="course_type_id[]" class="form-control coursetype" data-id="1">
                        <option value="">Select Course Type</option>
                        @foreach($CourseType as $val)
                        <option value="{{$val->id}}">{{$val->name}}</option>
                        @endforeach


                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label> Course</label>
                <select name="course_id" id="course_dt_id" class="form-control course" style="margin-bottom: 13px;">
                    <option value="">Filter By Course</option>
                    @foreach($courses as $courseVal)
                    <option value="{{ $courseVal->id }}">{{ $courseVal->course_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label> Sign Up Date</label>
                <input type="date" class="form-control" value="" id="signupdate">
            </div>
            <div class="col-md-3">
                <label>Last Login Date</label>
                <input type="date" class="form-control" value="" id="last_login_date">
            </div>

        </div>

        <div class="table-responsive">
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" name="select_all" id="select_all">
                        </th>

                        <th width="50">No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th width="200">Enrolled Course</th>
                        <th width="100">Status</th>
                        <th width="90">Created Date</th>
                        <th width="90">Last Login</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(".course").on("change", function() {
        var id = $(this).val();
        var countId = $(this).data("id");
        $.ajax({
            type: "GET",
            dataType: "json",
            url: '{{url("/getpackage")}}',
            data: {
                'id': id
            },
            success: function(data) {
                str = '';
                for (const iterator of data) {
                    console.log(iterator);
                    str = str + '<option value="' + iterator.id + '"> ' + iterator.package_title + ' </option>';
                }
                console.log(str);
                $(".package" + countId).html(str);

            }
        });

    })
    $(".coursetype").on("change", function() {
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
                str = '<option value="">Select Course </option>';
                for (const iterator of data) {
                    console.log(iterator);
                    str = str + '<option value="' + iterator.id + '"> ' + iterator.course_name + ' </option>';
                }
                console.log(str);
                $(".course").html(str);

            }
        });

    })

    function getcheckbox() {
        userids = [];
        $(".userids:checked").each(function() {
            userids.push($(this).val());
        });

        if (userids.length == 0) {
            alert("Please Select Min 1");
            return;
        } else {
            var URL = '{{url("/")}}';
            $.ajax({
                type: "POST",
                url: URL + "/redirectToReact",
                data: {
                    userids: userids,
                    "_token": "<?php echo csrf_token(); ?>"
                },


                success: function(response) {
                    window.location.href = "{{env('FRONTEND_URL')}}/performance/" + response
                }
            });
        }
    }

    $(document).on('click', '#select_all', function() {
        this.value = this.checked ? 1 : 0;
        if (this.checked) {
            $('.userids').prop('checked', true);
        } else {
            $('.userids').prop('checked', false);
        }
    });
    $(document).ready(function() {

        var URL = '{{url("/")}}';

        $("#course_dt_id").change(function() {
            $('#my_data_table').DataTable().ajax.reload();
        });

        $("#last_login_date").change(function() {
            $('#my_data_table').DataTable().ajax.reload();
        });



        $("#signupdate").change(function() {
            $('#my_data_table').DataTable().ajax.reload();
        });

        if ($('#my_data_table').length > 0) {
            $('#my_data_table').DataTable({
                processing: true,
                serverSide: true,
                aLengthMenu: [
                    [25, 50, 100, 200, -1],
                    [25, 50, 100, 200, "All"]
                ],
                "ordering": true,
                "pageLength": 50,
                ajax: {
                    "url": URL + "/user_call_data",
                    "type": "GET",
                    "data": function(d) {
                        d.course_id = $("#course_dt_id").val();
                        d.signupdate = $("#signupdate").val();
                        d.last_login_date = $("#last_login_date").val();
                    }
                },
                columns: [{
                        data: "checkbox",
                        name: "checkbox",
                        orderable: false
                    },
                    {
                        data: "DT_RowIndex",
                        name: "DT_RowIndex",
                        orderable: false
                    },
                    {
                        data: "name",
                        name: "name"
                    },
                    {
                        data: "email",
                        name: "email"
                    },
                    {
                        data: "course_list",
                        name: "course_list"
                    },
                    {
                        data: "status",
                        name: "status"
                    },
                    {
                        data: "created_at",
                        name: "created_at"
                    },
                    {
                        data: "last_login",
                        name: "last_login"
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

    function getcheckbox1() {
        userids = [];
        $(".userids:checked").each(function() {
            userids.push($(this).val());
        });
        $("#userids").val(userids);
        $(".selectedUserId").val(userids);
    }

    // send on mail page
    function sendEmailToUser() {
        useridsDta = [];
        $(".userids:checked").each(function() {
            useridsDta.push($(this).val());
        });
        $("#emailUserids").val(useridsDta);
        $('#sendmailform').submit();

    }
</script>
@endsection