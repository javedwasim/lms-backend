@extends('layouts.master') 
@section('content')
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>Progresss Bar Setting 
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action="{{ url('update_progress_bar_setting') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" >
                
                @csrf  
                 <div class="row fil_ters">
                     <div class="col-md-8"> 
                        <div class="table-responsive">
                            <table class="table table-bordered table-actions" > 
                                @foreach($GetData as $dataVal)
                                    <input type="hidden" name="progress_id[]" value="{{ $dataVal->id }}" />
                                    <tr>
                                        <th>{{ $dataVal->no_of_count }}</th>
                                        <td> 
                                            <input type="color" class="form-control" name="color_data[]" value="{{ $dataVal->color }}" />
                                        </td>
                                    </tr>     
                                @endforeach
                            </table>
                        </div>   
                    </div>
                    <div class="col-md-4"></div> 
                </div> <br/> 
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                        <div class="form-group">
                           <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div> 
            </form>
      </div> 
    </div> 
 
@endsection