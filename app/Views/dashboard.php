<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">

    <?= $this->include('navbar', ['active' => 'accueil']) ?>

    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Bienvenue</h1>
            <p class="text-center page-subtitle">
                Choisissez une action pour commencer.
            </p>

            <div class="row app-nav-row">
                <div class="col-md-4 col-sm-4">
                    <a href="<?= base_url('besoins') ?>" class="app-nav-card">
                        <div class="app-nav-icon orange">
                            <i class="fa fa-plus-circle"></i>
                        </div>
                        <h3>Créer un besoin</h3>
                        <p>Enregistrer une nouvelle demande de recrutement.</p>
                    </a>
                </div>

                <div class="col-md-4 col-sm-4">
                    <a href="<?= base_url('besoins/valider') ?>" class="app-nav-card">
                        <div class="app-nav-icon green">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h3>Valider</h3>
                        <p>Consulter et valider les besoins en attente.</p>
                    </a>
                </div>

                <div class="col-md-4 col-sm-4">
                    <a href="<?= base_url('annonces') ?>" class="app-nav-card">
                        <div class="app-nav-icon blue">
                            <i class="fa fa-bullhorn"></i>
                        </div>
                        <h3>Annonces</h3>
                        <p>Générer une annonce à partir d'un besoin validé.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>