<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <link rel="apple-touch-icon" sizes="76x76" href="{{asset('img/favicon.ico')}}">
   <link rel="icon" type="image/png" href="{{ asset('img/favicon.ico') }}">
   <title>
      {{ config('app.name') }}
   </title>
   <meta name="csrf-token" content="{{ csrf_token() }}" />

   <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
   <link href="{{asset('new_theme/assets/css/nucleo-icons.css')}}" rel="stylesheet" />
   <link href="{{asset('new_theme/assets/css/nucleo-svg.css')}}" rel="stylesheet" />
   <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
   <link href="{{asset('new_theme/assets/css/nucleo-svg.css')}}" rel="stylesheet" />
   <link id="pagestyle" href="{{asset('new_theme/assets/css/soft-ui-dashboard.css?v=1.0.5')}}" rel="stylesheet" />
   <link href="{{asset('new_theme/assets/css/custom.css')}}" rel="stylesheet" />
   <link id="pagestyle" href="{{asset('new_theme/assets/css/soft-ui-dashboard.css?v=1.0.5')}}" rel="stylesheet" />

   <link href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" rel="stylesheet">
   <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet">
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

   <style>
      .float-right {
         float: right;
      }

      .text-green {
         color: green;
      }

      .text-red {
         color: red;
      }

      .text-white {
         color: red;
      }

      .objectfit-cover {
         object-fit: cover;
      }

      .pagination {
         float: right;
      }

      .display-none {
         display: none;
      }

      .width-max {
         width: max-content;
         ;
      }

      table.dataTable.no-footer {
         border-bottom: 1px solid #e9ecef !important;
      }

      .navbar-vertical .navbar-nav>.nav-item .nav-link.active .icon {
         background-image: linear-gradient(310deg, #f8f9fa 0%, #30144c 100%);
         color: white;
      }

      .bg-gradient-primary {
         background-image: linear-gradient(310deg, #f8f9fa 0%, #834567 100%);
      }

      .btn-primary {
         background-color: #ff5c39;
      }

      .sidenav {
         background: linear-gradient(310deg, #58496b 0%, #5d9fb1 100%);
      }

      .btn-primary:hover,
      .btn.bg-gradient-primary:hover {
         background-color: #ff5c39;
      }

      .page-item.active .page-link {
         background-color: #ff5c39;
         border-color: #ff5c39;
      }

      div.dataTables_wrapper div.dataTables_info {
         font-size: .75rem;
      }

      .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
         color: white !important;
         border: 0px solid #ffff !important;
         box-shadow: none;
         background: white !important;
      }

      .dataTables_wrapper .dataTables_paginate .paginate_button:active {
         background: white !important;
         box-shadow: none;
      }

      .page-item:not(:first-child) .page-link {
         margin-left: -24px;
      }

      .badge-dark:hover,
      .badge-dark:focus {
         color: white !important;
      }

      .fiterDiv {
         border: 0.5px solid lightgray;
         padding: 10px;
         border-radius: 10px;
         margin-bottom: 15px;
      }

      .footer-fix {
         position: absolute;
         bottom: 0;
         width: 100%;
         left: 0;
      }

      .close {
         background: none;
         border: none;
         float: right;
      }

      .card-header {
         border-bottom: 1px solid #e9ced5;
      }
   </style>

   @yield('style')
</head>

<body class="g-sidenav-show  bg-gray-100" style="background: linear-gradient(310deg, #ffffff 0%, #5d9fb1 100%);">
   @include('admin.include.side_nav')
   <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

      @include('admin.include.top_nav')

      <div class="container-fluid py-2">
         @if ($message = Session::get('success'))
         <div class="alert alert-success  btn-sm w-100 btn-outline-success ">
            <strong>{{ $message }}</strong>
         </div>
         @endif
         @if ($message = Session::get('error'))
         <div class="alert alert-danger  btn-sm w-100 btn-outline-danger ">
            <strong>{{ $message }}</strong>
         </div>
         @endif
         @if ($message = Session::get('warning'))
         <div class="alert alert-warning btn-sm w-100 btn-outline-warning ">
            <strong>{{ $message }}</strong>
         </div>
         @endif
         @if ($errors->any())
         <div class="alert  btn-sm w-100 btn-outline-danger ">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
               @foreach ($errors->all() as $error)
               <li>{{ $error }}</li>
               @endforeach
            </ul>
         </div>
         @endif

         @yield('content')
      </div>
   </main>

   @include('admin.include.script')
   @yield('script')
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
   <script>
      $(document).on("click", '[data-logout-event]', function(e) { // worked with dynamic loaded jquery content

         data_id = $(this).data('id');
         e.preventDefault();
         swal({
               title: "Are you sure you want to logout?",
               text: "",
               icon: "warning",
               buttons: true,
               dangerMode: true,
            })
            .then((willDelete) => {
               if (willDelete) {
                  window.location.href = "{{ route('logout') }}";
               } else {

               }
            });

      });
      var URL = '{{url('/')}}';

      // print url
      console.log(URL);

      $(document).ready(function() {

         $(".decimal_number").keypress(function(e) { // input number only with decimal value 
            return (e.charCode != 8 && e.charCode == 0 || (e.charCode == 46 || (e.charCode >= 48 && e.charCode <= 57)))
         });

         setTimeout(function() {
            $('.alert').remove();
         }, 5000);

         $(document).on("click", '.ch_input', function(e) { // worked with dynamic loaded jquery content

            this.value = this.checked ? 1 : 0;

            if (this.checked) {
               $(this).attr('checked', 'checked');
            } else {
               $(this).removeAttr('checked');
            }

         });


         $(document).on("click", '.common_status_update', function(e) {

            data_id = $(this).data('id');

            var updated_status = $(this).val();

            data_action = $(this).data('action');

            $.ajaxSetup({
               headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               }
            });

            var form_data = new FormData();
            form_data.append("record_id", data_id);
            form_data.append("status", updated_status);

            form_action = data_action + '_status_update';

            $.ajax({
               type: "POST",
               url: URL + "/" + form_action,
               data: form_data,
               enctype: 'multipart/form-data',
               processData: false, // Important!
               contentType: false,
               cache: false,
               dataType: "json",

               success: function(response) {

                  $('#message_box').html('');

                  if (response.status == '10') {
                     htm = '';
                     var array = $.map(response.missingParam, function(value, index) {
                        htm += '<span class="invalid-feedback" role="alert"><strong>' + value + "</strong></span>";
                     });
                     var htt_box = '<div class="alert alert-danger" >' +
                        '<button class="close" data-close="alert"></button>' +
                        '<span>' + response.message + '</span>' +
                        '</div>';
                     $('#message_box').html(htt_box);

                  } else if (response.status == '1') {
                     swal(response.message);
                  }

               }
            });

         });

         $(document).on("click", '.del-confirm', function(e) { // worked with dynamic loaded jquery content

            data_id = $(this).data('id');
            e.preventDefault();
            swal({
                  title: "Are you sure?",
                  text: "Once deleted, you will not be able to recover again!",
                  icon: "warning",
                  buttons: true,
                  dangerMode: true,
               })
               .then((willDelete) => {
                  if (willDelete) {
                     $('#form_del_' + data_id).submit();
                  } else {
                     swal("Your record file is safe!");
                  }
               });

         });

         $(document).on("click", '.close', function(e) {
            $('#message_box').html('');
         });
      });
   </script>

</body>

</html>