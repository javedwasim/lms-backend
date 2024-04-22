@extends('layouts.master')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" crossorigin="anonymous">
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
<div class="card">
    <div class="card-header pb-0">
        <h5>Question
            <div style="float:right">
                @can('question-create')
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('question.create') }}"> Add </a>
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ url('questiontag') }}"style="margin-right:10px"> Tag </a>
                <a class="btn btn-icon bg-gradient-danger float-right" id="assign_btn" style="margin-right:10px" >Delete Selected</a>
                @endcan
            </div>
            <div style="float:right; margin-right: 5px;">
                <!-- <input type="text" id="question_tags" name="question_tags" value="" class="form-control" data-role="tagsinput" placeholder="Filter By Enter Key" /> -->
            </div>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
           

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
                    <label>Sub Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="subcategory" name="subcategory" onchange="redrawTable()">
                        <option value="">Select Sub Category</option>

                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tag<code>*</code></label>
                    <select class="form-control  validate[required]" id="tag" name="tag" onchange="redrawTable()">
                        <option value="">Select Tag</option>
                        @foreach ($adminTag as $categoryDt)
                        <option value="{{$categoryDt->name}}">{{ $categoryDt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Type<code>*</code></label>
                    <select class="form-control  validate[required]" id="type" name="type" onchange="redrawTable()">
                        <option value="">Select Type</option>

                        <option value="1">Question left Option right</option>
                        <option value="2">Drag and Drop</option>
                        <option value="3">No division</option>
                        <option value="4">Type 4</option>
                        <option value="5">Type 5</option>

                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
        <form id="assign_form" action="{{ url('deletequestion') }}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
        @csrf
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                    <th width="50">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th>
                        <th width="5%">No</th>
                        <th width="5%">Question Id</th>
                        <th width="5%">Category</th>
                        <th width="5%">Sub Category</th>
                        <th width="5%">Type</th>
                        <th width="5%">Tags</th>
                        <th width="10%">Question<br> Name</th>
                        <th width="10%">Rating</th>
                        <th width="10%">Comment</th>
                        <th width="10%">Feedback</th>

                        <th width="5%">Status</th>
                        <th width="5%">Created <br>Date</th>
                        <th width="20%">Action</th>
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

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js" integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js" crossorigin="anonymous"></script>

<script>

$("#assign_btn").click(function() {

   let confirmOut=  confirm ('Are you really want to delete selected question');
   console.log(confirmOut)
   if(confirmOut===true)
   {
    $("#assign_form").submit();
   }
            
        });
    $('#question_tags').tagsinput('add', 'tags data');
    $(document).on('click', '.assign_que_id', function() {
        this.value = this.checked ? 1 : 0;
    });
    $(document).on('click', '#select_all', function() {
        this.value = this.checked ? 1 : 0;
        if (this.checked) {
            $('.assign_que_id').prop('checked', true);
        } else {
            $('.assign_que_id').prop('checked', false);

        }
    });

    function redrawTable() {
        $('#my_data_table').DataTable().ajax.reload();
    }

    $(document).ready(function() {

        var URL = '{{url("/")}}';

        $("#question_tags").change(function() {
            $('#my_data_table').DataTable().ajax.reload();
        });
        $("#category").on("change", function() {
            $.ajax({
                type: "POST",
                url: URL + "/get_subcategory_ajax",
                data: {
                    category_id: $(this).val(),
                    _token: "{{csrf_token()}}"
                },

                success: function(response) {
                    console.log(response);
                    if (response.data.length > 0) {
                        let str = '<option value="">Select Sub Category</option>';
                        for (const iterator of response.data) {
                            str += '<option value="' + iterator.id + '">' + iterator.sub_category_name + '</option>';
                        }
                        $("#subcategory").html(str);

                    }
                }
            });
        })


        if ($('#my_data_table').length > 0) {
            $('#my_data_table').DataTable({
                processing: false,
                aLengthMenu: [
                    [25, 50, 100, 200, -1],
                    [25, 50, 100, 200, "All"]
                ],
                serverSide: true,
                "order": [
                    [7, 'desc']
                ],
                "pageLength": 50,
                ajax: URL + "/question_call_data",
                ajax: {
                    "url": URL + "/question_call_data",
                    "type": "GET",
                    "data": function(d) {
                        d.question_tags = $("#question_tags").val();
                        d.type = $("#type").val();
                        d.tag = $("#tag").val();
                        d.category = $("#category").val();
                        d.subcategory = $("#subcategory").val();
                    },
                    beforeSend: function () {
                        $('.overlay').show();
                    },
                    complete: function () {
                        $('.overlay').hide();
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
                        data: "question_id",
                        name: "question_id"
                    },
                    {
                        data: "category_name",
                        name: "category_name"
                    },

                    {
                        data: "subcategory_name",
                        name: "subcategory_name"
                    },
                    {
                        data: "question_type_name",
                        name: "question_type_name"
                    },
                    {
                        data: "question_tags",
                        name: "question_tags"
                    },
                    {
                        data: "question_name",
                        name: "question_name"
                    },
                    {
                        data: "rating",
                        name: "DT_RowIndex",
                        // orderable: false
                    },
                    {
                        data: "comment",
                        name: "DT_RowIndex",
                        // orderable: false
                    },
                    {
                        data: "feedback",
                        name: "DT_RowIndex",
                        // orderable: false
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