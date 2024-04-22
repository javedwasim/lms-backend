@extends('layouts.master')


@section('content')    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Permission   
            @can('permission-create')
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('permission.create') }}"> Add </a>
            @endcan
        </h5>
      </div>
      <div class="card-body"> 

          <div class="table-responsive">
              <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                  <thead>
                      <tr>
                        <th>No</th>
                        <th>Name</th> 
                        <th width="280px">Action</th>
                      </tr>
                  </thead>
                  <tbody>       
                       @foreach ($permissions as $permission)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>{{ $permission->name }}</td> 
                                <td>
                                    <form action="{{ route('permission.destroy',$permission->id) }}" method="POST">
                                        <a class="btn btn-sm btn-info" href="{{ route('permission.show',$permission->id) }}"><i class="fa fa-eye"></i></a>
                                        @can('permission-edit')
                                        <a class="btn btn-sm btn-primary" href="{{ route('permission.edit',$permission->id) }}"><i class="fa fa-edit"></i></a>
                                        @endcan


                                        @csrf
                                        @method('DELETE')
                                        @can('permission-delete')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        @endcan
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

            var URL = '{{url('/')}}';
   
            $('#my_data_table').DataTable();
        });
     </script>  
@endsection