@extends('layouts.master')
@section('content') 
<div class="card">
  <div class="pb-0 card-header">
    <h5>Testimonial
        <!-- <button type="button" class="float-right btn btn-icon bg-gradient-secondary reset_form" data-bs-toggle="modal" data-bs-target="#addForm">
          Add
        </button> -->

       
            <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ url('testimonial/create?type=') }}{{$type}}"> Add </a>
       

    </h5>
  </div>
  <div class="card-body"> 

      <div class="table-responsive">
          <table id="my_data_table" class="table table-bordered table-striped table-actions" >
              <thead>
                  <tr>
                      <th width="50">No</th>
                      <th >Testimonial</th> 
                      <th>Submited by </th>
                      <th>Position</th>
                     
                  
                      <th width="180">Action</th>
                  </tr>
              </thead>
              <tbody>  
                @foreach($testimonial as $key=> $val)   
                    <tr>
                        <td >{{++$key}}</td>
                    
                        <td>{{Str::limit(strip_tags($val->testimonial), 20, '...')}}</td>
                        <td>{{$val->submited_by}}</td>
                        <td>{{$val->position}}</td>
                       
                    
                        <td >
                            <a class="btn bg-gradient-secondary btn-rounded btn-condensed btn-sm s_btn1 " href="{{url('testimonial/')}}/{{$val->id}}/edit?type={{$type}}" ><i class="fa fa-pencil"></i></a>
                            <form action="{{url('testimonial/')}}/{{$val->id}}" method="post" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 "><i class="fa fa-trash"></i></button>
                            </form>
                            {{-- <a class="btn bg-gradient-primary btn-rounded btn-condensed btn-sm s_btn1 " href="{{url('testimonial/')}}/{{$val->id}}" ><i class="fa fa-trash"></i></a> --}}
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