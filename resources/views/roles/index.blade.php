@extends('layouts.master')


@section('content')    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Role Management 
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
                      @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ ++$i }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                <a class="btn btn-sm btn-info" href="{{ route('roles.show',$role->id) }}"><i class="fa fa-eye"></i></a>
                                @can('role-edit')
                                    <a class="btn btn-sm btn-primary" href="{{ route('roles.edit',$role->id) }}"><i class="fa fa-edit"></i></a>
                                @endcan
                                <!-- @can('role-delete')
                                    {!! Form::open(['method' => 'DELETE','route' => ['roles.destroy', $role->id],'style'=>'display:inline']) !!}
                                       <button type="submit" class="btn btn-sm btn-danger del-confirm" ><i class="fa fa-trash"></i></button>
                                    {!! Form::close() !!}
                                @endcan -->
                            </td>
                        </tr>
                        @endforeach  
                  </tbody>
                  {!! $roles->render('vendor.pagination.custom') !!}
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