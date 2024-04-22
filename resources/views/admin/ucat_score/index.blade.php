<link href="https://cdn.jsdelivr.net/timepicker.js/latest/timepicker.min.css" rel="stylesheet" />
@extends('layouts.master')
@section('content')
<style>
    ._jw-tpk-container ol>li {
        float: left !important;
        display: inline-block !important;
    }
</style>

<div class="card">
    <div class="pb-0 card-header">
        <h5> Score
            <a href="{{url('ucatscore/create')}}" class="float-right btn btn-icon bg-gradient-secondary reset_form">
                Add
            </a>
        </h5>
    </div>
    <div class="card-body">
    <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Couser Type<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_type_id" name="course_type_id" onchange="redrawTable()">
                        <option value="">Select Couser Type</option>
                        @foreach ($CourseType as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->name }}</option>
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
        </div>

        <div class="table-responsive">
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Course Type</th>
                        <th>Category Name</th>
                        <th>Band Name</th>
                        <th>Min Percent</th>
                        <th width="100">Max Percent</th>
                        <th width="100"> Score</th>
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
<script src="https://cdn.jsdelivr.net/timepicker.js/latest/timepicker.min.js"></script>

<script>
    var URL = "{{url('/')}}";
    function redrawTable()
        {
            $('#my_data_table').DataTable().ajax.reload();
        }
 
    $(document).ready(function() {


        if ($('#my_data_table').length > 0) {
            $('#my_data_table').DataTable({
                processing: true,
                serverSide: true,
                "ordering": false,
                "pageLength": 50,
               
                ajax: {
                    "url": URL + "/ucat_call_data",
                    "type": "GET",
                    "data": function(d) {
                     
                        d.categoryId = $("#category").val();
                        d.course_type_id = $("#course_type_id").val();
                    }
                },
                columns: [{
                        data: "DT_RowIndex",
                        name: "DT_RowIndex",
                        orderable: false
                    },
                    {
                        data: "course_type",
                        name: "course_type"
                    }, 
                    {
                        data: "category_name",
                        name: "category_name"
                    }, 
                    {
                        data: "band",
                        name: "band"
                    },
                    {
                        data: "min_score",
                        name: "min_score"
                    },
                    {
                        data: "max_score",
                        name: "max_score"
                    },
                    {
                        data: "ucat_score",
                        name: "ucat_score"
                    },

                    {
                        data: "action",
                        name: "DT_RowIndex",
                        orderable: false
                    }
                ]
            });
        }
    });
</script>
@endsection