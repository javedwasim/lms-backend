@extends('layouts.master')


@section('content')
<style>
    table tr td:nth-child(4) {
        white-space: break-spaces;
        width: 100%;
    }
</style>
<div class="card">
    <div class="card-header pb-0">
        <h5>Tutorial
           
        </h5>
    </div>
    <div class="card-body">
        <form>
       {{--  <div class="row">
         
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        @foreach ($courses as $categoryDt)
                        <option value="{{ $categoryDt->id }}" @if($courseId==$categoryDt->id) selected @endif>{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>  
            <div class="col-md-3">
                <div class="form-group">
                    <label>Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="category_id" name="category_id" required >
                        <option value="">Select Category</option>
                        @foreach ($category as $categoryDt)
                        <option value="{{ $categoryDt->id }}" @if($category_id==$categoryDt->id) selected @endif >{{ $categoryDt->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                    <input type="submit" value="Search" class="btn btn-primary" style="margin-top:31px">
            </div>
        </div> --}}
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Course<code>*</code></label>
                    <select class="form-control  validate[required]" id="course_id" name="course_id" >
                        <option value="">Select Course</option>
                        @foreach ($courses as $categoryDt)
                        <option value="{{ $categoryDt->id }}" @if($courseId==$categoryDt->id) selected @endif >{{ $categoryDt->course_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="category" name="category_id" >
                        <option value="">Select Category</option>
                        @foreach ($category as $categoryDt)
                        <option value="{{ $categoryDt->id }}" @if($category_id==$categoryDt->id) selected @endif>{{ $categoryDt->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div> 
            <div class="col-md-3">
                <input type="submit" value="Search" class="btn btn-primary" style="margin-top:31px">
            </div>
           
        </div>
        </form>
        <form method="post" action="{{url('/storeTutorialOrder')}}">
        {{ csrf_field() }}
        <div class="table-responsive">
            <div class="btndesign" style="text-align:right">  
                <input type="hidden" value="{{$courseId}}" name="course_id">
                <input type="hidden" value="{{$category_id}}" name="category_id">
                <input type="submit" value="Submit" class="btn btn-primary" > </div>
           
            <table style="width:100%" class="table table-bordered table-striped table-actions">
                <thead>
                    <tr>
                        <th >Tutorial Name</th>
                        <th>Category</th>
                        <th>Order Of Tutorial</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($tutorialList) && count($tutorialList) > 0)
                        @foreach($tutorialList as $val)
                        <tr>
                            <td >{{$val->chapter_name}}</td>
                            <td>{{$val->category_detail->category_name}}</td>
                            <td><input type="number" name="tutorialOrders[{{$val->id}}]" class="form-control" value="{{@$ordersList[$val->id] ? $ordersList[$val->id] : 1 }}"> </td>
                          
                        </tr>
                        @endforeach
                    @elseif($isSearch==0)
                    <tr>
                        <td colspan="3" style="text-align: center"> Please select course and category to set tutorial order </td>
                      
                    </tr>
                    @else
                    <tr>
                        <td colspan="3" style="text-align: center">No Record Found </td>
                      
                    </tr>
                    @endif



                </tbody>
            </table>
        </div>
        
        </form>
    </div>
</div>
@endsection
@section('script')
<script>
     
    $(document).ready(function() {

        $("#course_id").on("change",function(){
            let courseId=$(this).val();
            $.ajax({
                type: "POST",
                dataType: "text",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                url: '{{url("/getcoursecategory")}}',
                data: {'course_id': courseId},
                success: function(res){
                   $("#category").html(res);
                   
                }
            });
        })
        })
        </script>
        @endsection