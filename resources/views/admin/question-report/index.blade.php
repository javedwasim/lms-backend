@extends('layouts.master')
@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h5>Question Report
        <a class="btn btn-icon bg-gradient-danger float-right" id="assign_btn" style="margin-right:10px" >Delete Selected</a>
        </h5>

    </div>
    <style>
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

        .bootstrap-tagsinput input {
            margin: 1px !important;
            margin-top: 7px !important;
        }

        .dataTables_wrapper .loader {
            display: none;
        }

        .dataTables_processing .loader {
            display: block;
        }
        .overlay {
            left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        position: fixed;
        background: #222222d9;
        z-index: 99999;
    }

    .overlay__inner {
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        position: absolute;
    }

    .overlay__content {
        left: 50%;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .spinner {
        width: 75px;
        height: 75px;
        display: inline-block;
        border-width: 2px;
        border-color: rgba(255, 255, 255, 0.05);
        border-top-color: #fff;
        animation: spin 1s infinite linear;
        border-radius: 100%;
        border-style: solid;
    }

    @keyframes spin {
      100% {
        transform: rotate(360deg);
      }
    }
    </style>
    <div class="overlay">
        <div class="overlay__inner">
            <div class="overlay__content"><span class="spinner"></span></div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
        <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course" name="course" onchange="redrawTable()">
                        <option value="">Select Course</option>
                        @foreach ($getCourse as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="category" name="category" onchange="redrawTable()">
                        <option value="">Select Category</option>
                        @foreach ($getCategory as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Label<code>*</code></label>
                    <select class="form-control  validate[required]" id="label" name="label" onchange="redrawTable()">
                        <option value="">Select Label</option>
                        <option value="1">Resolved</option>
                        <option value="0">pending</option>


                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>From Date<code>*</code></label>
                    <input type="date" name="fromdate" id="fromdate" class="form-control" onchange="redrawTable()">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>To Date<code>*</code></label>
                  <input type="date" name="todate" id="todate" class="form-control" onchange="redrawTable()">
                </div>
            </div>
        </div>
        <div class="table-responsive">
        <form id="assign_form" action="{{ url('deletequestionreport') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
        @csrf
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                    <th width="50">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th>
                        <th width="50">No</th>
                        <th>Question Id</th>
                        <th>User Name </th>
                        <th>Issue </th>
                        <th>Type </th>
                        <th>Course </th>
                        <th>Category</th>
                        <th>Date Reported</th>
                        <th>Admin Notes</th>
                        <th>Label</th>
                        <th>Fix Question</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
</form>
        </div>
    </div>
</div>
@endsection


@section('script')
<script>
    var URL = '{{ url("/") }}';

    function redrawTable() {
        $('#my_data_table').DataTable().ajax.reload();
    }

    $(document).ready(function() {
        $(document).on('click', '#select_all', function() {
        this.value = this.checked ? 1 : 0;
        if (this.checked) {
            $('.assign_que_id').prop('checked', true);
        } else {
            $('.assign_que_id').prop('checked', false);

        }
    });
    $("#assign_btn").click(function() {

let confirmOut=  confirm ('Are you really want to delete selected question');
console.log(confirmOut)
if(confirmOut===true)
{
 $("#assign_form").submit();
}

     });

        if ($('#my_data_table').length > 0) {
            let type = "<?php echo @$type; ?>";

            let allColums = [];

            allColums = [
                {
                        data: "assign_checkbx",
                        name: "assign_checkbx",
                        orderable: false
                    },{
                    data: "DT_RowIndex",
                    name: "DT_RowIndex",
                    orderable: false
                },
                {
                    data: "questionid",
                    name: "questionid"
                },
                {
                    data: "user_name",
                    name: "user_name"
                },
                {
                    data: "issue",
                    name: "issue",
                    render: function(data, type, full, meta) {console.log(full.issue.truncated);
                        if (type === 'display') {
                            // Display truncated text in the cell
                            return '<span title="' + full.issue.full + '">' + full.issue.truncated + '</span>';
                        }
                    }
                },
                {
                    data: "type",
                    name: "type"
                },
                {
                    data: "course",
                    name: "course"
                },
                {
                    data: "category",
                    name: "category"
                },

                {
                    data: "date_reported",
                    name: "date_reported"
                },
                {
                    data: "admin_notes",
                    name: "admin_notes"
                },
                {
                    data: "label",
                    name: "label"
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
                aLengthMenu: [
                    [25, 50, 100, 200, -1],
                    [25, 50, 100, 200, "All"]
                ],
                "ordering": false,
                "pageLength": 50,
                "ajax": {
                    "url": URL + "/question-report/call_data",
                    "type": "get",
                    "data": function(d) {
                        d.course = $("#course").val();
                        d.category = $("#category").val();
                        d.label = $("#label").val();
                        d.fromdate = $("#fromdate").val();
                        d.todate = $("#todate").val();


                    },
                    beforeSend: function () {
                        $('.overlay').show();
                    },
                    complete: function () {
                        $('.overlay').hide();
                    }
                },

                columns: allColums
            });
        }
    });
</script>
@endsection
