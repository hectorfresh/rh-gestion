<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche candidature</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'candidatures']) ?>

<div class="container">
    <h1 class="text-center">Fiche de candidature</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="well">
                <h3><i class="fa fa-user"></i> Candidat</h3>
                <p><strong>Nom :</strong> <?= esc($candidature['nom']) ?></p>
                <p><strong>Email :</strong> <?= esc($candidature['email']) ?></p>
                <p><strong>Téléphone :</strong> <?= esc($candidature['telephone'] ?? '') ?></p>
                <p><strong>Adresse :</strong> <?= esc($candidature['adresse'] ?? '') ?></p>
                <p><strong>Ville :</strong> <?= esc($candidature['ville'] ?? '') ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="well">
                <h3><i class="fa fa-briefcase"></i> Candidature</h3>
                <p><strong>Poste :</strong> <?= esc($candidature['poste']) ?></p>
                <p><strong>Statut :</strong> <?= esc($candidature['statut']) ?></p>
                <p><strong>Date :</strong> <?= esc($candidature['date_candidature']) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($cv)) { ?>
        <div class="well">
            <h3><i class="fa fa-file-text-o"></i> CV</h3>
            <p><strong>Titre :</strong> <?= esc($cv['titre']) ?></p>
            <p><strong>Présentation :</strong><br><?= nl2br(esc($cv['descri'] ?? '')) ?></p>
        </div>
    <?php } ?>

    <?php if (!empty($formations)) { ?>
        <div class="well">
            <h3><i class="fa fa-graduation-cap"></i> Formations</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Diplôme</th>
                        <th>Établissement</th>
                        <th>Début</th>
                        <th>Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($formations as $f) { ?>
                        <tr>
                            <td><?= esc($f['diplome']) ?></td>
                            <td><?= esc($f['etablissement']) ?></td>
                            <td><?= esc($f['date_debut']) ?></td>
                            <td><?= esc($f['date_fin']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <?php if (!empty($experiences)) { ?>
        <div class="well">
            <h3><i class="fa fa-suitcase"></i> Expériences</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Poste</th>
                        <th>Entreprise</th>
                        <th>Début</th>
                        <th>Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($experiences as $e) { ?>
                        <tr>
                            <td><?= esc($e['poste']) ?></td>
                            <td><?= esc($e['entreprise']) ?></td>
                            <td><?= esc($e['date_debut']) ?></td>
                            <td><?= esc($e['date_fin']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <?php if (!empty($langues)) { ?>
        <div class="well">
            <h3><i class="fa fa-language"></i> Langues</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Langue</th>
                        <th>Niveau</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($langues as $l) { ?>
                        <tr>
                            <td><?= esc($l['langue']) ?></td>
                            <td><?= esc($l['niveau']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <div class="text-center">
        <a href="<?= base_url('candidatures/liste') ?>" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Retour
        </a>
    </div>
</div>
</body>
</html>