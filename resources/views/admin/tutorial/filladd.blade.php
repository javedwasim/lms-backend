@extends('layouts.master') 
@section('content')  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.0/slimselect.min.css" rel="stylesheet" /> 
 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" crossorigin="anonymous">
    <style>
        .inside_dv1{
            width: 66%; margin-left: 2px; float: left;
        }
        .inside_dv2{
            width: 20%; margin-left: 12px; float: left; background: #f4f4f4;
        }
        .inside_dv2_1{
            margin-left: 12px; float: left; padding: 10px;
        }
        .inside_dv3{
            width: 70%; margin: 2px; float: left; background: #cfdde1;
        }
        .bootstrap-tagsinput{
            width: 100%;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 5px;
            color: white;
        }
        .label-info, .badge-info {
            background-color: #3a87ad;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            padding: 2px 3px;
        }
        .bootstrap-tagsinput input{
            margin: 5px !important;
        }
        .tox-notification--warning{
            display:none !important;
        }
    </style> 
    <div class="card">
      <div class="card-header pb-0">
        <h5> Files
        </h5>
      </div>
      <div class="card-body"> 
            <form id="validate" action=""  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data"  > 
                @csrf
                
                <div class="row fil_ters div_type_1 div_Paragraph">
                  
                    <div class="col-md-12">
                        <div class="form-group">
                          <label>Description</label> 
                          <textarea id="paragraph_id" name="description" class="form-control validate[required]">{{@$tutorialFile->description}}</textarea>
                        </div>
                    </div>
                </div>
                <div class="row " style="padding:10px 0px 10px 0px">

                    <div class="col-md-3"> Title </div>
                    <div class="col-md-3 ">File</div>

                   
                    <div class="col-md-2">Type</div>
                    <div class="col-md-2">Is Downloadable </div>
                  <div class="col-md-1"> Order</div>
                    <div class="col-md-1"></div>
                </div>
                @if(@$tutorialFile)
                @foreach($tutorialFile->subfiles as $val)
                <div class="row old" style="padding:10px 0px 10px 0px">
                    
                    <div class="col-md-3"> 
                        <input type="text" class="form-control" name="titleold[]"  value="{{$val->title}}" required> 
                        <input type="hidden" class="form-control" name="subfileId[]"  value="{{$val->id}}" required> 
                    </div>
                    <div class="col-md-3 imagediv{{$val->id}}"  @if(@$val->type =="embed" ) style="display:none" @endif > <input type="file" class="form-control" name="imagesold[]" > 
                    @if($val->type=="image" ) <img src="{{$val->imageurl}}" width="200px"> @endif 
                    @if($val->type=="video" ) <a href="{{$val->imageurl}}"  download="" >Download </a>  @endif 
                    @if($val->type=="pdf" ) <a href="{{$val->imageurl}}"  download="" >Download </a> @endif 

                </div>

                <div class="col-md-2 imagediv{{$val->id}}text" @if(@$val->type !="embed" ) style="display:none" @endif > <input type="text" class="form-control" name="images_textold[]" value="{{$val->imageurl}}"> </div>
                    <div class="col-md-3"> <select class="form-control changeImageToEmbed" data-id="imagediv{{$val->id}}" name="typeold[]" required >
                                            <option value="image" @if($val->type=="image" ) selected @endif >Image</option>
                                            <option value="video" @if($val->type=="video" ) selected @endif >Video</option>
                                            <option value="pdf" @if($val->type=="pdf" ) selected @endif >Pdf</option>
                                            <option value="embed" @if($val->type=="embed" ) selected @endif >Embed</option>
                    </select> </div>
                    <div class="col-md-2"><select class="form-control " name="is_downloadableold[]" required >
                                          
                                            <option value="Yes" @if($val->is_downloadable=="Yes" ) selected @endif >Yes</option>
                                            <option value="No" @if($val->is_downloadable=="No" ) selected @endif >No</option>
                    </select> </div>
                    <div class="col-md-1"> 
                        <input type="number" class="form-control" name="positionold[]"  value="{{$val->position}}" required> 
                    </div>
                    <div class="col-md-1"><a href="javascript:"  class="btn btn-danger"  onclick="deletefromdb({{$val->id}})">X</a></div>
                </div>
                @endforeach
                @endif
              
                <div class="row clonedata" >
                </div>
                <div class="row orgdata" style="padding:10px 0px 10px 0px">

                    <div class="col-md-3"> <input type="text" class="form-control" name="title[]" @if(empty($tutorialFile)) required @endif> </div>
                    <div class="col-md-3 imagediv"> <input type="file" class="form-control" name="images[]" > </div>

                    <div class="col-md-3 imagedivtext" style="display:none"> <input type="text" class="form-control" name="images_text[]" > </div>
                    <div class="col-md-2"> <select class="form-control changeImageToEmbed" data-id="imagediv" name="type[]" @if(empty($tutorialFile)) required  @endif >
                                            <option value="image">Image</option>
                                            <option value="video">Video</option>
                                            <option value="pdf">Pdf</option>
                                            <option value="embed">Embed</option>
                    </select> </div>
                    <div class="col-md-2"><select class="form-control " name="is_downloadable[]"  >
                                          
                                          <option value="Yes">Yes</option>
                                          <option value="No">No</option>
                  </select> </div>
                  <div class="col-md-1"> <input type="number" class="form-control" name="position[]" placeholder="Order" @if(empty($tutorialFile)) required @endif> </div>
                    <div class="col-md-1"><a href="javascript:" class="btn btn-primary" onclick="addmore()">+</a></div>
                </div>
               

              
            

                <br/>
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                        <input type="hidden" name="id" value="{{@$tutorialFile->id}}">
                        <input type="hidden" name="countid" id="countid" value="1">
                        <button type="submit" class="form-group btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
      </div> 
    </div>
     
