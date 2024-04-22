@extends('layouts.master')


@section('content')
<style>
    table tr td:nth-child(4) {
        white-space: break-spaces;
        width: 100%;
    }
</style>
<div class="card">
    <div class="pb-0 card-header">
        <h5>Tutorial
            @can('tutorial-create')
            <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('tutorial.create') }}"> Add </a>
            <a class="float-right btn btn-icon bg-gradient-primary" href="{{ url('settutorialorder') }}" style="margin-right:10px"> Tutorial Orders </a>
            <a class="float-right btn btn-icon bg-gradient-danger" id="assign_btn" style="margin-right:10px" >Delete Selected</a>
            @endcan
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_id" name="course_id" onchange="redrawTable()">
                        <option value="">Select Course</option>
                        @foreach ($allCourse as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}
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
        <form id="assign_form" action="{{ url('deletetutorial') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
        @csrf
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                    <th width="50">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th>
                        <th width="50">No</th>
                        <th>Category</th>
                        {{-- <th>Order Of Tutorial</th> --}}
                        <th>Tutorial Name</th>
                        <th>Comment</th>
                        <th width="100">Status</th>
                        <th width="90">Created Date</th>
                        <th width="180">Action</th>
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
      function redrawTable()
        {
            $('#my_data_table').DataTable().ajax.reload();
        }
    $(document).ready(function() {

        $("#assign_btn").click(function() {

let confirmOut=  confirm ('Are you really want to delete selected question');
console.log(confirmOut)
if(confirmOut===true)
{
 $("#assign_form").submit();
}
         
     });
        $("#course_id").on("change",function(){
            let courseId=$(this).val();
            $.ajax({
                type: "POST",
                dataType: "text",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url: '{{url("/getcoursecategory")}}',
                data: {'course_id': courseId},
                success: function(res){
                   $("#category").html(res);
                   
                }
            });
        })

        var URL = '{{url("/")}}';

        if ($('#my_data_table').length > 0) {
            $('#my_data_table').DataTable({
                processing: true,
                serverSide: true,
                aLengthMenu: [
                    [25, 50, 100, 200, -1],
                    [25, 50, 100, 200, "All"]
                ],
                "ordering": false,
                "pageLength": 50,
            
                ajax: {
                    "url": URL + "/tutorial_call_data",
                    "type": "GET",
                    "data": function(d) {
                     
                        d.category = $("#category").val();
                        d.course_id = $("#course_id").val();
                    }
                },
                columns: [
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
                        data: "category_name",
                        name: "category_name"
                    },  
                  /*   {
                        data: "order_tutorial",
                        name: "order_tutorial"
                    }, */
                    {
                        data: "chapter_name",
                        name: "chapter_name"
                    },
                    {
                        data: "comment",
                        name: "comment"
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
                        data: "action",
                        name: "DT_RowIndex",
                        orderable: false
                    },
                ]
            });
        }
    });
</script>
@endsection