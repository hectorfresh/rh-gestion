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

<div class="container">
    <div class="col-md-12">
        <h1 class="text-center margin-bottom-15">Postuler avec mon CV</h1>
        <p class="text-center page-subtitle">Remplissez votre CV puis envoyez votre candidature.</p>

        <?php if (session()->getFlashdata('error')) { ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php } ?>
        <?php if (session()->getFlashdata('success')) { ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php } ?>

        <form class="form-horizontal templatemo-contact-form-2 templatemo-container" role="form"
              action="<?= base_url('postuler') ?>" method="post">
            <?= csrf_field() ?>

            <!-- ====== POSTE VISÉ + CV ====== -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="poste">Poste visé</label>
                            <p class="form-control-static" style="font-weight: bold; font-size: 1.2em; padding-top: 7px;"><?= esc($poste['titre']) ?></p>
                            <input type="hidden" name="annonce_id" value="<?= esc($poste['annonce_id']) ?>">

                           
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="titre">Titre du CV</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-file-text-o"></i>
                                <input type="text" class="form-control" id="titre" name="titre" maxlength="50"
                                       placeholder="Ex : Développeur web junior" value="<?= esc(old('titre')) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="descri">Présentation</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-pencil-square-o"></i>
                                <textarea rows="5" class="form-control" id="descri" name="descri"
                                          placeholder="Quelques lignes sur votre profil"><?= esc(old('descri')) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== IDENTITÉ ====== -->
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="email">Email</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" maxlength="50"
                                       value="<?= esc(old('email', session()->get('user')['email'] ?? '')) ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="telephone">Téléphone</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-phone"></i>
                                <input type="text" class="form-control" id="telephone" name="telephone" maxlength="20"
                                       value="<?= esc(old('telephone')) ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="adresse">Adresse</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-home"></i>
                                <input type="text" class="form-control" id="adresse" name="adresse" maxlength="100"
                                       value="<?= esc(old('adresse')) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label class="control-label" for="ville">Ville</label>
                            <div class="templatemo-input-icon-container">
                                <i class="fa fa-map-marker"></i>
                                <input type="text" class="form-control" id="ville" name="ville" maxlength="50"
                                       value="<?= esc(old('ville')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <!-- ====== FORMATIONS ====== -->
            <h4><i class="fa fa-graduation-cap"></i> Formations</h4>
            <div id="list-formation"></div>
            <button type="button" class="btn btn-default btn-sm" data-add="formation">
                <i class="fa fa-plus"></i> Ajouter une formation
            </button>

            <hr>

            <!-- ====== EXPÉRIENCES ====== -->
            <h4><i class="fa fa-suitcase"></i> Expériences</h4>
            <div id="list-experience"></div>
            <button type="button" class="btn btn-default btn-sm" data-add="experience">
                <i class="fa fa-plus"></i> Ajouter une expérience
            </button>

            <hr>

            <!-- ====== LANGUES ====== -->
            <h4><i class="fa fa-language"></i> Langues</h4>
            <div id="list-langue"></div>
            <button type="button" class="btn btn-default btn-sm" data-add="langue">
                <i class="fa fa-plus"></i> Ajouter une langue
            </button>

            <hr>

            <div class="form-group">
                <div class="col-md-12">
                    <a href="<?= base_url('/') ?>" class="btn btn-default btn-annuler">
                        <i class="fa fa-times"></i> Annuler
                    </a>
                    <input type="submit" value="ENVOYER MA CANDIDATURE" class="btn btn-warning pull-right">
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ====== MODÈLES DES BLOCS RÉPÉTABLES ====== -->
<template id="tpl-formation">
    <div class="repeat-item well well-sm">
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Diplôme</label>
                <input type="text" class="form-control" name="diplome[]" maxlength="50">
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Établissement</label>
                <input type="text" class="form-control" name="etablissement[]" maxlength="50">
            </div>
            <div class="col-md-5 form-group">
                <label class="control-label">Début</label>
                <input type="date" class="form-control" name="formation_debut[]">
            </div>
            <div class="col-md-5 form-group">
                <label class="control-label">Fin</label>
                <input type="date" class="form-control" name="formation_fin[]">
            </div>
            <div class="col-md-2 form-group text-right">
                <label class="control-label">&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-annuler btn-remove"><i class="fa fa-trash"></i> Retirer</button>
            </div>
        </div>
    </div>
</template>

<template id="tpl-experience">
    <div class="repeat-item well well-sm">
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="control-label">Poste occupé</label>
                <input type="text" class="form-control" name="poste_exp[]" maxlength="50">
            </div>
            <div class="col-md-6 form-group">
                <label class="control-label">Entreprise</label>
                <input type="text" class="form-control" name="entreprise[]" maxlength="50">
            </div>
            <div class="col-md-5 form-group">
                <label class="control-label">Début</label>
                <input type="date" class="form-control" name="exp_debut[]">
            </div>
            <div class="col-md-5 form-group">
                <label class="control-label">Fin</label>
                <input type="date" class="form-control" name="exp_fin[]">
            </div>
            <div class="col-md-2 form-group text-right">
                <label class="control-label">&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-annuler btn-remove"><i class="fa fa-trash"></i> Retirer</button>
            </div>
        </div>
    </div>
</template>

<template id="tpl-langue">
    <div class="repeat-item well well-sm">
        <div class="row">
            <div class="col-md-5 form-group">
                <label class="control-label">Langue</label>
                <select class="form-control" name="langue_id[]">
                    <option value="">-- Choisir --</option>
                    <?php foreach (($langues ?? []) as $l) { ?>
                        <option value="<?= esc($l['id']) ?>"><?= esc($l['libelle']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-5 form-group">
                <label class="control-label">Niveau</label>
                <select class="form-control" name="niveau_id[]">
                    <option value="">-- Choisir --</option>
                    <?php foreach (($niveaux ?? []) as $n) { ?>
                        <option value="<?= esc($n['id']) ?>"><?= esc($n['libelle']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2 form-group text-right">
                <label class="control-label">&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-annuler btn-remove"><i class="fa fa-trash"></i> Retirer</button>
            </div>
        </div>
    </div>
</template>

<script>
    // Ajouter un bloc
    document.querySelectorAll('[data-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-add');
            var tpl = document.getElementById('tpl-' + key);
            document.getElementById('list-' + key).appendChild(tpl.content.cloneNode(true));
        });
    });

    // Retirer un bloc
    document.addEventListener('click', function (e) {
        var rm = e.target.closest('.btn-remove');
        if (rm) rm.closest('.repeat-item').remove();
    });

    // Un bloc de chaque type affiché au départ
    document.querySelectorAll('[data-add]').forEach(function (btn) { btn.click(); });
</script>
</body>
</html>
    
</body>
</html>