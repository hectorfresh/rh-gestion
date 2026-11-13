<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Accès refusé</title>
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="templatemo-bg-gray">
    <div class="container" style="max-width:620px;margin-top:80px">
        <div class="app-card text-center">
            <h1 style="font-size:72px;margin:0;color:#f39c12">403</h1>
            <h3><i class="fa fa-lock text-danger"></i> Accès refusé</h3>
            <p class="text-muted">
                Votre rôle ne possède pas la permission nécessaire pour consulter cette page.
            </p>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-warning">
                <i class="fa fa-home"></i> Retour au tableau de bord
            </a>
            <a href="<?= base_url('annonces') ?>" class="btn btn-default">
                <i class="fa fa-bullhorn"></i> Voir les annonces
            </a>
        </div>
    </div>
</body>
</html>
