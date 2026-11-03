<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des besoins</title>

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
        .besoins-card {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        .besoins-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .besoins-card-header h4 {
            margin: 0;
            font-weight: 600;
            color: #444;
            font-size: 17px;
        }
        .besoins-card-header h4 i {
            color: #f0ad4e;
            margin-right: 10px;
        }
        .besoins-card-header .count-badge {
            background: #f0ad4e;
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .table-besoins {
            margin-bottom: 0;
        }
        .table-besoins thead th {
            background: #fafafa;
            color: #777;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #f0ad4e;
            padding: 12px 15px;
        }
        .table-besoins tbody td {
            vertical-align: middle;
            padding: 16px 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
        }
        .table-besoins tbody tr:last-child td {
            border-bottom: none;
        }
        .table-besoins tbody tr:hover {
            background: #fffbf2;
        }
        .besoin-id-badge {
            display: inline-block;
            min-width: 34px;
            padding: 5px 10px;
            background: #fdf0d5;
            color: #b8791a;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }
        .besoin-poste {
            color: #333;
            font-weight: 600;
            text-decoration: none;
        }
        .besoin-poste:hover {
            color: #f0ad4e;
            text-decoration: none;
        }
        .besoin-poste i {
            color: #f0ad4e;
            margin-right: 8px;
        }
        .actions .btn {
            margin-left: 4px;
        }
    </style>
</head>
<body class="templatemo-bg-gray">
    <?= $this->include('navbar', ['active' => 'creer']) ?>
    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Validation Des Besoins</h1>
            <p class="text-center page-subtitle">
                Consultez et validez les besoins en attente.
            </p>

            <div class="besoins-card">
                <div class="besoins-card-header">
                    <h4><i class="fa fa-clipboard"></i> Besoins à valider</h4>
                    <span class="count-badge"><?php echo count($besoins); ?> besoin(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-besoins">
                        <thead>
                            <tr>
                                <th style="width: 110px;">Numero</th>
                                <th>Poste</th>
                                <th class="text-right" style="width: 230px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($besoins as $b) {?>
                                <tr>
                                    <td>
                                        <a href="/besoins/<?php echo $b['id'];?>/" style="text-decoration: none;">
                                            <span class="besoin-id-badge">#<?php echo $b['id'];?></span>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="/besoins/<?php echo $b['id'];?>/" class="besoin-poste">
                                            <i class="fa fa-briefcase"></i><?php echo $b['libelle'];?>
                                        </a>
                                    </td>
                                    <td class="text-right actions">
                                        <a href="/besoins/valider/<?php echo $b['id'];?>" class="btn btn-sm btn-success">
                                            <i class="fa fa-check"></i> Valider
                                        </a>
                                        <a href="/besoins/refuser/<?php echo $b['id'];?>" class="btn btn-sm btn-default">
                                            <i class="fa fa-times"></i> Refuser
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
    </div>
</body>
</html>