@endsection
@section('script')  
    <script> 
        function addmore(){
            let count=$("#countid").val()
            count++;
            $("#countid").val(count)
            let str='<div class="row" style="padding:10px 0px 10px 0px"><div class="col-md-3"> <input type="text" class="form-control" name="title[]" required> </div><div class="col-md-3 imagedivmore'+count+'"> <input type="file" class="form-control" name="images[]" > </div> <div class="col-md-3 imagedivmore'+count+'text" style="display:none"> <input type="text" class="form-control" name="images_text[]" > </div>   <div class="col-md-2"> <select class="form-control changeImageToEmbed" data-id="imagedivmore'+count+'" name="type[]" required> <option value="image">Image</option> <option value="video">Video</option> <option value="pdf">Pdf</option> <option value="embed">Embed</option> </select> </div>   <div class="col-md-2"><select class="form-control " name="is_downloadable[]"  > <option value="Yes">Yes</option> <option value="No">No</option>    </select> </div><div class="col-md-1"> <input type="number" class="form-control" placeholder="Order" name="position[]" required> </div> <div class="col-md-1" ><a href="javascript:"  class="btn btn-danger" onclick="remove(this)">X</a></div></div>';
            $(".clonedata").append(str);

            $(".changeImageToEmbed").on("change",function(){
           
           var classname=$(this).data("id");
          
           var selectValue=$(this).val();
           if(selectValue=="embed")
           {
               $("."+classname+"text").show('')
               $("."+classname).hide('')
           }
           else{
            $("."+classname).show('')
               $("."+classname+"text").hide('')
           }


       })
        }
        function remove(e)
        {
           
            e.parentElement.parentElement.remove();
  
        }
        function deletefromdb(id)
        {
            alert(id)
            $.ajax({
                type: 'POST',
                url: "{{url('deleteFileFromTutorial')}}",
                data: {id:id,"_token":"{{csrf_token()}}"},
                dataType: "text",
                success: function(resultData) {
                    window.location.reload()
                 }
            });
        }
        function changeImageToEmbed(e)
        {
           
            $(e).closest(".imagediv").html(' <input type="text" class="form-control" name="title[]" required>')
        }
        $(".changeImageToEmbed").on("change",function(){
           
            var classname=$(this).data("id");
          
            var selectValue=$(this).val();
            if(selectValue=="embed")
           {
               $("."+classname+"text").show('')
               $("."+classname).hide('')
           }
           else{
            $("."+classname).show('')
               $("."+classname+"text").hide('')
           }


        })
    </script>
  
@endsection