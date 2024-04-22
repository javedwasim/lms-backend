@extends('layouts.master')
@section('content') 
<div class="card">
  <div class="pb-0 card-header">
    <h5>Books
        <!-- <button type="button" class="float-right btn btn-icon bg-gradient-secondary reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
          Add
        </button> -->

       
            <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ url('book/create') }}"> Add </a>
       

    </h5>
  </div>
  <div class="card-body"> 

      <div class="table-responsive">
          <table id="my_data_table" class="table table-bordered table-striped table-actions" >
              <thead>
                  <tr>
                      <th width="50">No</th>
                      <th >Title</th> 
                      <th>Sub Title  </th>
                      <th>Start Date</th>
                      <th>End Date</th>
                      <th>Description</th>
                     
                  
                      <th width="180">Action</th>
                  </tr>
              </thead>
              <tbody>  
                @foreach($book as $key=> $val)   
                    <tr>
                        <td >{{++$key}}</td>
                        <td>{{Str::limit(strip_tags($val->title), 20, '...')}}</td>
                  
                        <td>{{Str::limit(strip_tags($val->sub_title), 20, '...')}}</td>
                        <td>{{$val->start_time}}</td>
                        <td>{{$val->end_time}}</td>
                        <td>{{Str::limit(strip_tags($val->description), 20, '...')}}</td>
                        
                       
                    
                        <td >
                            <a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm s_btn1 " href="{{url('book')}}/{{$val->id}}/edit" ><i class="fa fa-pencil"></i></a>
                            <form action="{{url('book')}}/{{$val->id}}" method="post" style="display: inline-block;">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash"></i></button>
                            </form>
                            {{-- <a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="{{url('book/delete/')}}/{{$val->id}}" ><i class="fa fa-trash"></i></a> --}}
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
  $(document).ready(function(){
    $('#my_data_table').DataTable();
  })

</script>
@endsection