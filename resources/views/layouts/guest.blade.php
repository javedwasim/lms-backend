<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
        <link href="{{ asset('new_theme/assets/css/nucleo-icons.css') }}" rel="stylesheet" />
        <link href="{{ asset('new_theme/assets/css/nucleo-svg.css') }}" rel="stylesheet" />
        <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
        <link href="{{ asset('new_theme/assets/css/nucleo-svg.css') }}" rel="stylesheet" />
        <link id="pagestyle" href="{{ asset('new_theme/assets/css/soft-ui-dashboard.css?v=1.0.6') }}" rel="stylesheet" />
      
        <style>
            .main_dv_img {
                background-size: cover;
                height: 100vh;
                width: 100%;
                background-image: url('new_theme/assets/img/curved-images/curved9.jpg');
            }

            .error {
                color: red
            }
        </style>
    </head>
    <body class="bg-gray-100">
        <div class="font-sans antialiased text-gray-900">
            {{ $slot }}
        </div>
        

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js" integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
        <script src="{{ asset('new_theme/assets/js/core/popper.min.js') }}"></script>
        <script src="{{ asset('new_theme/assets/js/core/bootstrap.min.js') }}"></script>
        <script src="{{ asset('new_theme/assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
        <script src="{{ asset('new_theme/assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
        <script src="{{ asset('new_theme/assets/js/plugins/dragula/dragula.min.js') }}"></script>
        <script src="{{ asset('new_theme/assets/js/plugins/jkanban/jkanban.js') }}"></script>
        <script>
            var win = navigator.platform.indexOf('Win') > -1;
            if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
            }

            $("#commentForm").validate({
            rules: {
                email: {
                "required": true,
                "email": true,
                },
                password: "required",

            },
            messages: {

            }
            });
        </script>
        <!-- Github buttons -->
        <script async defer src="https://buttons.github.io/buttons.js"></script>
        <script src="{{ asset('new_theme/assets/js/soft-ui-dashboard.min.js?v=1.0.6') }}"></script>

    </body>
</html>
