@extends('layouts.master')
@section('content')
<style>
    .custombtn {
        padding-top: 31px;
    }
    .tox-notifications-container
    {
        display: none !important;
    }
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" />
<div class="card">
    <div class="card-header pb-0">
        <h5>Update Mocktest
            <!-- <a class="btn btn-icon bg-gradient-secondary float-right" href="{{ route('tutorial.index') }}"> Back</a> -->
        </h5>
    </div>
    <div class="card-body">
        <form id="validate" action="" class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data">
            @csrf
            <div class="row fil_ters">


                <div class="col-md-12">
                    <div class="form-group">
                        <label>Name<code>*</code></label>
                        <input type="text" class="form-control " required id="title" placeholder="name" name="name" autocomplete="off" required value="{{$mocktest->name}}">

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Image<code>*</code></label>
                        <input type="file" class="form-control "  id="subtitle" placeholder="Instruction" name="image" autocomplete="off">
                        <img src="{{$mocktest->image}}" width="200px" style="margin-top:10px">

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label> Course<code>*</code></label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            @foreach($courses as $val)
                            <option value="{{$val->id}}" @if($mocktest->course_id==$val->id) selected @endif >{{$val->course_name}}</option>
                            @endforeach
                        </select>

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Order<code>*</code></label>
                        <input type="number" class="form-control " required id="sort" placeholder="Instruction" name="sort" autocomplete="off" required value="{{$mocktest->sort}}">

                    </div>
                </div>
                <div class="row fil_ters ">
                    @foreach($mocktestcategory as $category)
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label> Catagory<code>*</code></label>
                                <select name="category_id[]" class="form-control">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $val)
                                    <option value="{{$val->id}}" @if($category->category_id==$val->id) selected @endif >{{$val->category_name}}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label> Time<code>*</code></label>
                                <input type="text" class="form-control " id="end_time" placeholder="00:00:00" name="time[]" autocomplete="off"  value="{{$category->time}}">

                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label> Instruction<code>*</code></label>
                                <input type="text" class="form-control instruction" id="end_time" placeholder="Instruction" name="instrucation[]" autocomplete="off" value="{{$category->instruction}}">

                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group custombtn">
                                <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a>

                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row fil_ters ">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label> Catagory<code>*</code></label>
                            <select name="category_id[]" class="form-control">
                                <option value="">Select Category</option>
                                @foreach($categories as $val)
                                <option value="{{$val->id}}">{{$val->category_name}}</option>
                                @endforeach
                            </select>

                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label> Time<code>*</code></label>
                            <input type="text" class="form-control " id="end_time" placeholder="00:00:00" name="time[]" autocomplete="off" value="00:00:00">

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Instruction<code>*</code></label>
                            <input type="text" class="form-control  instruction" id="end_time" placeholder="Instruction" name="instrucation[]" autocomplete="off">

                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group custombtn">
                            <a href="javascript:" class="btn btn-primary" onclick="addmoretutor()">Add More</a>

                        </div>
                    </div>
                    <div class="tutor"></div>
                </div>


                <br />
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
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    function addmoretutor() {
        str = '<div class="row">  <div class="col-md-3"> <div class="form-group"><label> Catagory<code>*</code></label><select name="category_id[]" class="form-control">    <option value="">Select Category</option>    @foreach($categories as $val)   <option value="{{$val->id}}">{{$val->category_name}}</option>   @endforeach</select> </div>   </div>   <div class="col-md-3"> <div class="form-group"><label> Time<code>*</code></label><input type="text" class="form-control "  id="end_time" placeholder="00:00:00" value="00:00:00" name="time[]" autocomplete="off"> </div>   </div>   <div class="col-md-4"> <div class="form-group"><label> Instruction<code>*</code></label><input type="text" class="form-control instruction"  id="end_time" placeholder="Instruction" name="instrucation[]" autocomplete="off"> </div>   </div>  <div class="col-md-2"> <div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a></div>  </div></div>';
        tinymce.remove('.instruction')
        $(".tutor").append(str);
        tinymce.init({
            selector: '.instruction',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });

    }

    function addmoreAddon() {
        str = '<div class="row"> <div class="col-md-6"> <div class="form-group"><label> FlashCard Addon<code>*</code></label><input type="text" class="form-control "  id="addon" placeholder="FlashCard Addon" name="addon[]" autocomplete="off"></div> </div> <div class="col-md-6"><div class="form-group custombtn"> <a href="javascript:" class="btn btn-danger" onclick="remove(this)">Remove</a> </div></div></div>';
        $(".addon").append(str);

    }
    tinymce.init({
            selector: '.instruction',
            menubar: false,
            branding: false,
            statusbar: false,
            height: "180"
        });
    function remove(e) {
        e.parentElement.parentElement.parentElement.remove();
    }
</script>
@endsection