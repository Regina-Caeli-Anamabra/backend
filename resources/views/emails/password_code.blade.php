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
            border: 2px solid orange;
            padding: 2rem 3rem;
            border-radius: 1rem;
        }

        .container{
            margin: 0 auto;
            width: 100%;
        }
        .header{
            font-weight: 500;
            padding: 1rem 0rem;
            text-align: center;
            font-size: 1.2rem;
        }
        .inner-header{
            font-weight: bolder;
            font-size: 1.4rem;
            text-align: center;
            margin: 1rem 0rem 1.5rem 0rem;


        }
        body{
            background-color: #e0e0fd;
        }
        .code{
            text-align: center;
            font-size: 2rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">Reset your RaffleDraws password</div>
    <div class="inner-container">
        <div class="inner-header">RaffleDraws password reset</div>
        <p>
            We heard that you lost your RaffleDraws password. Sorry about that!
        </p>
        <p>
            But don’t worry! You can use the following code to reset your password:
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
