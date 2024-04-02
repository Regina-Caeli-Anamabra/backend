<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        .inner-container{
            padding: 2rem 3rem;
            border-radius: 1rem;
        }

        .container{
            margin: 0 auto;
            width: 100%;
        }
        .inner-header{
            font-weight: bolder;
            font-size: 1.4rem;
            margin: 1rem 0rem 1.5rem 0rem;


        }
        body{
            background-color: #e0e0fd;
        }
        .code{
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="inner-container">
        <div class="inner-header">RaffleDraws Verification Code</div>
        <p>
           Your verification Code is
        </p>
        <p class="code">
            {{ $mailData["code"] }}
        </p>
        <p>
            You can ignore if your did not initiate this request
        </p>

    </div>
</div>
</body>
</html>
