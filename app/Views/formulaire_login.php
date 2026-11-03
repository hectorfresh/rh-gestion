<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">

    <style>
        .page-subtitle {
            color: #888;
            margin: -10px 0 30px;
            font-size: 14px;
        }
        .login-error {
            background: #fdf0d5;
            color: #b8791a;
            border: 1px solid #f0ad4e;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .login-error i {
            margin-right: 6px;
        }
    </style>
</head>
<body class="templatemo-bg-gray">
    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Connexion</h1>
            <p class="text-center page-subtitle">
                Accédez à votre espace de gestion des besoins et annonces.
            </p>

            <form class="form-horizontal templatemo-container templatemo-login-form-1 margin-bottom-30"
                  role="form"
                  action="/login"
                  method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <div class="col-xs-12">
                        <div class="control-wrapper">
                            <label for="email" class="control-label fa-label">
                                <i class="fa fa-user fa-medium"></i>
                            </label>
                            <input type="email" name="email" class="form-control" id="email" placeholder="Email">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-12">
                        <div class="control-wrapper">
                            <label for="password" class="control-label fa-label">
                                <i class="fa fa-lock fa-medium"></i>
                            </label>
                            <input type="password" name="mdp" class="form-control" id="password" placeholder="Password">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0; margin-top: 30px;">
                    <div class="col-md-12">
                        <input type="submit" value="Connexion" class="btn btn-info btn-block" style="margin-bottom: 15px;">
                    </div>
                </div>

            </form>
        </div>
    </div>
</body>
</html>