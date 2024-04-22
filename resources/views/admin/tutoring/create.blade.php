@extends('layouts.master') 
@section('content')  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
    <div class="card">
      <div class="card-header pb-0">
        <h5>Add Tutoring URL   
            <!-- <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('tutorial.index') }}"> Back</a> -->
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action=""  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                <div class="row fil_ters"> 
                    
                     <div class="col-md-6">
                        <div class="form-group">
                          <label>Tutoring Url<code>*</code></label>
                          <input type="text" class="form-control validate[required]" value="{{ $checkUrl->url }}" id="tutorial_name" placeholder="Tutoring Url" name="tutoring_url" autocomplete="off" >
                        </div> 
                     </div>
                     
                </div> <br/> 
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
      </div> 
    </div>
     
@endsection

@section('script')  
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.js"></script>
    <script>
        var highlits_select = new SlimSelect({
            select: '.multiple-selected',
            //showSearch: false,
            placeholder: 'Select',
            deselectLabel: '<span>&times;</span>',
            hideSelectedOption: true,
        })
    </script>
@endsection