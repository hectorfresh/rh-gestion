<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">

</head>
<style>
        /* Ajustements mineurs pour un meilleur rendu des labels dans form-horizontal */
        .templatemo-contact-form-2 .control-label {
            text-align: left !important;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            font-size: 13px;
            padding-top: 0;
        }
        .templatemo-contact-form-2 .form-group {
            margin-bottom: 20px;
        }
        .templatemo-contact-form-2 small.text-muted {
            display: block;
            margin-top: 4px;
            font-size: 11px;
        }
        .templatemo-contact-form-2 .btn + .btn {
            margin-left: 8px;
        }
        .page-subtitle {
            color: #888;
            margin: -10px 0 25px;
            font-size: 14px;
        }
        /* Espacement entre l'icône et la liste déroulante de poste */
        #poste {
            padding-left: 35px;
        }
        /* Bouton Annuler plus compact */
        .btn-annuler {
            padding: 3px 10px;
            font-size: 12px;
            line-height: 1.4;
            vertical-align: middle;
        }
        .btn-annuler i {
            font-size: 11px;
        }
    </style>
<body class="templatemo-bg-gray">
 <?= $this->include('navbar', ['active' => 'creer']) ?>

    
</body>
</html>