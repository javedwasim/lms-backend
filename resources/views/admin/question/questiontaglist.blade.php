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
</style>
<div class="card">
    <div class="card-header pb-0">
        <h5>Tag
            <div style="float:right">
                @can('question-create')
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ url('questiontag/add') }}"> Add </a>
                @endcan
            </div>
            <div style="float:right; margin-right: 5px;">
                <!-- <input type="text" id="question_tags" name="question_tags" value="" class="form-control" data-role="tagsinput" placeholder="Filter By Enter Key" /> -->
            </div>
        </h5>
    </div>
    <div class="card-body">
       
        <div class="table-responsive">
            <table id="my_data_table" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="5%">Tag Name</th>
                       
                        <th width="20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adminTag as $key=>$val)
                        <tr>
                            <td width="5%">{{$key+1}}</td>
                            <td width="5%">{{$val->name}}</td>
                        
                            <td width="20%">
                               
                                <a   class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 "  href="{{url('questiontag/edit')}}/{{$val->id}}"><i class="fa fa-pencil"></i></a>
                                <a   class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 "  href="{{url('questiontag/delete')}}/{{$val->id}}"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js" integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js" crossorigin="anonymous"></script>

<script>
  
</script>
@endsection