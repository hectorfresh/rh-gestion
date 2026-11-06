<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demander un entretien</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/font-awesome.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/bootstrap-theme.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/templatemo_style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet" type="text/css">
</head>
<body class="templatemo-bg-gray">
<?= $this->include('navbar', ['active' => 'entretiens']) ?>

<div class="container">
    <div class="col-md-6 col-md-offset-3">
        <h1 class="text-center">Demander un entretien</h1>

        <?php if (session()->getFlashdata('error')) { ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php } ?>

        <p><strong>Candidat :</strong> <?= esc($candidature['nom']) ?></p>
        <p><strong>Poste :</strong> <?= esc($candidature['poste']) ?></p>

        <form action="<?= base_url('entretiens/save') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="candidature_id" value="<?= esc($candidature['id']) ?>">

            <div class="form-group">
                <label>Date et heure</label>
                <input type="datetime-local" name="date_entretien" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Lieu</label>
                <input type="text" name="lieu" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Commentaire</label>
                <textarea name="commentaire" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-warning">
                <i class="fa fa-paper-plane"></i> Envoyer
            </button>
            <a href="<?= base_url('entretiens') ?>" class="btn btn-default">Annuler</a>
        </form>
    </div>
</div>
</body>
</html>