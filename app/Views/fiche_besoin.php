<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Besoin</title>

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
        .fiche-card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        .fiche-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .fiche-card-header h4 {
            margin: 0;
            font-weight: 600;
            color: #444;
            font-size: 17px;
        }
        .fiche-card-header h4 i {
            color: #f0ad4e;
            margin-right: 10px;
        }
        .fiche-id-badge {
            display: inline-block;
            padding: 5px 14px;
            background: #fdf0d5;
            color: #b8791a;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
        }
        .fiche-field {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f5f5f5;
        }
        .fiche-field:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .fiche-field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #999;
            margin-bottom: 6px;
        }
        .fiche-field-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        .fiche-field-value i {
            color: #f0ad4e;
            margin-right: 8px;
            font-size: 14px;
        }
        .fiche-field-value.description {
            font-weight: 400;
            color: #555;
            line-height: 1.7;
            white-space: pre-line;
        }
        .fiche-actions {
            text-align: right;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        .fiche-actions .btn {
            margin-left: 6px;
        }
    </style>
</head>
<body class="templatemo-bg-gray">
    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Fiche Besoin</h1>
            <p class="text-center page-subtitle">
                Détails du besoin et actions de validation.
            </p>

            <div class="fiche-card">
                <div class="fiche-card-header">
                    <h4><i class="fa fa-clipboard"></i> Besoin n°<?php echo $besoins['id'];?></h4>
                    <span class="fiche-id-badge">#<?php echo $besoins['id'];?></span>
                </div>

                <div class="fiche-field">
                    <span class="fiche-field-label">Numero</span>
                    <div class="fiche-field-value">
                        <i class="fa fa-hashtag"></i><?php echo $besoins['id'];?>
                    </div>
                </div>

                <div class="fiche-field">
                    <span class="fiche-field-label">Poste</span>
                    <div class="fiche-field-value">
                        <i class="fa fa-briefcase"></i><?php echo $besoins['libelle'];?>
                    </div>
                </div>

                <div class="fiche-field">
                    <span class="fiche-field-label">Nombre</span>
                    <div class="fiche-field-value">
                        <i class="fa fa-users"></i><?php echo $besoins['nombre'];?>
                    </div>
                </div>

                <div class="fiche-field">
                    <span class="fiche-field-label">Description</span>
                    <div class="fiche-field-value description"><?php echo $besoins['desce'];?></div>
                </div>

                <div class="fiche-actions">
                    <a href="/besoins/valider" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Retour à la liste
                    </a>
                    <a href="/besoins/refuser/<?php echo $besoins['id'];?>" class="btn btn-default">
                        <i class="fa fa-times"></i> Refuser
                    </a>
                    <a href="/besoins/valider/<?php echo $besoins['id'];?>" class="btn btn-success">
                        <i class="fa fa-check"></i> Valider
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>