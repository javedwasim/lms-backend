@extends('layouts.master')
@section('content')
<style>
    .custombtn {
        padding-top: 31px;
    }
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
<div class="card">
    <div class="pb-0 card-header">
        <h5>Update Score
            <!-- <a class="float-right btn btn-icon bg-gradient-secondary" href="{{ route('tutorial.index') }}"> Back</a> -->
        </h5>
    </div>
    <div class="card-body">
        <form id="validate" action="/ucatscore/{{$ucat->id}}" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row fil_ters">
            <div class="col-md-12">
                    <div class="form-group">
                    <label>Couser Type<code>*</code></label>
                    <select class="form-control" id="course_type_id" name="course_type_id">
                        <option value="">Select Couser Type</option>
                        @foreach ($CourseType as $categoryDt)
                        <option value="{{ $categoryDt->id }}"  @if($ucat->course_type_id==$categoryDt->id) selected @endif>{{ $categoryDt->name }}</option>
                        @endforeach
                    </select>

                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label> Catagory<code>*</code></label>
                        <select name="category_id" class="form-control category_id" required>
                            <option value="">Select Category</option>
                            @foreach($category as $val)
                            <option value="{{$val->id}}" @if($ucat->category_id==$val->id) selected @endif>{{$val->category_name}}</option>
                            @endforeach
                        </select>

                    </div>
                </div>
                <div class="col-md-12 band" style="display: @if($ucat->band_id >0) block @else none @endif" >
                    <div class="form-group">
                        <label> Band<code>*</code></label>
                        <select name="band_id" class="form-control" >
                            <option value="">Select Band</option>
                            @foreach($band as $val)
                            <option value="{{$val->id}}" @if($ucat->band_id==$val->id) selected @endif>{{$val->name}}</option>
                            @endforeach
                        </select>

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Min Score (%)<code>*</code></label>
                        <input type="text" class="form-control " required id="min_score" placeholder="Min Score (%)" name="min_score" autocomplete="off" value="{{$ucat->min_score}}">

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Max Score (%)<code>*</code></label>
                        <input type="text" class="form-control " required id="max_score" placeholder="Max Score (%)" name="max_score" autocomplete="off" value="{{$ucat->max_score}}">

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label> Score <code>*</code></label>
                        <input type="text" class="form-control " required id="score" placeholder="UCAT Score " name="score" autocomplete="off" value="{{$ucat->score}}">

                    </div>
                </div>



                <br />
                <div class="row">
                    <div class="text-right col-xs-12 col-sm-12 col-md-12">

                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script>
  /*   function addmoretutor() {
        str = '<div class="row">  <div class="col-md-3"> <div class="form-group"><label> Catagory<code>*</code></label><select name="category_id[]" class="form-control">    <option value="">Select Category</option>    @foreach($category as $val)   <option value="{{$val->id}}">{{$val->category_name}}</option>   @endforeach</select> </div>   </div>   <div class="col-md-3"> <div class="form-group"><label> Time<code>*</code></label><input type="text" class="form-control "  id="end_time" placeholder="00:00:00" value="00:00:00" name="time[]" autocomplete="off"> </div>   </div>   <div class="col-md-4"> <div class="form-group"><label> Instruction<code>*</code></label><input type="text" class="form-control "  id="end_time" placeholder="Instruction" name="instrucation[]" autocomplete="off"> </div>   </div>  <div class="col-md-2"> <div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div>  </div></div>';
        $(".tutor").append(str);

    }

    function addmoreAddon() {
        str = '<div class="row"> <div class="col-md-6"> <div class="form-group"><label> FlashCard Addon<code>*</code></label><input type="text" class="form-control "  id="addon" placeholder="FlashCard Addon" name="addon[]" autocomplete="off"></div> </div> <div class="col-md-6"><div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
        $(".addon").append(str);

    }

    function remove(e) {
        e.parentElement.parentElement.parentElement.remove();
    } */
    $(".category_id").on("change",function(){
       let categoryId=$(this).val();
        $(".band").hide();
        console.log(categoryId)
        if( categoryId==6)
        {
            $(".band").show();
        }
    })
</script>
@endsection