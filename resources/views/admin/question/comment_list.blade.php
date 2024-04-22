@extends('layouts.master')
 
@section('content')    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" crossorigin="anonymous">
    <style>
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
        .bootstrap-tagsinput input {
    margin: 1px !important;
    margin-top: 7px !important;
}
    </style>
    <div class="card">
      <div class="card-header pb-0">
        <h5>Comment List  
            <div style="float:right"> 
               
            </div>
          
        </h5>
      </div>
      <div class="card-body">   
              <div class="table-responsive">
                  <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                      <thead>
                          <tr>
                            <th width="5%">Username</th> 
                            <th  width="5%">Comment</th>
                            <th  width="5%">Course</th> 
                            <th  width="5%">Category</th> 
                            <th  width="10%">Date Reported</th> 
                            <th  width="10%">Admin Note</th> 
                            <th  width="10%">Lable</th> 
                            <th width="5%">Reply to Comment</th>  
                          
                            <th  width="20%">Action</th>                            
                          </tr>
                      </thead>
                      <tbody>  
                          @foreach($commentlist as $val)
                          <?php 
                       
                            $adminComment=App\Models\Comment::where("parent_id",$val->id)->first();
                          ?>
                          <tr>
                              <td>{{$val->user->name}}</td>
                              <td>{{$val->comment}}</td>
                              <td>{{@$val->question->course->course_name}}</td>
                              <td>{{@$val->question->category->category_name}}</td>
                              <td>{{$val->created_at}}</td>
                              <td>{{@$adminComment->comment}}</td>
                              <td>{{$val->status=="0"?"Pending":"Resolved"}}</td>
                              <td><a href="{{url('/replyComment')}}/{{$val->id}}" >Reply To Comment</a></td>
                              <td>
                                  <a href="{{url('/deleteComment')}}/{{$val->id}}" class="btn bg-gradient-danger btn-rounded btn-condensed btn-sm s_btn1 view_in_modal">X</a>
                                  <a href="{{url('/editComment')}}/{{$val->id}}" class="btn bg-gradient-secondary btn-rounded
                                    btn-condensed btn-sm form_data_act" ><i class="fa fa-pencil"></i></a>
                            </td>
                          </tr>
                          @endforeach
                            
                      </tbody>
                  </table>
              </div> 
      </div> 
    </div>
@endsection

 @section('script')    

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js" integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js" crossorigin="anonymous"></script>

    <script>
        $('#question_tags').tagsinput('add', 'tags data');
        $(document).on('click', '.assign_que_id', function (){ 
            this.value = this.checked ? 1 : 0;   
        });


        $(document).ready(function() {  

            var URL = '{{url('/')}}';
            
            $("#question_tags").change(function(){ 
                $('#my_data_table').DataTable().ajax.reload();  
            });

            if($('#my_data_table').length > 0){
                $('#my_data_table').DataTable();
            }  
        });
     </script>  
@endsection