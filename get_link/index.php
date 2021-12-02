<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Get Link </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>

</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-8 offset-md-2">
                <div name="form-get-link" id="form-get-link" style="padding-top: 100px;">
                    <div class="form-group row">
                        <div class="col-10 input-group">
                            <label for="fs-link" class="input-group-addon">LINK CẦN GET: </label>
                            <input class="form-control" type="text" value="" id="link">
                        </div>
                        <div class="col-2">
                            <button id="btn-getlink" type="button" class="btn btn-secondary"
                                    data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Đang tải...">GET LINK</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-8 offset-md-2">
                <div class="col-12 input-group">
                    <label for="fs-link" class="input-group-addon"><i class="fa fa-external-link" aria-hidden="true"></i></label>
                    <input style="background-color:#F8F8F8;" onclick="$(this).select();" class="form-control text-center" id="derect-link" type="text" readonly="">
                </div>
            </div>
        </div>
        <br/>
        <div class="alert text-center" role="alert">

        </div>
    </div>
</body>
</html>