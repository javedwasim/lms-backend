@extends('layouts.master')
@section('content')
<div class="card">
  <div class="pb-0 card-header">
    <h5>Seminars

      <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ url('seminar/create/') }}"> Add </a>

    </h5>
  </div>
  <div class="card-body">
    <form>
      <div class="row">


        <div class="col-md-3">
          <div class="form-group">
            <label>From Date<code>*</code></label>
            <input type="date" name="fromdate" id="fromdate" class="form-control" value="{{@$_GET['fromdate']}}" onchange="redrawTable()">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>To Date<code>*</code></label>
            <input type="date" name="todate" id="todate" class="form-control"  value="{{@$_GET['todate']}}" onchange="redrawTable()">
          </div>
        </div>
        <div class="col-md-3" style=" margin-top: 30px;  margin-bottom: 0px;">
          <div class="form-group">

            <input type="submit" value="Search" class="btn btn-primary">
            <a href="{{url('seminar')}}" class="btn btn-default"> Reset </a>

          </div>
        </div>

      </div>
    </form>
    <div class="table-responsive">
      <table id="my_data_table" class="table table-bordered table-striped table-actions">
        <thead>
          <tr>
            <th width="50">No</th>
            <th>Title</th>
            <th>Sub Title </th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Description</th>
            <th width="180">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($seminar as $key=> $val)
          <tr>
            <td>{{++$key}}</td>
            <td>{{Str::limit(strip_tags($val->title), 20, '...')}}</td>
                  
                        <td>{{Str::limit(strip_tags($val->sub_title), 20, '...')}}</td>
            <td>{{$val->start_time}}</td>
            <td>{{$val->end_time}}</td>
            <td>{{Str::limit(strip_tags($val->description), 20, '...')}}</td>


            <td>
              <a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm s_btn1 " href="{{url('seminar')}}/{{$val->id}}/edit"><i class="fa fa-pencil"></i></a>
              <form action="{{url('seminar/')}}/{{$val->id}}" method="POST" style="display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 "><i class="fa fa-trash"></i></button>
              </form>
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
<script>
  $(document).ready(function() {
    $('#my_data_table').DataTable();
  })
</script>
@endsection