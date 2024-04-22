@extends('layouts.master')


@section('content')    
<style>
    #autoassign {
  position: relative;
  width: 40px;
  height: 20px;
  -webkit-appearance: none;
  appearance: none;
  background: red;
  outline: none;
  border-radius: 2rem;
  cursor: pointer;
  box-shadow: inset 0 0 5px rgb(0 0 0 / 50%);
}

#autoassign::before {
  content: "";
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #fff;
  position: absolute;
  top: 0;
  left: 0;
  transition: 0.5s;
}

#autoassign:checked::before {
  transform: translateX(100%);
  background: #fff;
}

#autoassign:checked {
  background: #00ed64;
}</style>
    <div class="card">
      <div class="pb-0 card-header">
        <h5>Assign Tutorial For {{ ucwords(str_replace("_"," ",$page_type)) }}
        @if($page_type=="package")
            <label class="switch"> Auto Assign Tutorail
                <input type="checkbox" name="autoassign" id="autoassign" value="1" {{$isChecked}}>
                <span class="slider round"></span>
            </label>
            @endif
            <a style="margin-left: 15px;" href="{{ url()->previous() }}" class="float-right btn btn-default btn-icon bg-gradient-secondary">Back</a>
            <button class="float-right btn btn-icon bg-gradient-primary" id="assign_btn">Submit</button>
           
            @if($page_type=="package")
               <a href="{{url('unassignquestionAll/')}}/{{ $record_id }}/packagetutorial" class="float-right btn btn-danger" onclick="return confirm('Are you really want to unassign all question')"> UnAssign All </a>

            @else
            <a href="{{url('unassignquestionAll/')}}/{{ $record_id }}/tutorailtest" class="float-right btn btn-danger" onclick="return confirm('Are you really want to unassign all question')"> UnAssign All </a>

            @endif

        </h5>
      </div>
      <div class="card-body"> 
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Category<code>*</code></label>
                    <select class="form-control  validate[required]" id="category" name="category" onchange="redrawTable()">
                        <option value="">Select Category</option>
                        @foreach ($getCategory as $categoryDt)
                        <option value="{{ $categoryDt->id }}">{{ $categoryDt->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div> 
         
        </div>
          <form id="assign_form" action="{{ url('question_common_assign_submit') }}"  class="form-horizontal comm_form" method="POST" role="form" enctype="multipart/form-data" > 
              <input type="hidden" name="assign_type" id="assign_type" value="tutorial" />
              <input type="hidden" name="record_id" id="record_id" value="{{ $record_id }}" />
              <input type="hidden" name="page_type" id="page_type" value="{{ $page_type }}" />
              @csrf  

              <div class="table-responsive">
                  <table id="my_data_table" class="table table-bordered table-striped table-actions" >
                      <thead>
                          <tr>
                            <th width="50">
                                <input type="checkbox" name="select_all" id="select_all">
                            </th> 
                            <th>Category</th>
                            <th>Tutorial Name</th>  
                            <th width="180">Action</th>                              
                          </tr>
                      </thead>
                      <tbody>       
                            
                      </tbody>
                  </table>
              </div>
          </form>
      </div> 
    </div>
@endsection

 @section('script')    
    <script> 
      function redrawTable()
        {
            $('#my_data_table').DataTable().ajax.reload();
        }
        $(document).on('click', '#select_all', function (){ 
            this.value = this.checked ? 1 : 0;
            if(this.checked){
                $('.assign_que_id').prop('checked', true);
            }else{
                $('.assign_que_id').prop('checked', false);

            }    
        });
        
        $(document).ready(function() {  

            var URL = '{{url('/')}}';
            
            $("#autoassign").on("change",function(){
            console.log($(this).prop('checked'));
            $.ajax({
                type: "POST",
                url: URL + "/updateautoassigntutorial",
                data: {
                    packageId:"{{$record_id}}",
                    status:$(this).prop('checked'),
                    _token:"{{csrf_token()}}"
                },
                          
                success: function(response) {
                    console.log(response);
                  
                }
            });
        })
            $("#assign_btn").click(function(){
                $("#assign_form").submit();
            });
  
            if($('#my_data_table').length > 0){
                $('#my_data_table').DataTable({
                    aLengthMenu: [
                        [5,10,50,100,200,500,-1],
                        [5,10,50,100,200,500,"All"]
                    ],
                    iDisplayLength: -1,
                    processing:true,
                    serverSide:true,
                    "order": [[3,'desc']],
                    "pageLength": 5, 
                    ajax: {
                        "url": URL+"/call_tutorial_list_data",
                        "type": "GET",
                        "data": function(d) {
                            d.record_id = $("#record_id").val();
                            d.page_type = $("#page_type").val();
                            d.category = $("#category").val();
                        }
                    },
                    columns:[
                        {data:"assign_checkbx",name:"assign_checkbx",orderable:false}, 
                        {data:"category_name",name:"category_name"}, 
                        {data:"chapter_name",name:"chapter_name"}, 
                        {data:"action",name:"DT_RowIndex",orderable:false},
                    ]
                });
            } 
        });
     </script>  
@endsection