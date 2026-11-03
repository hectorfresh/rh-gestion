<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de besoin</title>

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
</head>
<body class="templatemo-bg-gray">
	<?= $this->include('navbar', ['active' => 'creer']) ?>
    <div class="container">
        <div class="col-md-12">
            <h1 class="text-center margin-bottom-15">Création De Besoin</h1>
            <p class="text-center page-subtitle">
                Renseignez les informations ci-dessous pour créer un nouveau besoin de recrutement.
            </p>

            <form class="form-horizontal templatemo-contact-form-2 templatemo-container"
                  role="form"
                  action="<?= base_url('besoins/creer') ?>"
                  method="post">

                <?= csrf_field() ?>

                <!-- Bloc 1 : Poste + Nombre sur la même ligne -->
                <div class="row">
                    <div class="col-md-7 col-sm-7">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label for="poste" class="control-label">
                                    Poste <span class="text-danger">*</span>
                                </label>
                                <div class="templatemo-input-icon-container">
                                    <i class="fa fa-briefcase"></i>
                                    <select name="poste" id="poste" class="form-control">
                                        <option value="">— Choisir un poste —</option>
                                        <?php foreach ($postes as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= esc($p['libelle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 col-sm-5">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label for="nombre" class="control-label">
                                    Nombre  <span class="text-danger">*</span>
                                </label>
                                <div class="templatemo-input-icon-container">
                                    <i class="fa fa-users"></i>
                                    <input type="number" class="form-control" id="nombre" name="nombre"
                                           min="1" step="1" placeholder="Ex : 3">
                                </div>
                                <small class="text-muted"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 2 : Description pleine largeur -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="col-sm-12">
                                <label for="description" class="control-label">
                                    Description du besoin <span class="text-danger">*</span>
                                </label>
                                <div class="templatemo-input-icon-container">
                                    <i class="fa fa-pencil-square-o"></i>
                                    <textarea rows="7" class="form-control" id="description"
                                              name="description"
                                              placeholder="Décrivez les missions, les compétences attendues, le contexte du recrutement..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 3 : Actions alignées à droite -->
                <div class="form-group">
                    <div class="col-md-12 text-right">
                        <a href="<?= base_url('besoins') ?>" class="btn btn-default btn-annuler">
                            <i class="fa fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-check"></i> Valider
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>