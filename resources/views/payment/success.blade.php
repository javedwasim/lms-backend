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
        <h2>Payment Successful !</h2>
        <p>We are delighted to inform you that we received your payments</p>
        <a href="{{ env('FRONTEND_URL') }}" class="btn-view-orders">Continue</a>
    </div>
</body>
</html>
