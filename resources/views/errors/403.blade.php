@php
    $frontend_url = env('FRONTEND_URL', 'http://localhost:3000');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div>You are unauthorized to access this resource</div>
    <script>
        setTimeout(() => {
            window.location.href = "{{ $frontend_url }}";
        }, 3000);
    </script>
</body>
</html>
