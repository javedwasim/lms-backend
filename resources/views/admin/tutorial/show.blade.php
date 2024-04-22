@extends('layouts.master') 
@section('content')
    
    <div class="card">
      <div class="card-header pb-0">
        <h5>View {{ $page_title }}   
            @if($page_title=='user')
                <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('event.index') }}"> Back</a>
            @endif
        </h5>
      </div>
      <div class="card-body"> 
            <div class="row fil_ters"> 
                <div class="col-md-12"> 
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-actions" >
                            <tr>
                                <th>Course</th>
                                <td>
                                    <p style="white-space: pre-wrap;">{{ $my_course_list }}</p></td>
                            </tr>
                            <tr>
                                <th>Category Name</th>
                                <td>{{ ucwords($getData->category_detail->category_name) }}</td>
                            </tr>
                            <tr>
                                <th>Tutorial Name</th>
                                <td>{{ $getData->chapter_name }}</td>
                            </tr>  
                            <tr>
                                <th>Video Length</th>
                                <td>{{ $getData->total_video_time }}</td>
                            </tr> 
                            <tr>
                                <th>Video</th>
                                <td>
                                    @if($getData->video_url) 
                                        <video  style="width:225px;" controls >
                                           <source src="{{ $getData->video_url }}" > 
                                        </video>
                                    @endif
                                </td>
                            </tr>  
                            <tr>
                                <th>Status</th>
                                <td>{!!  
                                 ($getData->status=='1') ? '<span class="label label-success sdd">Active</span>' : '<span class="label label-warning  sdd">InActive</span>'
                                 !!}</td>
                            </tr>  
                        </table>
                    </div>   
                </div>           
            </div>
      </div> 
    </div>
   
@endsection 
 