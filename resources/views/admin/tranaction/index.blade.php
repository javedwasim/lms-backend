@extends('layouts.master')
@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h5>Subscriptions
        </h5>
        <a href="{{url('public/transaction.csv')}}" class="btn btn-primary"> Export</a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>User name<code>*</code></label>
                    <input type="text" name="name" id="name" class="form-control" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>User Email<code>*</code></label>
                    <input type="text" name="email" id="email" class="form-control">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Packages<code>*</code></label>
                    <select class="form-control  validate[required]" id="package" name="package" >
                        <option value="">Select Packages</option>
                        @foreach ($package as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->package_title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course" name="course" >
                        <option value="">Select Course</option>
                        @foreach ($course as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status<code>*</code></label>
                    <select class="form-control  validate[required]" id="status" name="status" >
                        <option value="">Select Status</option>
                        <option value="1">Success</option>
                        <option value="0">pending</option>


                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>From Date<code>*</code></label>
                    <input type="date" name="fromdate" id="fromdate" class="form-control" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>To Date<code>*</code></label>
                    <input type="date" name="todate" id="todate" class="form-control" >
                </div>
            </div>
            <div class="col-md-3" style=" margin-top: 30px;  margin-bottom: 0px;">
          <div class="form-group">

            <input type="button" value="Search" class="btn btn-primary" onclick="redrawTable()">
            <a href="javascript:" onclick="window.location.reload()" class="btn btn-default"> Reset </a>

          </div>
        </div>
        </div>
        <div class="table-responsive">
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>User Name </th>
                        <th>User Email </th>
                        <th>Package Name </th>
                        <th>Package Amount</th>
                        <th>{{@$type=="seminar" ?  "Seminar":(@$type=="book"?"Book":(@$type=="flashcard"?"FlashCard":"Course"))}} </th>
                        <th width="100">Enroll Date</th>
                        <th width="90">Expire On</th>
                        <th width="90">Status</th>

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
    var URL = '{{ url(' / ') }}';

    function redrawTable()
        {
            $('#my_data_table').DataTable().ajax.reload();
        }

    $(document).ready(function() {

        if ($('#my_data_table').length > 0) {
            let type = "<?php echo @$type; ?>";
            let allColums = [];

            allColums = [{
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false
                },
                {
                    data: "user_name",
                    name: "user_name"
                },
                {
                    data: "user_email",
                    name: "user_email"
                },
                {
                    data: "package_name",
                    name: "package_name"
                },
                   {
                    data: "amount",
                    name: "amount"
                },
                {
                    data: "name",
                    name: "name"
                },
                {
                    data: "enroll_date",
                    name: "enroll_date"
                },

                {
                    data: "enroll_end",
                    name: "enroll_end"
                },
                {
                    data: "status",
                    name: "status"
                },
                {
                    data: "action",
                    name: "DT_RowIndex",
                    orderable: false
                },
            ];


            $('#my_data_table').DataTable({
                processing: true,
                serverSide: true,
                "ordering": false,
                "pageLength": 50,
                "ajax": {
                    "url": URL + "/tranaction_call_data",
                    "type": "get",
                    "data": function(d) {
                        d.type = type;
                        d.name = $("#name").val();
                        d.email = $("#email").val();
                        d.package = $("#package").val();
                        d.status = $("#status").val();
                        d.fromdate = $("#fromdate").val();
                        d.todate = $("#todate").val();
                        d.course = $("#course").val();
                    }
                },

                columns: allColums
            });
        }
    });
</script>
@endsection