@extends('layouts.master')
<style>
    label {
    font-size: 13px;
    padding: 3px;
}
.support_txt{
    width: 100% !important;
    border: 1px solid #b3b3b3;
}
#admin_reply_div{
    display: none;
}
</style>

@section('content')
   <ul class="breadcrumb">
        <li><a href="#">Home</a></li> 
        <li><a href="#">Help & Support</a></li> 
    </ul> 
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> Help & Support Details</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('contact_us.index') }}"> Back</a>
            </div>
        </div>
    </div> 
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="panel panel-default">  
                <div class="page-head">        
                    <div class="page-head-text">
                        <h3 class="panel-title"><strong>Help & Support Details</strong></h3> 
                    </div>
                    <div class="page-head-controls">  
                    </div>                    
                </div>  
                <div class="panel-default">
                    <div class="panel-body">

                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label>Name:</label>
                                        {{ $get_data->user_name }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label>Subject:</label>
                                        {{ $get_data->enquiry }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label>Description:</label>
                                        {{ $get_data->description }}
                                    </div>
                                </div> 
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label>Create Date:</label>
                                        {{ date("Y-m-d", strtotime($get_data->created_at)) }}
                                    </div>
                                </div>

                                <!-- <div class="col-xs-4 col-sm-3 col-md-3">
                                    <div class="form-group"> 
                                        <button class="btn btn-info btn-md" id="supp_reply_btn" name="support_submit" data-toggle="modal" data-target="#support_modal"  data-id="{{$get_data->id}}" >Add Reply</button>
                                    </div>
                                </div> -->
                                <div class="col-xs-4 col-sm-4 col-md-6" id="admin_reply_div" style="background: #ebe6d9eb;" >
                                    <h5 class="modal-title" style="text-align:center;">Admin Reply</h5>
                                    <div>
                                        
                                        <form id="support_repl_form" method="post"   > 
                                            @csrf
                                            <input type="hidden" name="support_id" id="my_support_id" value="{{$get_data->id}}" >
                                            <div class="modal-body" style="padding: 4px 0px 9px 0px !important;">
                                                <div class="alert alert-danger errorcart" style="display: none;" ></div>
                                                <div class="alert alert-success successcart" style="display: none;" ></div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">                
                                                            <textarea class="support_txt" style="min-height: 85px !important;" name="support_reply">{{$get_data->support_reply}}</textarea>          
                                                        </div>
                                                    </div> 
                                                </div>
                                            </div>
                                            <div class="justify-content-between" style="text-align: right;" >
                                              <button id="rep_close" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                              <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>                            

                    </div>
                </div>
            </div>
        </div> 
    </div>
    

    <!-- <div class="modal fade" id="support_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog details_strt">
            <div class="modal-content">  
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Reply</h5> 
                </div>
                <form id="support_repl_form" method="post"   > 
                    @csrf
                    <input type="hidden" name="support_id" id="my_support_id" value="" >
                    <div class="modal-body">
                        <div class="alert alert-danger errorcart" style="display: none;" ></div>
                        <div class="alert alert-success successcart" style="display: none;" ></div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">                
                                    <textarea class="support_txt" rowspan="6" name="support_reply">{{$get_data->support_reply}}</textarea>          
                                </div>
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div> -->

@endsection 
 @section('script')    
 <script>
    $(document).ready(function() { 
        
        Ad_rp = '{{$get_data->support_reply}}';
        if(Ad_rp!=""){
            setTimeout(function() { 
                $('#admin_reply_div').toggle();
            }, 700);
        }   
        
        $("#supp_reply_btn").on("click", function(e) {
            data_id = $(this).data('id'); 
            $('#my_support_id').val(data_id);   

            $('#admin_reply_div').toggle();

        });

        $("#rep_close").on("click", function(e) { 
            $('#admin_reply_div').toggle(); 
        });

        $("#support_repl_form").on("submit", function(e) {
           
                  $(".successcart").html('');
                  $(".errorcart").html('');
                  $(".successcart").css("display", "none");
                  $(".errorcart").css("display", "none");
                  
                  e.preventDefault();

                    red_data = $('#support_repl_form').serialize();
                    //alert("red_data");
                    $.ajax({
                      url: URL+"/support_reply_submit",  // "order/assign_to_lab", 
                      type:"post",
                      data: red_data,
                      success: function(data) {
                            $(".successcart").css("display", "block");
                            $(".successcart").html("Reply submitted");  

                          setTimeout(function() { 
                            $(".alert").hide(); 
                            $('#admin_reply_div').toggle();
                          }, 2000);
                      }
                    
                    })

        });

    });
 </script>
@endsection