<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération Annonces</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">

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
        .templatemo-contact-form-2 .btn + .btn {
            margin-left: 8px;
        }
        .page-subtitle {
            color: #888;
            margin: -10px 0 25px;
            font-size: 14px;
        }
        /* Espacement entre l'icône et la liste déroulante de besoin */
        #besoin {
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
</head>
<body class="templatemo-bg-gray">
	<?= $this->include('navbar', ['active' => 'creer']) ?>
    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Génération Annonces</h1>
            <p class="text-center page-subtitle">
                Sélectionnez un besoin validé et rédigez le contenu de l'annonce.
            </p>

            <form class="form-horizontal templatemo-contact-form-2 templatemo-container"
                  role="form"
                  action="/annonces/creer"
                  method="post">

                <?= csrf_field() ?>

                <!-- Bloc 1 : Sélection du besoin -->
                <div class="row">
                    <div class="col-md-7 col-sm-7">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label for="besoin" class="control-label">Besoins Validés Par le DG</label>
                                <div class="templatemo-input-icon-container">
                                    <i class="fa fa-info-circle"></i>
                                    <select name="besoin" id="besoin" class="form-control">
  										<option value="">— Choisir un besoin —</option>
                                        <?php foreach ($besoins as $b) { ?>
                                            <option value="<?php echo $b->id; ?>">BESOIN00<?php echo $b->id . ' - ';?><?php echo $b->libelle; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 2 : Description pleine largeur -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label for="description" class="control-label">Description de l'annonce</label>
                                <div class="templatemo-input-icon-container">
                                    <i class="fa fa-pencil-square-o"></i>
                                    <textarea rows="10" cols="50" class="form-control" id="description" placeholder="description" name="desce"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 3 : Case à cocher à gauche + actions à droite -->
                <div class="form-group">
                    <div class="col-md-12">
                        <div class="checkbox pull-left">
                            <label>
                                <input type="checkbox"> Send a copy to my email.
                            </label>
                        </div>
                        <div class="pull-right">
                            <a href="<?= base_url('annonces') ?>" class="btn btn-default btn-annuler">
                                <i class="fa fa-times"></i> Annuler
                            </a>
                            <input type="submit" value="Valider" class="btn btn-warning">
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</body>
</html>