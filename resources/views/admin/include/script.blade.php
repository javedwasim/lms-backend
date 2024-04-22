
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="{{asset('new_theme/assets/js/core/popper.min.js')}}"></script>
<script src="{{asset('new_theme/assets/js/core/bootstrap.min.js')}}"></script>
<script src="{{asset('new_theme/assets/js/plugins/perfect-scrollbar.min')}}.js"></script>
<script src="{{asset('new_theme/assets/js/plugins/smooth-scrollbar.min')}}.js"></script>
<script src="{{asset('new_theme/assets/js/plugins/dragula/dragula.min')}}.js"></script>
<script src="{{asset('new_theme/assets/js/plugins/jkanban/jkanban.js')}}"></script>
<script src="{{asset('new_theme/assets/js/plugins/chartjs.min.js')}}"></script>
<script src="{{asset('new_theme/assets/js/plugins/threejs.js')}}"></script>
<script src="{{asset('new_theme/assets/js/plugins/orbit-controls.js')}}"></script>


<script src="{{asset('new_theme/assets/js/plugins/datatables.js')}}"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.ckeditor.com/4.17.1/full/ckeditor.js"></script>
<script> 

   $(document).ready(function() {
      $('.js-example-basic-single').select2();
   });
   if (document.querySelector('.datepicker')) {
      flatpickr('.datepicker', {
         mode: "range"
      });
   }

   let digitValidate = function(ele) {
      // console.log(ele.value);
      ele.value = ele.value.replace(/[^0-9]/g, '');
   }

   $('#password, #password_confirmation').on('keyup', function() {
      if ($('#password').val() == $('#password_confirmation').val()) {
         $('#passwordMatchmessage').html('');
      } else
         $('#passwordMatchmessage').html('Confirm password does not match with password.').css('color', 'red');
   });
</script>

<script>
   var win = navigator.platform.indexOf('Win') > -1;
   if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
         damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
   }
   function unassignquestion(questionId,primaryId,type)
   {
      $.ajax({
                type: "POST",
                url: URL + "/unassignquestion",
                data: {
                  questionId:questionId,
                  primaryId:primaryId,
                  type:type,
                    _token:"{{csrf_token()}}"
                },
                          
                success: function(response) {
                  $('#my_data_table').DataTable().ajax.reload();
                }
            });
      console.log(questionId,primaryId,type);
   }
</script>
<script async defer src="https://buttons.github.io/buttons.js"></script>
<script src="{{asset('new_theme/assets/js/soft-ui-dashboard.min.js?v=1.0.5')}}"></script>