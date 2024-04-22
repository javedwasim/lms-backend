@extends('layouts.master')
 
@section('content')    

    <div class="card">
     
      <div class="card-body">   
              <div class="table-responsive">
                  <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                      <thead>
                          <tr>
                            <th width="50">No</th> 
                            <th>Datetime</th>
                            <th>User</th>
                            <th>Comment</th> 
                            <th>Reply</th> 
                            <th>Name Status</th>
                            <th width="180">Action</th>                            
                          </tr>
                      </thead>
                      <tbody>       
                            <?php 
                            if(!empty($comments))
                            {
                                $sr = 1;
                                foreach($comments as $key =>$val)
                                {
                                    ?>
                                    <tr>
                                        <td>{{ $sr++ }}</td>
                                        <td>{{ date('d-M-Y h:i a',strtotime($val->created_at)) }}</td>
                                        <td>{{ $val->user_name }} </td>
                                        <td>{{ $val->comment }} </td>
                                        <td>{{ $val->admin_reply }} </td>
                                        <td>{{ !($val->is_name_display)?'Show':'Hide' }}</td>
                                        <td>
                                            <a href="javascript:;" data-reply-id="{{ route('question-comments-reply',[$val->id]) }}" data-reply-to="" class="btn btn-primary btn-sm">Reply</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                      </tbody>
                  </table>
              </div> 
      </div> 
    </div>
@endsection

 @section('script')    
 <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script>
        $(document).on('click','[data-reply-id]',function(){

            var url = $(this).attr('data-reply-id');

            Swal.fire({
                title: 'Reply Comment',
                input: 'textarea'
            }).then(function(result) {
            if (result.value) {
                if(result.value)
                {
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {message:result.value,_token:'{{ csrf_token() }}'},
                        dataType: "json",
                        success: function (response) {
                            if(response.status == true)
                            {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text:response.message,
                                    })
                                setTimeout(() => {
                                    window.location.reload();
                                    
                                }, 2000);
                                
                                
                            }else{
                                Swal.fire({
  icon: 'error',
  title: 'Oops...',
  text:response.message,
})
                            }
                        },
                        error:function(err){
                            console.log('err',err)
                        }
                    });
                }
                return ;
            }
            })

        })
   </script>
@endsection