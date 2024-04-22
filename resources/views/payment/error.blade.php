<html>
<head>
    <style>
        .success-page{
            max-width:300px;
            display:block;
            margin: 0 auto;
            text-align: center;
            position: relative;
            top: 10%;
            transform: perspective(1px) translateY(50%)
        }
        .success-page img{
            max-width:100px;
            display: block;
            margin: 0 auto;
        }

        .btn-view-orders{
            display: block;
            border:1px solid #47c7c5;
            width:150px;
            margin: 0 auto;
            margin-top: 45px;
            padding: 10px;
            color:#fff;
            background-color:#47c7c5;
            text-decoration: none;
            margin-bottom: 20px;
        }
        h2{
            color:#47c7c5;
            margin-top: 25px;

        }
        a{
            text-decoration: none;
        }
    </style>
    <script type="text/javascript">
        function preventBack() {
            window.history.forward();
            setTimeout("preventBack()", 0);
            window.onunload = function () {
                null
            };
        }
    </script>
    <script>
        history.pushState(null, null, window.location.href);
        history.back();
        window.onpopstate = () => history.forward();
    </script>
</head>
<body>
<div class="success-page">
    <img  src="data:image/png;base64,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" class="center" alt="" />
    <h2>Payment not Complete !</h2>
    <p>Unable to process your request</p>
    <a href="{{ env('APP_FRONT_URL') }}" class="btn-view-orders">Continue</a>
</div>
</body>
</html>
