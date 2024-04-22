@extends('layouts.master') 
@section('content')
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>View Paragraph
            <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('paragraph.index') }}"> Back</a>
        </h5>
      </div>
      <div class="card-body"> 
            <div class="row fil_ters"> 
                <div class="col-md-12"> 
                    <div class="table-responsive">
                        {{ $get_data->paragraph }}
                    </div>   
                </div>           
            </div>
      </div> 
    </div>
   
@endsection 
 